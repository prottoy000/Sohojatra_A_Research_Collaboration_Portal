<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$user_id = (int)$_SESSION['user_id'];

if ($role !== "Faculty" && $role !== "Admin") {
    header("Location: login.php");
    exit();
}

$message = "";
$message_type = "info";

if ($role === "Faculty" && isset($_POST['action'])) {
    $student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
    $team_id = isset($_POST['team_id']) ? (int)$_POST['team_id'] : 0;
    $action = $_POST['action'];

    // Fallback: If student_id or team_id was not directly sent, resolve via request_id
    if (($student_id === 0 || $team_id === 0) && isset($_POST['request_id'])) {
        $req_id = (int)$_POST['request_id'];
        $req_stmt = $conn->prepare("SELECT User_ID, Team_ID FROM Join_Request WHERE Request_ID = ? AND Status = 'Pending' LIMIT 1");
        $req_stmt->bind_param("i", $req_id);
        $req_stmt->execute();
        $req_res = $req_stmt->get_result();
        if ($req_row = $req_res->fetch_assoc()) {
            $student_id = (int)$req_row['User_ID'];
            $team_id = (int)$req_row['Team_ID'];
        }
        $req_stmt->close();
    }

    if ($student_id > 0 && $team_id > 0) {
        // Verify the faculty supervises this team
        $team_stmt = $conn->prepare("SELECT Team_ID FROM Team WHERE Team_ID = ? AND Faculty_ID = ?");
        $team_stmt->bind_param("ii", $team_id, $user_id);
        $team_stmt->execute();
        $team_res = $team_stmt->get_result();

        if ($team_res->num_rows === 1) {
            if ($action === "accept") {
                $check_stmt = $conn->prepare("SELECT User_ID FROM Joins WHERE User_ID = ? AND Team_ID = ?");
                $check_stmt->bind_param("ii", $student_id, $team_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows === 0) {
                    $insert_stmt = $conn->prepare("INSERT INTO Joins (User_ID, Team_ID, Role, Date) VALUES (?, ?, 'Member', CURDATE())");
                    $insert_stmt->bind_param("ii", $student_id, $team_id);
                    $insert_stmt->execute();
                    $insert_stmt->close();
                }
                $check_stmt->close();

                $update_stmt = $conn->prepare("UPDATE Join_Request SET Status = 'Accepted' WHERE User_ID = ? AND Team_ID = ?");
                $update_stmt->bind_param("ii", $student_id, $team_id);
                $update_stmt->execute();
                $update_stmt->close();

                $message = "Student join request accepted successfully! Added to team roster.";
                $message_type = "success";
            } elseif ($action === "reject") {
                $update_stmt = $conn->prepare("UPDATE Join_Request SET Status = 'Rejected' WHERE User_ID = ? AND Team_ID = ?");
                $update_stmt->bind_param("ii", $student_id, $team_id);
                $update_stmt->execute();
                $update_stmt->close();

                $message = "Student join request rejected.";
                $message_type = "danger";
            }
        }
        $team_stmt->close();
    }
}

