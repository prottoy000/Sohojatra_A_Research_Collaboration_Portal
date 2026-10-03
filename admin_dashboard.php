<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "Admin") {
    header("Location: login.php");
    exit();
}

$admin_id = (int)$_SESSION['user_id'];
$first_name = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : 'Administrator';
$last_name = isset($_SESSION['last_name']) ? $_SESSION['last_name'] : '';

// 1. Total Registered Users
$total_users = 0;
$res = $conn->query("SELECT COUNT(*) AS total FROM User");
if ($res) {
    $row = $res->fetch_assoc();
    $total_users = $row['total'];
}

// 2. Total Projects
$total_projects = 0;
$res = $conn->query("SELECT COUNT(*) AS total FROM Project");
if ($res) {
    $row = $res->fetch_assoc();
    $total_projects = $row['total'];
}

// 3. Total Teams
$total_teams = 0;
$res = $conn->query("SELECT COUNT(*) AS total FROM Team");
if ($res) {
    $row = $res->fetch_assoc();
    $total_teams = $row['total'];
}

// 4. Total Meetings
$total_meetings = 0;
$res = $conn->query("SELECT COUNT(*) AS total FROM Meeting");
if ($res) {
    $row = $res->fetch_assoc();
    $total_meetings = $row['total'];
}

// Breakdown counts
$total_students = 0;
$res_s = $conn->query("SELECT COUNT(*) AS total FROM Student");
if ($res_s) {
    $total_students = $res_s->fetch_assoc()['total'];
}

$total_faculty = 0;
$res_f = $conn->query("SELECT COUNT(*) AS total FROM Faculty");
if ($res_f) {
    $total_faculty = $res_f->fetch_assoc()['total'];
}

// Recent Projects
$recent_projects = $conn->query("SELECT Project_ID, Title, Domain, Status, Creation_Date FROM Project ORDER BY Project_ID DESC LIMIT 5");

$page_title = "Admin Dashboard — Sohojatra Portal";
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
                        System Administrator
                    </span>
                    <h1 style="font-size: 1.75rem; font-weight: 700; color: #ffffff; margin-bottom: 0.35rem;">
                        System Administration Console
                    </h1>
                    <p style="color: #cbd5e1; font-size: 0.95rem;">
                        Welcome, <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?> &bull; System ID: <?php echo htmlspecialchars($admin_id); ?>
                    </p>
                </div>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <a href="admin_view_all.php" class="btn btn-sm" style="background: #ffffff; color: #1e3a8a; font-weight: 600;">
                        Open System Overview
                    </a>
                </div>
            </div>
        </div>

        <!-- Real Statistics Grid -->
        <div class="grid-4" style="margin-bottom: 2rem;">
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
                    <div class="stat-value"><?php echo $total_users; ?></div>
                    <div class="stat-label">Total Users</div>
                    <div style="font-size: 0.775rem; color: var(--text-muted); margin-top: 0.15rem;">
                        <?php echo $total_students; ?> Students &bull; <?php echo $total_faculty; ?> Faculty
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon accent">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                    </svg>
                </div>
                <div class="stat-body">
                    <div class="stat-value"><?php echo $total_projects; ?></div>
                    <div class="stat-label">Research Projects</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon success">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                    </svg>
                </div>
                <div class="stat-body">
                    <div class="stat-value"><?php echo $total_teams; ?></div>
                    <div class="stat-label">Active Teams</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon warning">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <div class="stat-body">
                    <div class="stat-value"><?php echo $total_meetings; ?></div>
                    <div class="stat-label">Scheduled Meetings</div>
                </div>
            </div>
        </div>

        <!-- Recent Projects Table -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h2 class="card-title">Recent Research Projects</h2>
                <a href="view_all_projects.php" class="btn btn-sm btn-outline">View All Projects</a>
            </div>
            <div class="card-body">
                <?php if ($recent_projects && $recent_projects->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Domain</th>
                                    <th>Status</th>
                                    <th>Created Date</th>
                                    <th>Actions</th>
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
                                        <td>#<?php echo htmlspecialchars($p['Project_ID']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($p['Title']); ?></strong></td>
                                        <td><span class="badge badge-low"><?php echo htmlspecialchars($p['Domain']); ?></span></td>
                                        <td><span class="badge <?php echo $s_class; ?>"><?php echo htmlspecialchars($p['Status']); ?></span></td>
                                        <td style="color: var(--text-muted); font-size: 0.85rem;"><?php echo htmlspecialchars($p['Creation_Date']); ?></td>
                                        <td>
                                            <a href="view_project.php?id=<?php echo $p['Project_ID']; ?>" class="btn btn-sm btn-outline">Workspace &rarr;</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state" style="padding: 2.5rem 1rem;">
                        <div class="empty-icon">&#128218;</div>
                        <div class="empty-title">No Projects Found</div>
                        <div class="empty-description">Student researchers have not submitted project proposals yet.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Admin Navigation Modules -->
        <div class="page-header" style="margin-top: 1rem;">
            <div>
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Administrative Operations</h2>
                <p class="page-subtitle">Central registry and portal governance controls</p>
            </div>
        </div>

        <div class="grid-4">
            <div class="card">
                <h3 style="font-size: 1.05rem; font-weight: 600; color: var(--primary); margin-bottom: 0.35rem;">
                    User Registry
                </h3>
                <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem;">
                    View registered students, faculty supervisors, and system accounts.
                </p>
                <a href="manage_users.php" class="btn btn-sm btn-primary">Manage Users</a>
            </div>

            <div class="card">
                <h3 style="font-size: 1.05rem; font-weight: 600; color: var(--primary); margin-bottom: 0.35rem;">
                    All Projects
                </h3>
                <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem;">
                    Inspect proposals, research domains, status, and project details.
                </p>
                <a href="view_all_projects.php" class="btn btn-sm btn-primary">View Projects</a>
            </div>

            <div class="card">
                <h3 style="font-size: 1.05rem; font-weight: 600; color: var(--primary); margin-bottom: 0.35rem;">
                    Meetings Registry
                </h3>
                <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem;">
                    Monitor supervision meetings and student attendance across teams.
                </p>
                <a href="admin_view_meetings.php" class="btn btn-sm btn-primary">View Meetings</a>
            </div>

            <div class="card">
                <h3 style="font-size: 1.05rem; font-weight: 600; color: var(--primary); margin-bottom: 0.35rem;">
                    System Overview
                </h3>
                <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem;">
                    Comprehensive database overview with all entities in one consolidated view.
                </p>
                <a href="admin_view_all.php" class="btn btn-sm btn-primary">Open Overview</a>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>