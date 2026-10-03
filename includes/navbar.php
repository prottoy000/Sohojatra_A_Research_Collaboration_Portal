<?php
if (!isset($_SESSION)) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$user_name = '';
$user_initial = 'U';

if (isset($_SESSION['first_name'])) {
    $user_name = $_SESSION['first_name'] . ' ' . (isset($_SESSION['last_name']) ? $_SESSION['last_name'] : '');
    $user_initial = strtoupper(substr($_SESSION['first_name'], 0, 1));
}

// Brand dashboard destination by role
$brand_href = 'login.php';
if ($role === 'Student') {
    $brand_href = 'student_dashboard.php';
} elseif ($role === 'Faculty') {
    $brand_href = 'faculty_dashboard.php';
} elseif ($role === 'Admin') {
    $brand_href = 'admin_dashboard.php';
}
?>
<nav class="navbar">
    <div class="container navbar-container">
        <a href="<?php echo htmlspecialchars($brand_href); ?>" class="navbar-brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
            </svg>
            <span>Sohojatra</span>
            <?php if (!empty($role)): ?>
                <span class="portal-tag"><?php echo htmlspecialchars($role); ?></span>
            <?php endif; ?>
        </a>

        <button class="mobile-toggle" aria-label="Toggle navigation" aria-expanded="false">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

        <?php if (!empty($role)): ?>
            <ul class="navbar-links">
                <?php if ($role === 'Student'): ?>
                    <li>
                        <a href="student_dashboard.php" class="nav-link <?php echo ($current_page === 'student_dashboard.php') ? 'active' : ''; ?>">
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="view_project.php" class="nav-link <?php echo in_array($current_page, ['view_project.php', 'create_project.php']) ? 'active' : ''; ?>">
                            My Project
                        </a>
                    </li>
                    <li>
                        <a href="view_team.php" class="nav-link <?php echo in_array($current_page, ['view_team.php', 'create_team.php', 'join_team.php', 'manage_teams.php', 'team_requests.php']) ? 'active' : ''; ?>">
                            Teams
                        </a>
                    </li>
                    <li>
                        <a href="update_task.php" class="nav-link <?php echo in_array($current_page, ['update_task.php', 'create_task.php']) ? 'active' : ''; ?>">
                            Tasks
                        </a>
                    </li>
                    <li>
                        <a href="view_resources.php" class="nav-link <?php echo in_array($current_page, ['view_resources.php', 'add_resource.php']) ? 'active' : ''; ?>">
                            Resources
                        </a>
                    </li>
                    <li>
                        <a href="view_progress.php" class="nav-link <?php echo in_array($current_page, ['view_progress.php', 'submit_progress.php']) ? 'active' : ''; ?>">
                            Progress
                        </a>
                    </li>
                    <li>
                        <a href="view_meetings.php" class="nav-link <?php echo ($current_page === 'view_meetings.php') ? 'active' : ''; ?>">
                            Meetings
                        </a>
                    </li>
                    <li>
                        <a href="view_feedback.php" class="nav-link <?php echo ($current_page === 'view_feedback.php') ? 'active' : ''; ?>">
                            Feedback
                        </a>
                    </li>

                <?php elseif ($role === 'Faculty'): ?>
                    <li>
                        <a href="faculty_dashboard.php" class="nav-link <?php echo ($current_page === 'faculty_dashboard.php') ? 'active' : ''; ?>">
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="my_projects.php" class="nav-link <?php echo in_array($current_page, ['my_projects.php', 'join_project.php']) ? 'active' : ''; ?>">
                            Projects
                        </a>
                    </li>
                    <li>
                        <a href="manage_teams.php" class="nav-link <?php echo in_array($current_page, ['manage_teams.php', 'create_team.php']) ? 'active' : ''; ?>">
                            Teams
                        </a>
                    </li>
                    <li>
                        <a href="view_meetings.php" class="nav-link <?php echo in_array($current_page, ['view_meetings.php', 'schedule_meeting.php']) ? 'active' : ''; ?>">
                            Meetings
                        </a>
                    </li>
                    <li>
                        <a href="view_resources.php" class="nav-link <?php echo ($current_page === 'view_resources.php') ? 'active' : ''; ?>">
                            Resources
                        </a>
                    </li>
                    <li>
                        <a href="view_progress.php" class="nav-link <?php echo ($current_page === 'view_progress.php') ? 'active' : ''; ?>">
                            Progress
                        </a>
                    </li>
                    <li>
                        <a href="view_feedback.php" class="nav-link <?php echo in_array($current_page, ['view_feedback.php', 'give_feedback.php']) ? 'active' : ''; ?>">
                            Feedback
                        </a>
                    </li>

                <?php elseif ($role === 'Admin'): ?>
                    <li>
                        <a href="admin_dashboard.php" class="nav-link <?php echo ($current_page === 'admin_dashboard.php') ? 'active' : ''; ?>">
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="manage_users.php" class="nav-link <?php echo ($current_page === 'manage_users.php') ? 'active' : ''; ?>">
                            Users
                        </a>
                    </li>
                    <li>
                        <a href="view_all_projects.php" class="nav-link <?php echo ($current_page === 'view_all_projects.php') ? 'active' : ''; ?>">
                            Projects
                        </a>
                    </li>
                    <li>
                        <a href="admin_view_meetings.php" class="nav-link <?php echo ($current_page === 'admin_view_meetings.php') ? 'active' : ''; ?>">
                            Meetings
                        </a>
                    </li>
                    <li>
                        <a href="admin_view_all.php" class="nav-link <?php echo ($current_page === 'admin_view_all.php') ? 'active' : ''; ?>">
                            System Overview
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="navbar-user">
                <div class="user-badge" title="<?php echo htmlspecialchars($user_name); ?>">
                    <span class="user-avatar"><?php echo htmlspecialchars($user_initial); ?></span>
                    <span><?php echo htmlspecialchars($user_name); ?></span>
                </div>
                <a href="logout.php" class="btn btn-sm btn-outline">Logout</a>
            </div>
        <?php endif; ?>
    </div>
</nav>
