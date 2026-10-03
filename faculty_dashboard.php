<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "Faculty") {
    header("Location: login.php");
    exit();
}

$faculty_id = (int)$_SESSION['user_id'];
$first_name = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : 'Faculty';
$last_name = isset($_SESSION['last_name']) ? $_SESSION['last_name'] : '';
$department = isset($_SESSION['department']) ? $_SESSION['department'] : 'Academic Department';
$today = date('Y-m-d');

// 1. Supervised Projects Count
$project_count = 0;
$stmt_proj = $conn->prepare("SELECT COUNT(DISTINCT p.Project_ID) AS total FROM project p JOIN team t ON p.Project_ID = t.Project_ID WHERE t.Faculty_ID = ?");
if ($stmt_proj) {
    $stmt_proj->bind_param("i", $faculty_id);
    $stmt_proj->execute();
    $res = $stmt_proj->get_result()->fetch_assoc();
    $project_count = $res['total'];
    $stmt_proj->close();
}

// 2. Teams Supervised Count
$team_count = 0;
$stmt_team = $conn->prepare("SELECT COUNT(*) AS total FROM team WHERE Faculty_ID = ?");
if ($stmt_team) {
    $stmt_team->bind_param("i", $faculty_id);
    $stmt_team->execute();
    $res = $stmt_team->get_result()->fetch_assoc();
    $team_count = $res['total'];
    $stmt_team->close();
}

