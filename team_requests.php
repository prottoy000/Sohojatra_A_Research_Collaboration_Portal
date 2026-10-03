<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "Student") {
    header("Location: login.php");
    exit();
}

$student_id = (int)$_SESSION['user_id'];
$message = "";
$message_type = "info";

if (isset($_POST['accept']) || isset($_POST['reject'])) {
    $req_student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
    $team_id = isset($_POST['team_id']) ? (int)$_POST['team_id'] : 0;

    // Fallback if student_id/team_id was not directly sent
    if (($req_student_id === 0 || $team_id === 0) && isset($_POST['request_id'])) {
        $req_id = (int)$_POST['request_id'];
        $f_stmt = $conn->prepare("SELECT User_ID, Team_ID FROM Join_Request WHERE Request_ID = ? AND Status = 'Pending' LIMIT 1");
        $f_stmt->bind_param("i", $req_id);
        $f_stmt->execute();
        $f_res = $f_stmt->get_result();
        if ($f_row = $f_res->fetch_assoc()) {
            $req_student_id = (int)$f_row['User_ID'];
            $team_id = (int)$f_row['Team_ID'];
        }
        $f_stmt->close();
    }

    if ($req_student_id > 0 && $team_id > 0) {
        // Verify current user is Leader of this team
        $leader_stmt = $conn->prepare("SELECT User_ID FROM Joins WHERE User_ID = ? AND Team_ID = ? AND Role = 'Leader'");
        $leader_stmt->bind_param("ii", $student_id, $team_id);
        $leader_stmt->execute();
        $leader_res = $leader_stmt->get_result();

        if ($leader_res->num_rows > 0) {
            if (isset($_POST['accept'])) {
                // Check if already in team
                $check_m = $conn->prepare("SELECT User_ID FROM Joins WHERE User_ID = ? AND Team_ID = ?");
                $check_m->bind_param("ii", $req_student_id, $team_id);
                $check_m->execute();
                if ($check_m->get_result()->num_rows === 0) {
                    $ins_m = $conn->prepare("INSERT INTO Joins (User_ID, Team_ID, Role, Date) VALUES (?, ?, 'Member', CURDATE())");
                    $ins_m->bind_param("ii", $req_student_id, $team_id);
                    $ins_m->execute();
                    $ins_m->close();
                }
                $check_m->close();

                $upd_req = $conn->prepare("UPDATE Join_Request SET Status = 'Accepted' WHERE User_ID = ? AND Team_ID = ?");
                $upd_req->bind_param("ii", $req_student_id, $team_id);
                $upd_req->execute();
                $upd_req->close();

                $message = "Student request approved! Added as team member.";
                $message_type = "success";
            } elseif (isset($_POST['reject'])) {
                $upd_req = $conn->prepare("UPDATE Join_Request SET Status = 'Rejected' WHERE User_ID = ? AND Team_ID = ?");
                $upd_req->bind_param("ii", $req_student_id, $team_id);
                $upd_req->execute();
                $upd_req->close();

                $message = "Student request rejected.";
                $message_type = "danger";
            }
        } else {
            $message = "Permission denied: Only the designated team leader can manage join requests.";
            $message_type = "danger";
        }
        $leader_stmt->close();
    }
}

// Fetch all pending requests for teams led by this student
$sql = "SELECT jr.Request_ID,
               jr.User_ID AS Applicant_ID,
               jr.Team_ID,
               jr.Request_Date,
               jr.Status,
               u.First_Name,
               u.Last_Name,
               u.Email,
               u.Department,
               t.Team_Name
        FROM Join_Request jr
        JOIN Team t ON jr.Team_ID = t.Team_ID
        JOIN Joins j ON t.Team_ID = j.Team_ID
        JOIN User u ON jr.User_ID = u.User_ID
        WHERE j.User_ID = ?
          AND j.Role = 'Leader'
          AND jr.Status = 'Pending'
        ORDER BY jr.Request_Date ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$page_title = "Team Join Inquiries — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Team Join Inquiries</h1>
                <p class="page-subtitle">Review and evaluate student applications to join the research teams you lead</p>
            </div>
            <div class="page-actions">
                <a href="view_team.php" class="btn btn-outline">&larr; My Teams</a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <span class="alert-icon">
                    <?php echo ($message_type === 'success') ? '✓' : (($message_type === 'danger') ? '✕' : 'ℹ'); ?>
                </span>
                <div><?php echo htmlspecialchars($message); ?></div>
            </div>
        <?php endif; ?>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Department</th>
                            <th>Email</th>
                            <th>Target Team</th>
                            <th>Date Applied</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['First_Name'] . ' ' . $row['Last_Name']); ?></strong>
                                    <div style="font-size: 0.775rem; color: var(--text-muted);">ID: <?php echo htmlspecialchars($row['Applicant_ID']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($row['Department']); ?></td>
                                <td style="color: var(--text-muted);"><?php echo htmlspecialchars($row['Email']); ?></td>
                                <td>
                                    <span class="badge badge-low"><?php echo htmlspecialchars($row['Team_Name']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($row['Request_Date']); ?></td>
                                <td>
                                    <form method="POST" style="display: inline-flex; gap: 0.5rem; margin: 0;">
                                        <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($row['Request_ID']); ?>">
                                        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($row['Applicant_ID']); ?>">
                                        <input type="hidden" name="team_id" value="<?php echo htmlspecialchars($row['Team_ID']); ?>">
                                        
                                        <button type="submit" name="accept" class="btn btn-sm btn-success" data-confirm="Accept this student into your research team?">
                                            Accept
                                        </button>
                                        <button type="submit" name="reject" class="btn btn-sm btn-danger" data-confirm="Decline this join request?">
                                            Decline
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">&#9989;</div>
                <h2 class="empty-title">All Caught Up!</h2>
                <p class="empty-description">
                    There are no pending join requests for any of the research teams you currently lead.
                </p>
                <a href="view_team.php" class="btn btn-outline">&larr; Return to My Teams</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
