<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "Admin") {
    header("Location: login.php");
    exit();
}

// 1. Users
$user_sql = "SELECT User_ID, First_Name, Last_Name, Department, Email FROM user ORDER BY User_ID ASC";
$user_result = $conn->query($user_sql);

// 2. Projects
$project_sql = "SELECT Project_ID, Title, Student_ID, Domain, Status, Creation_Date FROM project ORDER BY Project_ID DESC";
$project_result = $conn->query($project_sql);

// 3. Teams
$team_sql = "SELECT Team_ID, Team_Name, Project_ID, Faculty_ID, Creation_Date FROM team ORDER BY Team_ID DESC";
$team_result = $conn->query($team_sql);

// 4. Tasks
$task_sql = "SELECT Task_ID, Title, Priority, Status, Student_ID, Deadline FROM tasks ORDER BY Task_ID DESC";
$task_result = $conn->query($task_sql);

// 5. Resources
$resource_sql = "SELECT Resource_ID, Title, Type, Link, Student_ID FROM resource ORDER BY Resource_ID DESC";
$resource_result = $conn->query($resource_sql);

// 6. Meetings
$meeting_sql = "SELECT Meeting_ID, Date, Time, Location, Link FROM meeting ORDER BY Date DESC, Time DESC";
$meeting_result = $conn->query($meeting_sql);

