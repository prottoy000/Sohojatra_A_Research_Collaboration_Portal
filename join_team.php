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

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['team_id'])) {
    $team_id = (int)$_POST['team_id'];

    // 1. Check if already a member of the team
    $check_stmt = $conn->prepare("SELECT User_ID FROM Joins WHERE User_ID = ? AND Team_ID = ?");
    $check_stmt->bind_param("ii", $student_id, $team_id);
    $check_stmt->execute();
    $joined_result = $check_stmt->get_result();

    if ($joined_result->num_rows > 0) {
        $message = "You are already a member of this research team.";
        $message_type = "warning";
    } else {
        // 2. Check if a request already exists
        $req_stmt = $conn->prepare("SELECT Status FROM Join_Request WHERE User_ID = ? AND Team_ID = ?");
        $req_stmt->bind_param("ii", $student_id, $team_id);
        $req_stmt->execute();
        $existing_req = $req_stmt->get_result();

        if ($existing_req->num_rows > 0) {
            $req_row = $existing_req->fetch_assoc();

            if ($req_row['Status'] === 'Pending') {
                $message = "You have already sent a join request to this team (Status: Pending Review).";
                $message_type = "warning";
            } elseif ($req_row['Status'] === 'Accepted') {
                $message = "Your request was already accepted. You are a member of this team.";
                $message_type = "info";
            } else {
                // Previously rejected: allow re-submitting request
                $reapply_stmt = $conn->prepare("UPDATE Join_Request SET Status = 'Pending', Request_Date = CURDATE() WHERE User_ID = ? AND Team_ID = ?");
                $reapply_stmt->bind_param("ii", $student_id, $team_id);
                if ($reapply_stmt->execute()) {
                    $message = "Join request re-sent successfully.";
                    $message_type = "success";
                } else {
                    $message = "Error sending request. Please try again.";
                    $message_type = "danger";
                }
                $reapply_stmt->close();
            }
        } else {
            // 3. Insert new request with safe sequential Request_ID
            $max_res = mysqli_query($conn, "SELECT COALESCE(MAX(Request_ID), 0) + 1 AS next_id FROM Join_Request");
            $next_id = 1;
            if ($max_res && $max_row = mysqli_fetch_assoc($max_res)) {
                $next_id = (int)$max_row['next_id'];
            }

            $ins_stmt = $conn->prepare("INSERT INTO Join_Request (Request_ID, User_ID, Team_ID, Request_Date, Status) VALUES (?, ?, ?, CURDATE(), 'Pending')");
            $ins_stmt->bind_param("iii", $next_id, $student_id, $team_id);

            if ($ins_stmt->execute()) {
                $message = "Join request sent successfully to the team leader!";
                $message_type = "success";
            } else {
                $message = "Error submitting join request.";
                $message_type = "danger";
            }
            $ins_stmt->close();
        }
        $req_stmt->close();
    }
    $check_stmt->close();
}

// Fetch all teams with project and faculty info
$sql = "SELECT t.Team_ID,
               t.Team_Name,
               t.Project_ID,
               t.Creation_Date,
               p.Title AS Project_Title,
               p.Domain,
               u.First_Name AS Faculty_First,
               u.Last_Name AS Faculty_Last
        FROM Team t
        LEFT JOIN Project p ON t.Project_ID = p.Project_ID
        LEFT JOIN Faculty f ON t.Faculty_ID = f.Faculty_ID
        LEFT JOIN User u ON f.Faculty_ID = u.User_ID
        ORDER BY t.Team_ID DESC";

$result = mysqli_query($conn, $sql);

// Pre-fetch student's memberships and pending requests
$my_memberships = [];
$mem_res = $conn->query("SELECT Team_ID FROM Joins WHERE User_ID = $student_id");
if ($mem_res) {
    while ($r = $mem_res->fetch_assoc()) {
        $my_memberships[] = (int)$r['Team_ID'];
    }
}

$my_requests = [];
$req_res = $conn->query("SELECT Team_ID, Status FROM Join_Request WHERE User_ID = $student_id");
if ($req_res) {
    while ($r = $req_res->fetch_assoc()) {
        $my_requests[(int)$r['Team_ID']] = $r['Status'];
    }
}

$page_title = "Explore Research Teams — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Explore Research Teams</h1>
                <p class="page-subtitle">Discover university collaborative research groups and submit membership requests</p>
            </div>
            <div class="page-actions">
                <a href="view_team.php" class="btn btn-outline">&larr; My Teams</a>
                <a href="create_team.php" class="btn btn-primary">+ Form Team</a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <span class="alert-icon">
                    <?php echo ($message_type === 'success') ? '✓' : (($message_type === 'danger') ? '✕' : '⚠'); ?>
                </span>
                <div><?php echo htmlspecialchars($message); ?></div>
            </div>
        <?php endif; ?>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Team ID</th>
                            <th>Team Name</th>
                            <th>Research Project</th>
                            <th>Domain</th>
                            <th>Supervisor</th>
                            <th>Status / Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($team = mysqli_fetch_assoc($result)): ?>
                            <?php 
                            $t_id = (int)$team['Team_ID'];
                            $is_member = in_array($t_id, $my_memberships);
                            $req_status = isset($my_requests[$t_id]) ? $my_requests[$t_id] : null;
                            ?>
                            <tr>
                                <td><strong>#<?php echo htmlspecialchars($team['Team_ID']); ?></strong></td>
                                <td style="font-weight: 600; color: var(--text-main);">
                                    <?php echo htmlspecialchars($team['Team_Name']); ?>
                                </td>
                                <td>
                                    <?php if (!empty($team['Project_Title'])): ?>
                                        <?php echo htmlspecialchars($team['Project_Title']); ?>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">Independent Team</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($team['Domain'])): ?>
                                        <span class="badge badge-low"><?php echo htmlspecialchars($team['Domain']); ?></span>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($team['Faculty_First'])): ?>
                                        Dr. <?php echo htmlspecialchars($team['Faculty_First'] . ' ' . $team['Faculty_Last']); ?>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($is_member): ?>
                                        <span class="badge badge-active">Already Joined</span>
                                    <?php elseif ($req_status === 'Pending'): ?>
                                        <span class="badge badge-pending">Request Pending</span>
                                    <?php else: ?>
                                        <form method="POST" style="margin: 0;">
                                            <input type="hidden" name="team_id" value="<?php echo htmlspecialchars($team['Team_ID']); ?>">
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                Request to Join
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">&#128101;</div>
                <h2 class="empty-title">No Teams Available</h2>
                <p class="empty-description">There are no research teams registered in the portal yet.</p>
                <a href="create_team.php" class="btn btn-primary">+ Form First Team</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