// 3. Upcoming Consultations Count
$meeting_count = 0;
$stmt_meet = $conn->prepare("SELECT COUNT(DISTINCT m.Meeting_ID) AS total 
                             FROM attends a 
                             JOIN meeting m ON a.Meeting_ID = m.Meeting_ID 
                             WHERE a.Faculty_ID = ? AND m.Date >= ?");
if ($stmt_meet) {
    $stmt_meet->bind_param("is", $faculty_id, $today);
    $stmt_meet->execute();
    $res = $stmt_meet->get_result()->fetch_assoc();
    $meeting_count = $res['total'];
    $stmt_meet->close();
}

// 4. Pending Reviews (Student Progress in supervised teams)
$pending_reviews_count = 0;
$stmt_pr_count = $conn->prepare("SELECT COUNT(DISTINCT p.User_ID, p.Week_No) AS total 
                                 FROM progress p 
                                 JOIN joins j ON p.User_ID = j.User_ID 
                                 JOIN team t ON j.Team_ID = t.Team_ID 
                                 WHERE t.Faculty_ID = ?");
if ($stmt_pr_count) {
    $stmt_pr_count->bind_param("i", $faculty_id);
    $stmt_pr_count->execute();
    $res = $stmt_pr_count->get_result()->fetch_assoc();
    $pending_reviews_count = $res['total'];
    $stmt_pr_count->close();
}

// Recent Supervised Projects
$recent_projects = [];
$stmt_rp = $conn->prepare("SELECT p.Project_ID, p.Title, p.Domain, p.Status, t.Team_Name 
                           FROM project p 
                           JOIN team t ON p.Project_ID = t.Project_ID 
                           WHERE t.Faculty_ID = ? 
                           ORDER BY p.Project_ID DESC LIMIT 5");
if ($stmt_rp) {
    $stmt_rp->bind_param("i", $faculty_id);
    $stmt_rp->execute();
    $recent_projects = $stmt_rp->get_result();
    $stmt_rp->close();
}

// Recent Student Progress Submissions from Supervised Teams
$recent_progress = [];
$stmt_prog = $conn->prepare("SELECT p.Week_No, p.User_ID, p.Submission_Date, p.Progress_Update, 
                                    u.First_Name, u.Last_Name, tm.Team_Name, prj.Title AS Project_Title 
                             FROM progress p 
                             JOIN user u ON p.User_ID = u.User_ID 
                             JOIN joins j ON p.User_ID = j.User_ID 
                             JOIN team tm ON j.Team_ID = tm.Team_ID 
                             JOIN project prj ON tm.Project_ID = prj.Project_ID 
                             WHERE tm.Faculty_ID = ? 
                             ORDER BY p.Submission_Date DESC LIMIT 4");
if ($stmt_prog) {
    $stmt_prog->bind_param("i", $faculty_id);
    $stmt_prog->execute();
    $recent_progress = $stmt_prog->get_result();
    $stmt_prog->close();
}

// Upcoming Supervision Meetings
$upcoming_meetings = [];
$stmt_um = $conn->prepare("SELECT DISTINCT m.Meeting_ID, m.Date, m.Time, m.Location, m.Link 
                           FROM attends a 
                           JOIN meeting m ON a.Meeting_ID = m.Meeting_ID 
                           WHERE a.Faculty_ID = ? 
                           ORDER BY m.Date DESC, m.Time DESC LIMIT 3");
if ($stmt_um) {
    $stmt_um->bind_param("i", $faculty_id);
    $stmt_um->execute();
    $upcoming_meetings = $stmt_um->get_result();
    $stmt_um->close();
}

$page_title = "Faculty Dashboard — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <!-- Welcome Banner -->
        <div class="card" style="background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%); color: #ffffff; border: none; margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <span class="badge" style="background: rgba(255,255,255,0.15); color: #ffffff; border: 1px solid rgba(255,255,255,0.2); margin-bottom: 0.5rem;">
                        Faculty Supervisor
                    </span>
                    <h1 style="font-size: 1.75rem; font-weight: 700; color: #ffffff; margin-bottom: 0.35rem;">
                        Welcome back, Dr. <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>
                    </h1>
                    <p style="color: #cbd5e1; font-size: 0.95rem;">
                        <?php echo htmlspecialchars($department); ?> &bull; Faculty ID: <?php echo htmlspecialchars($faculty_id); ?>
                    </p>
                </div>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <a href="schedule_meeting.php" class="btn btn-sm" style="background: #ffffff; color: #1e3a8a; font-weight: 600;">
                        + Schedule Meeting
                    </a>
                    <a href="give_feedback.php" class="btn btn-sm" style="background: rgba(255,255,255,0.15); color: #ffffff; border: 1px solid rgba(255,255,255,0.3);">
                        Provide Feedback
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
                    <div class="stat-label">Supervised Projects</div>
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
                    <div class="stat-label">Supervised Teams</div>
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

            <div class="stat-card">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </div>
                <div class="stat-body">
                    <div class="stat-value"><?php echo $pending_reviews_count; ?></div>
                    <div class="stat-label">Student Submissions</div>
                </div>
            </div>
        </div>

        <!-- Supervised Projects Table with Workspace Links -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Projects Under Your Supervision</h2>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.2rem;">Direct access to project workspaces, milestone tasks, and team rosters</p>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <a href="join_project.php" class="btn btn-sm btn-outline">+ Supervise Existing</a>
                    <a href="my_projects.php" class="btn btn-sm btn-primary">All Supervised</a>
                </div>
            </div>
            <div class="card-body">
                <?php if ($recent_projects && $recent_projects->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Project Title</th>
                                    <th>Assigned Team</th>
                                    <th>Domain</th>
                                    <th>Status</th>
                                    <th>Workspace Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($p = $recent_projects->fetch_assoc()): ?>
                                    <?php 
                                    $s_class = 'badge-pending';
                                    if (strcasecmp($p['Status'], 'Active') === 0 || strcasecmp($p['Status'], 'Approved') === 0) $s_class = 'badge-active';
                                    elseif (strcasecmp($p['Status'], 'Completed') === 0) $s_class = 'badge-completed';
                                    ?>
                                    <tr>
                                        <td style="max-width: 260px;">
                                            <strong><?php echo htmlspecialchars($p['Title']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-low"><?php echo htmlspecialchars($p['Team_Name']); ?></span>
                                        </td>
                                        <td><span class="badge badge-low">&#128394; <?php echo htmlspecialchars($p['Domain']); ?></span></td>
                                        <td><span class="badge <?php echo $s_class; ?>"><?php echo htmlspecialchars($p['Status']); ?></span></td>
                                        <td>
                                            <div class="table-actions">
                                                <a href="view_project.php?id=<?php echo $p['Project_ID']; ?>" class="btn btn-sm btn-primary">
                                                    Workspace &rarr;
                                                </a>
                                                <a href="schedule_meeting.php" class="btn btn-sm btn-outline">
                                                    Meet
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state" style="padding: 2.5rem 1rem;">
                        <div class="empty-icon">&#128218;</div>
                        <div class="empty-title">No Projects Supervised Yet</div>
                        <div class="empty-description">Select an existing student research project to provide academic guidance and supervision.</div>
                        <a href="join_project.php" class="btn btn-sm btn-primary">Supervise a Project</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pending Work: Student Progress & Upcoming Meetings -->
        <div class="grid-2" style="margin-bottom: 2rem;">
            <!-- Recent Student Progress Submissions -->
            <div class="card" style="margin-bottom: 0;">
                <div class="card-header">
                    <h2 class="card-title">Recent Student Progress</h2>
                    <a href="give_feedback.php" class="btn btn-sm btn-outline">Provide Feedback</a>
                </div>
                <div class="card-body">
                    <?php if ($recent_progress && $recent_progress->num_rows > 0): ?>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <?php while ($pr = $recent_progress->fetch_assoc()): ?>
                                <div style="padding: 0.85rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-md); background: var(--bg-card);">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem; flex-wrap: wrap; gap: 0.25rem;">
                                        <div>
                                            <span class="badge badge-active">Week <?php echo htmlspecialchars($pr['Week_No']); ?></span>
                                            <strong style="color: var(--text-main); font-size: 0.9rem; margin-left: 0.25rem;">
                                                <?php echo htmlspecialchars($pr['First_Name'] . ' ' . $pr['Last_Name']); ?>
                                            </strong>
                                            <span style="font-size: 0.775rem; color: var(--text-muted);">(<?php echo htmlspecialchars($pr['Team_Name']); ?>)</span>
                                        </div>
                                        <span style="font-size: 0.775rem; color: var(--text-muted);">&#128197; <?php echo htmlspecialchars($pr['Submission_Date']); ?></span>
                                    </div>
                                    <p style="font-size: 0.85rem; color: var(--text-body); line-height: 1.45; margin-bottom: 0.5rem;">
                                        <?php echo htmlspecialchars(mb_strimwidth($pr['Progress_Update'], 0, 110, "...")); ?>
                                    </p>
                                    <div style="text-align: right;">
                                        <a href="give_feedback.php" class="btn btn-sm btn-outline" style="font-size: 0.775rem; padding: 0.25rem 0.6rem;">
                                            Evaluate & Feedback &rarr;
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="padding: 1.5rem 1rem;">
                            <div class="empty-icon">&#128221;</div>
                            <div class="empty-title">No Recent Submissions</div>
                            <div class="empty-description">Student progress reports from your supervised teams will appear here.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Upcoming Consultation Meetings -->
            <div class="card" style="margin-bottom: 0;">
                <div class="card-header">
                    <h2 class="card-title">Supervision Sessions</h2>
                    <a href="schedule_meeting.php" class="btn btn-sm btn-primary">+ Schedule</a>
                </div>
                <div class="card-body">
                    <?php if ($upcoming_meetings && $upcoming_meetings->num_rows > 0): ?>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <?php while ($m = $upcoming_meetings->fetch_assoc()): ?>
                                <?php $is_upcoming = ($m['Date'] >= $today); ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.85rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-md); background: var(--bg-card);">
                                    <div>
                                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.2rem;">
                                            <strong style="color: var(--text-main); font-size: 0.9rem;">Session #<?php echo $m['Meeting_ID']; ?></strong>
                                            <span class="badge <?php echo $is_upcoming ? 'badge-active' : 'badge-low'; ?>">
                                                <?php echo $is_upcoming ? 'Upcoming' : 'Concluded'; ?>
                                            </span>
                                        </div>
                                        <div style="font-size: 0.8rem; color: var(--text-muted);">
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
                            <div class="empty-description">Schedule check-ins and lab review consultations with student teams.</div>
                            <a href="schedule_meeting.php" class="btn btn-sm btn-primary" style="margin-top: 0.5rem;">+ Schedule First Meeting</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Supervisory Modules Grid -->
        <div class="page-header" style="margin-top: 1rem;">
            <div>
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Supervisory Modules</h2>
                <p class="page-subtitle">Academic collaboration and milestone management controls</p>
            </div>
        </div>

        <div class="grid-3" style="margin-bottom: 2rem;">
            <div class="card" style="margin-bottom: 0;">
                <h3 style="font-size: 1.05rem; font-weight: 600; color: var(--primary); margin-bottom: 0.35rem;">
                    Research Projects
                </h3>
                <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem;">
                    Supervise undergraduate and graduate collaborative research projects.
                </p>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <a href="my_projects.php" class="btn btn-sm btn-primary">My Projects</a>
                    <a href="join_project.php" class="btn btn-sm btn-secondary">Supervise New</a>
                </div>
            </div>

            <div class="card" style="margin-bottom: 0;">
                <h3 style="font-size: 1.05rem; font-weight: 600; color: var(--primary); margin-bottom: 0.35rem;">
                    Team Management
                </h3>
                <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem;">
                    Review team compositions, appoint leaders, and form collaborative teams.
                </p>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <a href="manage_teams.php" class="btn btn-sm btn-primary">Manage Teams</a>
                    <a href="create_team.php" class="btn btn-sm btn-secondary">+ Create Team</a>
                </div>
            </div>

            <div class="card" style="margin-bottom: 0;">
                <h3 style="font-size: 1.05rem; font-weight: 600; color: var(--primary); margin-bottom: 0.35rem;">
                    Supervision Consultations
                </h3>
                <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem;">
                    Schedule consultations, lab reviews, and post constructive evaluations.
                </p>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <a href="schedule_meeting.php" class="btn btn-sm btn-primary">Schedule Meeting</a>
                    <a href="give_feedback.php" class="btn btn-sm btn-secondary">Give Feedback</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>