$page_title = "Central System Overview — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Central System Overview</h1>
                <p class="page-subtitle">Consolidated view of all database records, collaborative teams, and milestone activity</p>
            </div>
            <div class="page-actions">
                <a href="admin_dashboard.php" class="btn btn-outline">&larr; Admin Dashboard</a>
            </div>
        </div>

        <!-- Quick Summary Stats Grid -->
        <div class="grid-3" style="margin-bottom: 2rem;">
            <div class="stat-card">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    </svg>
                </div>
                <div class="stat-body">
                    <div class="stat-value"><?php echo $user_result ? $user_result->num_rows : 0; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon accent">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                    </svg>
                </div>
                <div class="stat-body">
                    <div class="stat-value"><?php echo $project_result ? $project_result->num_rows : 0; ?></div>
                    <div class="stat-label">Total Projects</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon success">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    </svg>
                </div>
                <div class="stat-body">
                    <div class="stat-value"><?php echo $team_result ? $team_result->num_rows : 0; ?></div>
                    <div class="stat-label">Total Teams</div>
                </div>
            </div>
        </div>

        <!-- 1. Registered Users -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h2 class="card-title">1. Registered Users (<?php echo $user_result ? $user_result->num_rows : 0; ?>)</h2>
                <a href="manage_users.php" class="btn btn-sm btn-outline">Manage Users</a>
            </div>
            <div class="card-body">
                <?php if ($user_result && $user_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Email</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $user_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($row['User_ID']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['First_Name'] . ' ' . $row['Last_Name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['Department']); ?></td>
                                        <td style="color: var(--text-muted);"><?php echo htmlspecialchars($row['Email']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-muted);">No users recorded.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Research Projects -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h2 class="card-title">2. Research Projects (<?php echo $project_result ? $project_result->num_rows : 0; ?>)</h2>
                <a href="view_all_projects.php" class="btn btn-sm btn-outline">View Registry</a>
            </div>
            <div class="card-body">
                <?php if ($project_result && $project_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Project Title</th>
                                    <th>Lead Student ID</th>
                                    <th>Domain</th>
                                    <th>Status</th>
                                    <th>Creation Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $project_result->fetch_assoc()): ?>
                                    <?php 
                                    $s_class = 'badge-pending';
                                    if (strcasecmp($row['Status'], 'Active') === 0 || strcasecmp($row['Status'], 'Approved') === 0) $s_class = 'badge-active';
                                    elseif (strcasecmp($row['Status'], 'Completed') === 0) $s_class = 'badge-completed';
                                    ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($row['Project_ID']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['Title']); ?></strong></td>
                                        <td>ID: <?php echo htmlspecialchars($row['Student_ID']); ?></td>
                                        <td><span class="badge badge-low"><?php echo htmlspecialchars($row['Domain']); ?></span></td>
                                        <td><span class="badge <?php echo $s_class; ?>"><?php echo htmlspecialchars($row['Status']); ?></span></td>
                                        <td><?php echo htmlspecialchars($row['Creation_Date']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-muted);">No projects recorded.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- 3. Teams -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h2 class="card-title">3. Research Teams (<?php echo $team_result ? $team_result->num_rows : 0; ?>)</h2>
            </div>
            <div class="card-body">
                <?php if ($team_result && $team_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Team ID</th>
                                    <th>Team Name</th>
                                    <th>Project ID</th>
                                    <th>Supervisor ID</th>
                                    <th>Creation Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $team_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($row['Team_ID']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['Team_Name']); ?></strong></td>
                                        <td>#<?php echo htmlspecialchars($row['Project_ID']); ?></td>
                                        <td>Faculty ID: <?php echo htmlspecialchars($row['Faculty_ID']); ?></td>
                                        <td><?php echo htmlspecialchars($row['Creation_Date']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-muted);">No teams recorded.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- 4. Tasks -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h2 class="card-title">4. Tasks & Milestones (<?php echo $task_result ? $task_result->num_rows : 0; ?>)</h2>
            </div>
            <div class="card-body">
                <?php if ($task_result && $task_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Task ID</th>
                                    <th>Title</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Assigned Student</th>
                                    <th>Deadline</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $task_result->fetch_assoc()): ?>
                                    <?php 
                                    $p_class = 'badge-low';
                                    if (strcasecmp($row['Priority'], 'High') === 0) $p_class = 'badge-high';
                                    elseif (strcasecmp($row['Priority'], 'Medium') === 0) $p_class = 'badge-medium';

                                    $s_class = 'badge-pending';
                                    if (strcasecmp($row['Status'], 'Completed') === 0) $s_class = 'badge-completed';
                                    elseif (strcasecmp($row['Status'], 'In Progress') === 0) $s_class = 'badge-active';
                                    ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($row['Task_ID']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['Title']); ?></strong></td>
                                        <td><span class="badge <?php echo $p_class; ?>"><?php echo htmlspecialchars($row['Priority']); ?></span></td>
                                        <td><span class="badge <?php echo $s_class; ?>"><?php echo htmlspecialchars($row['Status']); ?></span></td>
                                        <td>Student ID: <?php echo htmlspecialchars($row['Student_ID']); ?></td>
                                        <td><?php echo htmlspecialchars($row['Deadline']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-muted);">No tasks recorded.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- 5. Resources -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h2 class="card-title">5. Research Resources (<?php echo $resource_result ? $resource_result->num_rows : 0; ?>)</h2>
            </div>
            <div class="card-body">
                <?php if ($resource_result && $resource_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Resource ID</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Student ID</th>
                                    <th>Link</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $resource_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($row['Resource_ID']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['Title']); ?></strong></td>
                                        <td><span class="badge badge-low"><?php echo htmlspecialchars($row['Type']); ?></span></td>
                                        <td>Student ID: <?php echo htmlspecialchars($row['Student_ID']); ?></td>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($row['Link']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline">
                                                Open Link &nearr;
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-muted);">No resources recorded.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- 6. Meetings -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">6. Scheduled Meetings (<?php echo $meeting_result ? $meeting_result->num_rows : 0; ?>)</h2>
                <a href="admin_view_meetings.php" class="btn btn-sm btn-outline">All Meetings</a>
            </div>
            <div class="card-body">
                <?php if ($meeting_result && $meeting_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Meeting ID</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Location</th>
                                    <th>Video Link</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $meeting_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($row['Meeting_ID']); ?></td>
                                        <td>&#128197; <?php echo htmlspecialchars($row['Date']); ?></td>
                                        <td style="font-weight: 600; color: var(--primary);">&#128347; <?php echo htmlspecialchars($row['Time']); ?></td>
                                        <td><?php echo htmlspecialchars(!empty($row['Location']) ? $row['Location'] : 'Online'); ?></td>
                                        <td>
                                            <?php if (!empty($row['Link'])): ?>
                                                <a href="<?php echo htmlspecialchars($row['Link']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline">
                                                    Join &nearr;
                                                </a>
                                            <?php else: ?>
                                                &mdash;
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-muted);">No meetings recorded.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>