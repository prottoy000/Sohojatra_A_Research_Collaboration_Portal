<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "Student") {
    header("Location: login.php");
    exit();
}

$student_id = (int)$_SESSION['user_id'];
$first_name = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : 'Student';
$last_name = isset($_SESSION['last_name']) ? $_SESSION['last_name'] : '';
$department = isset($_SESSION['department']) ? $_SESSION['department'] : 'Academic Department';
$today = date('Y-m-d');

// 1. Projects Count (created or joined as team member)
$project_count = 0;
$stmt_proj = $conn->prepare("SELECT COUNT(DISTINCT p.Project_ID) AS total 
                             FROM project p 
                             LEFT JOIN team t ON p.Project_ID = t.Project_ID 
                             LEFT JOIN joins j ON t.Team_ID = j.Team_ID 
                             WHERE p.Student_ID = ? OR j.User_ID = ?");
if ($stmt_proj) {
    $stmt_proj->bind_param("ii", $student_id, $student_id);
    $stmt_proj->execute();
    $res = $stmt_proj->get_result()->fetch_assoc();
    $project_count = $res['total'];
    $stmt_proj->close();
}

// 2. Active Teams Count
$team_count = 0;
$stmt_team = $conn->prepare("SELECT COUNT(DISTINCT Team_ID) AS total FROM joins WHERE User_ID = ?");
if ($stmt_team) {
    $stmt_team->bind_param("i", $student_id);
    $stmt_team->execute();
    $res = $stmt_team->get_result()->fetch_assoc();
    $team_count = $res['total'];
    $stmt_team->close();
}

// 3. Pending Tasks Count
$pending_tasks = 0;
$stmt_tasks = $conn->prepare("SELECT COUNT(*) AS total FROM tasks WHERE Student_ID = ? AND Status != 'Completed'");
if ($stmt_tasks) {
    $stmt_tasks->bind_param("i", $student_id);
    $stmt_tasks->execute();
    $res = $stmt_tasks->get_result()->fetch_assoc();
    $pending_tasks = $res['total'];
    $stmt_tasks->close();
}

// 4. Upcoming Meetings Count (Date >= today)
$meeting_count = 0;
$stmt_meet = $conn->prepare("SELECT COUNT(DISTINCT a.Meeting_ID) AS total 
                             FROM attends a 
                             JOIN meeting m ON a.Meeting_ID = m.Meeting_ID 
                             WHERE a.Student_ID = ? AND m.Date >= ?");
if ($stmt_meet) {
    $stmt_meet->bind_param("is", $student_id, $today);
    $stmt_meet->execute();
    $res = $stmt_meet->get_result()->fetch_assoc();
    $meeting_count = $res['total'];
    $stmt_meet->close();
}

// 5. Current Projects
$current_projects = [];
$stmt_cp = $conn->prepare("SELECT DISTINCT p.Project_ID, p.Title, p.Domain, p.Status, p.Description, 
                                  t.Team_Name, u.First_Name AS Fac_First, u.Last_Name AS Fac_Last 
                           FROM project p 
                           LEFT JOIN team t ON p.Project_ID = t.Project_ID 
                           LEFT JOIN joins j ON t.Team_ID = j.Team_ID 
                           LEFT JOIN faculty f ON t.Faculty_ID = f.Faculty_ID 
                           LEFT JOIN user u ON f.Faculty_ID = u.User_ID 
                           WHERE p.Student_ID = ? OR j.User_ID = ? 
                           ORDER BY p.Project_ID DESC LIMIT 3");
if ($stmt_cp) {
    $stmt_cp->bind_param("ii", $student_id, $student_id);
    $stmt_cp->execute();
    $current_projects = $stmt_cp->get_result();
    $stmt_cp->close();
}

// 6. Recent Tasks
$recent_tasks = [];
$stmt_rt = $conn->prepare("SELECT Task_ID, Title, Priority, Deadline, Status FROM tasks WHERE Student_ID = ? ORDER BY (Status = 'Completed') ASC, Deadline ASC LIMIT 5");
if ($stmt_rt) {
    $stmt_rt->bind_param("i", $student_id);
    $stmt_rt->execute();
    $recent_tasks = $stmt_rt->get_result();
    $stmt_rt->close();
}

// 7. Upcoming/Recent Meetings
$recent_meetings = [];
$stmt_rm = $conn->prepare("SELECT m.Meeting_ID, m.Date, m.Time, m.Location, m.Link, u.First_Name, u.Last_Name 
                           FROM attends a 
                           JOIN meeting m ON a.Meeting_ID = m.Meeting_ID 
                           LEFT JOIN user u ON a.Faculty_ID = u.User_ID 
                           WHERE a.Student_ID = ? 
                           ORDER BY m.Date DESC, m.Time DESC LIMIT 3");
if ($stmt_rm) {
    $stmt_rm->bind_param("i", $student_id);
    $stmt_rm->execute();
    $recent_meetings = $stmt_rm->get_result();
    $stmt_rm->close();
}

$page_title = "Student Dashboard — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <!-- Welcome Banner -->
        <div class="card" style="background: linear-gradient(135deg, #1e3a8a 0%, #1e293b 100%); color: #ffffff; border: none; margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <span class="badge" style="background: rgba(255,255,255,0.15); color: #ffffff; border: 1px solid rgba(255,255,255,0.2); margin-bottom: 0.5rem;">
                        Student Researcher
                    </span>
                    <h1 style="font-size: 1.75rem; font-weight: 700; color: #ffffff; margin-bottom: 0.35rem;">
                        Welcome back, <?php echo htmlspecialchars($first_name); ?>!
                    </h1>
                    <p style="color: #cbd5e1; font-size: 0.95rem;">
                        <?php echo htmlspecialchars($department); ?> &bull; Student ID: <?php echo htmlspecialchars($student_id); ?>
                    </p>
                </div>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <a href="create_project.php" class="btn btn-sm" style="background: #ffffff; color: #1e3a8a; font-weight: 600;">
                        + Propose Project
                    </a>
                    <a href="submit_progress.php" class="btn btn-sm" style="background: rgba(255,255,255,0.15); color: #ffffff; border: 1px solid rgba(255,255,255,0.3);">
                        Submit Weekly Progress
                    </a>
                </div>
            </div>
        </div>

        <!-- 4 Real Statistics Cards -->
        <div class="grid-4" style="margin-bottom: 2rem;">
            <div class="stat-card">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                    </svg>
                </div>
                <div class="stat-body">
                    <div class="stat-value"><?php echo $project_count; ?></div>
                    <div class="stat-label">My Projects</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div class="stat-body">
                    <div class="stat-value"><?php echo $team_count; ?></div>
                    <div class="stat-label">Active Teams</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 11 12 14 22 4"></polyline>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                    </svg>
                </div>
                <div class="stat-body">
                    <div class="stat-value"><?php echo $pending_tasks; ?></div>
                    <div class="stat-label">Pending Tasks</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <div class="stat-body">
                    <div class="stat-value"><?php echo $meeting_count; ?></div>
                    <div class="stat-label">Upcoming Meetings</div>
                </div>
            </div>
        </div>

        <!-- Current Projects Section -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <div>
                    <h2 class="card-title">My Current Research Projects</h2>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.2rem;">Projects you lead or collaborate on as a team member</p>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <a href="view_all_projects.php" class="btn btn-sm btn-outline">Explore Directory</a>
                    <a href="create_project.php" class="btn btn-sm btn-primary">+ Propose Project</a>
                </div>
            </div>
            <div class="card-body">
                <?php if ($current_projects && $current_projects->num_rows > 0): ?>
                    <div class="grid-3" style="margin-bottom: 0;">
                        <?php while ($proj = $current_projects->fetch_assoc()): ?>
                            <?php 
                            $status_class = 'badge-pending';
                            if (strcasecmp($proj['Status'], 'Active') === 0 || strcasecmp($proj['Status'], 'Approved') === 0) {
                                $status_class = 'badge-active';
                            } elseif (strcasecmp($proj['Status'], 'Completed') === 0) {
                                $status_class = 'badge-completed';
                            }
                            ?>
                            <div style="border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.15rem; background: var(--bg-card); display: flex; flex-direction: column; justify-content: space-between;">
                                <div>
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem; margin-bottom: 0.5rem;">
                                        <span class="badge badge-low">&#128394; <?php echo htmlspecialchars($proj['Domain']); ?></span>
                                        <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($proj['Status']); ?></span>
                                    </div>
                                    <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.4rem; line-height: 1.35;">
                                        <?php echo htmlspecialchars($proj['Title']); ?>
                                    </h3>
                                    <div style="font-size: 0.825rem; color: var(--text-muted); margin-bottom: 0.75rem;">
                                        Supervisor: 
                                        <strong style="color: var(--text-main);">
                                            <?php echo !empty($proj['Fac_First']) ? 'Dr. ' . htmlspecialchars($proj['Fac_First'] . ' ' . $proj['Fac_Last']) : 'Pending Assignment'; ?>
                                        </strong>
                                    </div>
                                </div>
                                <div>
                                    <a href="view_project.php?id=<?php echo $proj['Project_ID']; ?>" class="btn btn-sm btn-primary" style="width: 100%; text-align: center;">
                                        Open Workspace &rarr;
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state" style="padding: 1.5rem 1rem;">
                        <div class="empty-icon">&#128214;</div>
                        <div class="empty-title">No Projects Registered Yet</div>
                        <div class="empty-description">Propose a new undergraduate research topic or join an existing research team to get started.</div>
                        <div style="display: flex; gap: 0.5rem; justify-content: center; margin-top: 0.75rem;">
                            <a href="create_project.php" class="btn btn-sm btn-primary">+ Propose Project</a>
                            <a href="view_all_projects.php" class="btn btn-sm btn-secondary">Browse Projects</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Tasks & Meetings Side-by-Side -->
        <div class="grid-2" style="margin-bottom: 2rem;">
            <!-- Recent Tasks Card -->
            <div class="card" style="margin-bottom: 0;">
                <div class="card-header">
                    <h2 class="card-title">Recent Tasks & Milestones</h2>
                    <a href="update_task.php" class="btn btn-sm btn-outline">Task Board</a>
                </div>
                <div class="card-body">
                    <?php if ($recent_tasks && $recent_tasks->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Task Objective</th>
                                        <th>Deadline</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($t = $recent_tasks->fetch_assoc()): ?>
                                        <?php 
                                        $is_done = (strcasecmp($t['Status'], 'Completed') === 0);
                                        $s_class = $is_done ? 'badge-completed' : ((strcasecmp($t['Status'], 'In Progress') === 0) ? 'badge-active' : 'badge-pending');
                                        
                                        $due_tag = '';
                                        if ($is_done) {
                                            $due_tag = '<span class="badge badge-completed">Done</span>';
                                        } elseif ($t['Deadline'] < $today) {
                                            $due_tag = '<span class="badge badge-danger">Overdue</span>';
                                        } elseif ($t['Deadline'] === $today) {
                                            $due_tag = '<span class="badge badge-warning">Today</span>';
                                        }
                                        ?>
                                        <tr>
                                            <td style="max-width: 220px; <?php echo $is_done ? 'text-decoration: line-through; opacity: 0.7;' : 'font-weight: 600; color: var(--text-main);'; ?>">
                                                <?php echo htmlspecialchars($t['Title']); ?>
                                            </td>
                                            <td style="white-space: nowrap; font-size: 0.825rem; color: var(--text-muted);">
                                                <?php echo htmlspecialchars($t['Deadline']); ?> <?php echo $due_tag; ?>
                                            </td>
                                            <td><span class="badge <?php echo $s_class; ?>"><?php echo htmlspecialchars($t['Status']); ?></span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="padding: 1.5rem 1rem;">
                            <div class="empty-icon">&#10003;</div>
                            <div class="empty-title">No Tasks Assigned Yet</div>
                            <div class="empty-description">Schedule milestones and manage research deliverables.</div>
                            <a href="create_task.php" class="btn btn-sm btn-primary">+ Create Task</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Upcoming Meetings Card -->
            <div class="card" style="margin-bottom: 0;">
                <div class="card-header">
                    <h2 class="card-title">Supervision Meetings</h2>
                    <a href="view_meetings.php" class="btn btn-sm btn-outline">All Meetings</a>
                </div>
                <div class="card-body">
                    <?php if ($recent_meetings && $recent_meetings->num_rows > 0): ?>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <?php while ($m = $recent_meetings->fetch_assoc()): ?>
                                <?php $is_upcoming = ($m['Date'] >= $today); ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.85rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-md); background: var(--bg-card);">
                                    <div>
                                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.2rem;">
                                            <span style="font-weight: 600; color: var(--text-main); font-size: 0.95rem;">
                                                Session #<?php echo htmlspecialchars($m['Meeting_ID']); ?>
                                            </span>
                                            <span class="badge <?php echo $is_upcoming ? 'badge-active' : 'badge-low'; ?>">
                                                <?php echo $is_upcoming ? 'Upcoming' : 'Concluded'; ?>
                                            </span>
                                        </div>
                                        <div style="font-size: 0.825rem; color: var(--text-muted);">
                                            &#128197; <?php echo htmlspecialchars($m['Date']); ?> at <?php echo htmlspecialchars($m['Time']); ?> 
                                            &bull; &#128205; <?php echo htmlspecialchars(!empty($m['Location']) ? $m['Location'] : 'Online'); ?>
                                        </div>
                                    </div>
                                    <div>
                                        <a href="<?php echo htmlspecialchars($m['Link']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm <?php echo $is_upcoming ? 'btn-primary' : 'btn-outline'; ?>">
                                            Join Link
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="padding: 1.5rem 1rem;">
                            <div class="empty-icon">&#128197;</div>
                            <div class="empty-title">No Meetings Scheduled</div>
                            <div class="empty-description">Your faculty supervisor will schedule regular research check-ins here.</div>
                            <a href="view_meetings.php" class="btn btn-sm btn-outline">Check Meeting Room</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions Grid -->
        <div class="page-header" style="margin-top: 1rem;">
            <div>
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Quick Actions</h2>
                <p class="page-subtitle">Common operations and shortcuts for your active research workflow</p>
            </div>
        </div>

        <div class="grid-4" style="margin-bottom: 2rem;">
            <div class="card" style="margin-bottom: 0; text-align: center; padding: 1.25rem;">
                <h3 style="font-size: 1rem; font-weight: 600; color: var(--primary); margin-bottom: 0.35rem;">
                    Propose Project
                </h3>
                <p style="font-size: 0.825rem; color: var(--text-muted); margin-bottom: 1rem;">
                    Register a new research problem and abstract.
                </p>
                <a href="create_project.php" class="btn btn-sm btn-primary" style="width: 100%;">+ Propose Project</a>
            </div>

            <div class="card" style="margin-bottom: 0; text-align: center; padding: 1.25rem;">
                <h3 style="font-size: 1rem; font-weight: 600; color: var(--primary); margin-bottom: 0.35rem;">
                    Browse Registry
                </h3>
                <p style="font-size: 0.825rem; color: var(--text-muted); margin-bottom: 1rem;">
                    Search topics across all departments.
                </p>
                <a href="view_all_projects.php" class="btn btn-sm btn-secondary" style="width: 100%;">Browse Projects</a>
            </div>

            <div class="card" style="margin-bottom: 0; text-align: center; padding: 1.25rem;">
                <h3 style="font-size: 1rem; font-weight: 600; color: var(--primary); margin-bottom: 0.35rem;">
                    Team Members
                </h3>
                <p style="font-size: 0.825rem; color: var(--text-muted); margin-bottom: 1rem;">
                    Manage roster and handle team join requests.
                </p>
                <a href="view_team.php" class="btn btn-sm btn-secondary" style="width: 100%;">View Teams</a>
            </div>

            <div class="card" style="margin-bottom: 0; text-align: center; padding: 1.25rem;">
                <h3 style="font-size: 1rem; font-weight: 600; color: var(--primary); margin-bottom: 0.35rem;">
                    Weekly Progress
                </h3>
                <p style="font-size: 0.825rem; color: var(--text-muted); margin-bottom: 1rem;">
                    Submit experiments, achievements, and notes.
                </p>
                <a href="submit_progress.php" class="btn btn-sm btn-primary" style="width: 100%;">&#9998; Submit Progress</a>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