if ($role === "Faculty") {
    $team_sql = "SELECT t.Team_ID,
                        t.Team_Name,
                        t.Creation_Date,
                        t.Project_ID,
                        p.Title AS Project_Title
                 FROM Team t
                 LEFT JOIN Project p ON t.Project_ID = p.Project_ID
                 WHERE t.Faculty_ID = ?
                 ORDER BY t.Team_ID DESC";
    $stmt = $conn->prepare($team_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $team_result = $stmt->get_result();
} else {
    $team_sql = "SELECT t.Team_ID,
                        t.Team_Name,
                        t.Creation_Date,
                        t.Project_ID,
                        p.Title AS Project_Title,
                        u.First_Name AS Faculty_First,
                        u.Last_Name AS Faculty_Last
                 FROM Team t
                 LEFT JOIN Project p ON t.Project_ID = p.Project_ID
                 LEFT JOIN Faculty f ON t.Faculty_ID = f.Faculty_ID
                 LEFT JOIN User u ON f.Faculty_ID = u.User_ID
                 ORDER BY t.Team_ID DESC";
    $team_result = mysqli_query($conn, $team_sql);
}

$page_title = "Manage Research Teams — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Manage Research Teams</h1>
                <p class="page-subtitle">
                    <?php echo ($role === 'Faculty') ? 'Review team members, evaluate pending student applications, and monitor team activities' : 'Central registry of all research teams across university faculties'; ?>
                </p>
            </div>
            <div class="page-actions">
                <?php if ($role === 'Faculty'): ?>
                    <a href="create_team.php" class="btn btn-primary">+ Create New Team</a>
                <?php else: ?>
                    <a href="admin_dashboard.php" class="btn btn-outline">&larr; Admin Console</a>
                <?php endif; ?>
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

        <?php if ($team_result && $team_result->num_rows > 0): ?>
            <?php while ($team = $team_result->fetch_assoc()): ?>
                <?php $team_id = (int)$team['Team_ID']; ?>

                <div class="card" style="margin-bottom: 2rem;">
                    <div class="card-header" style="flex-wrap: wrap; gap: 0.75rem;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                <span class="badge badge-active">Team #<?php echo htmlspecialchars($team['Team_ID']); ?></span>
                                <?php if (!empty($team['Project_Title'])): ?>
                                    <span class="badge badge-low">Project: <?php echo htmlspecialchars($team['Project_Title']); ?></span>
                                <?php endif; ?>
                            </div>
                            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">
                                <?php echo htmlspecialchars($team['Team_Name']); ?>
                            </h2>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">
                            Created on: <?php echo htmlspecialchars($team['Creation_Date']); ?>
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- Members Section -->
                        <div style="margin-bottom: 1.5rem;">
                            <h3 style="font-size: 0.95rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted); margin-bottom: 0.6rem;">
                                Team Roster
                            </h3>
                            <?php
                            $member_sql = "SELECT User.First_Name,
                                                  User.Last_Name,
                                                  User.Email,
                                                  Joins.Role
                                           FROM User
                                           JOIN Joins ON User.User_ID = Joins.User_ID
                                           WHERE Joins.Team_ID = ?
                                           ORDER BY (Joins.Role = 'Leader') DESC, User.First_Name ASC";
                            $m_stmt = $conn->prepare($member_sql);
                            $m_stmt->bind_param("i", $team_id);
                            $m_stmt->execute();
                            $member_result = $m_stmt->get_result();
                            ?>
                            <?php if ($member_result && $member_result->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Member Name</th>
                                                <th>Institutional Email</th>
                                                <th>Role</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($member = $member_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($member['First_Name'] . ' ' . $member['Last_Name']); ?></strong></td>
                                                    <td style="color: var(--text-muted);"><?php echo htmlspecialchars($member['Email']); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo ($member['Role'] === 'Leader') ? 'badge-active' : 'badge-low'; ?>">
                                                            <?php echo htmlspecialchars($member['Role']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p style="color: var(--text-muted); font-size: 0.875rem;">No members recorded in this team.</p>
                            <?php endif; ?>
                            <?php $m_stmt->close(); ?>
                        </div>

                        <!-- Faculty Join Requests Section -->
                        <?php if ($role === "Faculty"): ?>
                            <?php
                            $request_sql = "SELECT Join_Request.Request_ID,
                                                   Join_Request.User_ID,
                                                   Join_Request.Request_Date,
                                                   User.First_Name,
                                                   User.Last_Name,
                                                   User.Email
                                            FROM Join_Request
                                            JOIN User ON Join_Request.User_ID = User.User_ID
                                            WHERE Join_Request.Team_ID = ? AND Join_Request.Status = 'Pending'
                                            ORDER BY Join_Request.Request_Date ASC";
                            $req_stmt = $conn->prepare($request_sql);
                            $req_stmt->bind_param("i", $team_id);
                            $req_stmt->execute();
                            $request_result = $req_stmt->get_result();
                            ?>
                            <div style="padding-top: 1rem; border-top: 1px solid var(--border-subtle);">
                                <h3 style="font-size: 0.95rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted); margin-bottom: 0.6rem;">
                                    Pending Join Inquiries
                                </h3>
                                <?php if ($request_result && $request_result->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Applicant</th>
                                                    <th>Email</th>
                                                    <th>Applied On</th>
                                                    <th>Evaluation Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($req = $request_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($req['First_Name'] . ' ' . $req['Last_Name']); ?></strong></td>
                                                        <td style="color: var(--text-muted);"><?php echo htmlspecialchars($req['Email']); ?></td>
                                                        <td><?php echo htmlspecialchars($req['Request_Date']); ?></td>
                                                        <td>
                                                            <form method="POST" style="display: inline-flex; gap: 0.5rem; margin: 0;">
                                                                <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($req['Request_ID']); ?>">
                                                                <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($req['User_ID']); ?>">
                                                                <input type="hidden" name="team_id" value="<?php echo htmlspecialchars($team_id); ?>">
                                                                <button type="submit" name="action" value="accept" class="btn btn-sm btn-success" data-confirm="Accept this student into the research team?">
                                                                    Accept
                                                                </button>
                                                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger" data-confirm="Reject this student's request?">
                                                                    Reject
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p style="color: var(--text-muted); font-size: 0.875rem;">No pending membership inquiries for this team.</p>
                                <?php endif; ?>
                                <?php $req_stmt->close(); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">&#128101;</div>
                <h2 class="empty-title">No Teams Found</h2>
                <p class="empty-description">
                    <?php echo ($role === 'Faculty') ? 'You are not supervising any research teams yet.' : 'No research teams have been registered in the system.'; ?>
                </p>
                <?php if ($role === 'Faculty'): ?>
                    <a href="create_team.php" class="btn btn-primary">+ Create First Team</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
