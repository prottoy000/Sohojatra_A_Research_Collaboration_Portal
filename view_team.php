<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "Student") {
    header("Location: login.php");
    exit();
}

$student_id = (int)$_SESSION['user_id'];

$sql = "SELECT Team.Team_ID,
               Team.Team_Name,
               Team.Project_ID,
               Team.Creation_Date,
               Team.Faculty_ID,
               p.Title AS Project_Title,
               f_user.First_Name AS Faculty_First,
               f_user.Last_Name AS Faculty_Last,
               fac.Designation
        FROM Team
        JOIN Joins ON Team.Team_ID = Joins.Team_ID
        LEFT JOIN Project p ON Team.Project_ID = p.Project_ID
        LEFT JOIN Faculty fac ON Team.Faculty_ID = fac.Faculty_ID
        LEFT JOIN User f_user ON fac.Faculty_ID = f_user.User_ID
        WHERE Joins.User_ID = ?
        ORDER BY Team.Team_ID DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$page_title = "My Research Teams — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Research Teams</h1>
                <p class="page-subtitle">Your active research groups, team rosters, and faculty supervisors</p>
            </div>
            <div class="page-actions">
                <a href="join_team.php" class="btn btn-outline">Explore Teams</a>
                <a href="create_team.php" class="btn btn-primary">+ Form New Team</a>
            </div>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($team = $result->fetch_assoc()): ?>
                <?php
                $team_id = (int)$team['Team_ID'];

                // Query members of this team
                $member_sql = "SELECT User.User_ID,
                                      User.First_Name,
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
                $members = $m_stmt->get_result();

                $is_leader = false;
                ?>

                <div class="card" style="margin-bottom: 2rem;">
                    <div class="card-header" style="flex-wrap: wrap; gap: 0.75rem;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                <span class="badge badge-active">Team #<?php echo htmlspecialchars($team['Team_ID']); ?></span>
                                <?php if (!empty($team['Project_ID'])): ?>
                                    <span class="badge badge-low">
                                        Project: <?php echo htmlspecialchars(!empty($team['Project_Title']) ? $team['Project_Title'] : '#' . $team['Project_ID']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">
                                <?php echo htmlspecialchars($team['Team_Name']); ?>
                            </h2>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">
                            Formed on: <?php echo htmlspecialchars($team['Creation_Date']); ?>
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- Faculty Supervisor Info -->
                        <div style="margin-bottom: 1.25rem;">
                            <h3 style="font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted); margin-bottom: 0.4rem;">
                                Faculty Supervisor
                            </h3>
                            <?php if (!empty($team['Faculty_First'])): ?>
                                <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.65rem 1rem; background: var(--primary-light); border: 1px solid var(--primary-border); border-radius: var(--radius-md); font-size: 0.9rem;">
                                    <strong>Dr. <?php echo htmlspecialchars($team['Faculty_First'] . ' ' . $team['Faculty_Last']); ?></strong>
                                    <span style="color: var(--text-muted);">
                                        &bull; <?php echo htmlspecialchars(!empty($team['Designation']) ? $team['Designation'] : 'Faculty'); ?>
                                    </span>
                                </div>
                            <?php else: ?>
                                <p style="color: var(--text-muted); font-size: 0.875rem;">No faculty supervisor assigned yet.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Team Members Table -->
                        <div>
                            <h3 style="font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted); margin-bottom: 0.6rem;">
                                Team Members
                            </h3>
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
                                        <?php while ($member = $members->fetch_assoc()): ?>
                                            <?php 
                                            if ((int)$member['User_ID'] === $student_id && $member['Role'] === 'Leader') {
                                                $is_leader = true;
                                            }
                                            $role_class = ($member['Role'] === 'Leader') ? 'badge-active' : 'badge-low';
                                            ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($member['First_Name'] . ' ' . $member['Last_Name']); ?></strong>
                                                    <?php if ((int)$member['User_ID'] === $student_id): ?>
                                                        <span style="font-size: 0.75rem; color: var(--primary); font-weight: 600;">(You)</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="color: var(--text-muted); font-size: 0.85rem;">
                                                    <?php echo htmlspecialchars($member['Email']); ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $role_class; ?>">
                                                        <?php echo htmlspecialchars($member['Role']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <?php if ($is_leader): ?>
                            <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--border-subtle); display: flex; gap: 0.75rem;">
                                <a href="team_requests.php" class="btn btn-sm btn-outline">
                                    Review Pending Join Requests
                                </a>
                                <a href="manage_teams.php" class="btn btn-sm btn-secondary">
                                    Manage Team Roster
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php $m_stmt->close(); ?>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">&#128101;</div>
                <h2 class="empty-title">You Have Not Joined Any Teams Yet</h2>
                <p class="empty-description">
                    Collaborative research requires teamwork. Form a new team for your project or browse peer teams to join.
                </p>
                <div style="display: flex; justify-content: center; gap: 0.75rem; margin-top: 1rem;">
                    <a href="create_team.php" class="btn btn-primary">+ Form New Team</a>
                    <a href="join_team.php" class="btn btn-outline">Explore Available Teams</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>