<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// 1. Determine which project to view
$target_project_id = 0;
if (isset($_GET['id'])) {
    $target_project_id = (int)$_GET['id'];
} elseif (isset($_GET['project_id'])) {
    $target_project_id = (int)$_GET['project_id'];
}

// Fetch list of user's projects for project switcher navigation
$user_projects = [];
if ($role === 'Student') {
    $p_stmt = $conn->prepare("SELECT DISTINCT p.Project_ID, p.Title 
                              FROM project p 
                              LEFT JOIN team t ON p.Project_ID = t.Project_ID 
                              LEFT JOIN joins j ON t.Team_ID = j.Team_ID 
                              WHERE p.Student_ID = ? OR j.User_ID = ? 
                              ORDER BY p.Project_ID DESC");
    if ($p_stmt) {
        $p_stmt->bind_param("ii", $user_id, $user_id);
        $p_stmt->execute();
        $user_projects = $p_stmt->get_result();
    }
} elseif ($role === 'Faculty') {
    $p_stmt = $conn->prepare("SELECT DISTINCT p.Project_ID, p.Title 
                              FROM project p 
                              JOIN team t ON p.Project_ID = t.Project_ID 
                              WHERE t.Faculty_ID = ? 
                              ORDER BY p.Project_ID DESC");
    if ($p_stmt) {
        $p_stmt->bind_param("i", $user_id);
        $p_stmt->execute();
        $user_projects = $p_stmt->get_result();
    }
} else {
    // Admin: access to all projects
    $user_projects = $conn->query("SELECT Project_ID, Title FROM project ORDER BY Project_ID DESC");
}

// Fallback to first project if none specified in URL
if ($target_project_id <= 0 && $user_projects && $user_projects->num_rows > 0) {
    $user_projects->data_seek(0);
    $first = $user_projects->fetch_assoc();
    $target_project_id = (int)$first['Project_ID'];
}

// Fetch target project details
$project = null;
if ($target_project_id > 0) {
    $stmt = $conn->prepare("SELECT p.*, u.First_Name AS Creator_First, u.Last_Name AS Creator_Last, u.Email AS Creator_Email, u.Department AS Creator_Dept
                            FROM project p 
                            LEFT JOIN user u ON p.Student_ID = u.User_ID 
                            WHERE p.Project_ID = ?");
    if ($stmt) {
        $stmt->bind_param("i", $target_project_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 1) {
            $project = $res->fetch_assoc();
        }
        $stmt->close();
    }
}

// If project still not found, show empty state or redirect
$team_data = null;
$members = [];
$tasks = [];
$resources = [];
$progress_entries = [];
$meetings = [];
$feedback_entries = [];

if ($project) {
    $proj_id = (int)$project['Project_ID'];
    $creator_student_id = (int)$project['Student_ID'];

    // 2. Fetch Team & Faculty Supervisor
    $team_stmt = $conn->prepare("SELECT t.Team_ID, t.Team_Name, t.Faculty_ID, u.First_Name, u.Last_Name, u.Email, f.Designation 
                                  FROM team t 
                                  LEFT JOIN faculty f ON t.Faculty_ID = f.Faculty_ID 
                                  LEFT JOIN user u ON f.Faculty_ID = u.User_ID 
                                  WHERE t.Project_ID = ?");
    if ($team_stmt) {
        $team_stmt->bind_param("i", $proj_id);
        $team_stmt->execute();
        $team_res = $team_stmt->get_result();
        if ($team_res->num_rows > 0) {
            $team_data = $team_res->fetch_assoc();
        }
        $team_stmt->close();
    }

    $team_id = $team_data ? (int)$team_data['Team_ID'] : 0;
    $faculty_id = $team_data ? (int)$team_data['Faculty_ID'] : 0;

    // 3. Fetch Team Members
    if ($team_id > 0) {
        $mem_stmt = $conn->prepare("SELECT u.User_ID, u.First_Name, u.Last_Name, u.Email, u.Department, j.Role 
                                    FROM joins j 
                                    JOIN user u ON j.User_ID = u.User_ID 
                                    WHERE j.Team_ID = ? 
                                    ORDER BY (j.Role LIKE '%Lead%') DESC, u.First_Name ASC");
        if ($mem_stmt) {
            $mem_stmt->bind_param("i", $team_id);
            $mem_stmt->execute();
            $members = $mem_stmt->get_result();
            $mem_stmt->close();
        }
    }

    // 4. Fetch Tasks (from `contains` junction table + creator fallback)
    $task_stmt = $conn->prepare("SELECT t.*, u.First_Name, u.Last_Name 
                                 FROM tasks t 
                                 LEFT JOIN user u ON t.Student_ID = u.User_ID 
                                 WHERE t.Task_ID IN (SELECT Task_ID FROM contains WHERE Project_ID = ?) 
                                    OR (t.Student_ID = ? AND t.Task_ID NOT IN (SELECT Task_ID FROM contains))
                                 ORDER BY (t.Status = 'Completed') ASC, t.Deadline ASC");
    if ($task_stmt) {
        $task_stmt->bind_param("ii", $proj_id, $creator_student_id);
        $task_stmt->execute();
        $tasks = $task_stmt->get_result();
        $task_stmt->close();
    }

    // 5. Fetch Resources (from `has` junction table + creator fallback)
    $res_stmt = $conn->prepare("SELECT r.*, u.First_Name, u.Last_Name 
                                FROM resource r 
                                LEFT JOIN user u ON r.Student_ID = u.User_ID 
                                WHERE r.Resource_ID IN (SELECT Resource_ID FROM has WHERE Project_ID = ?) 
                                   OR (r.Student_ID = ? AND r.Resource_ID NOT IN (SELECT Resource_ID FROM has))
                                ORDER BY r.Resource_ID DESC");
    if ($res_stmt) {
        $res_stmt->bind_param("ii", $proj_id, $creator_student_id);
        $res_stmt->execute();
        $resources = $res_stmt->get_result();
        $res_stmt->close();
    }

    // 6. Fetch Progress Reports (from `tracks` junction table + creator fallback)
    $prog_stmt = $conn->prepare("SELECT p.*, u.First_Name, u.Last_Name 
                                 FROM progress p 
                                 JOIN user u ON p.User_ID = u.User_ID 
                                 WHERE (p.User_ID, p.Week_No) IN (SELECT User_ID, Week_No FROM tracks WHERE Project_ID = ?) 
                                    OR (p.User_ID = ? AND (p.User_ID, p.Week_No) NOT IN (SELECT User_ID, Week_No FROM tracks))
                                 ORDER BY p.Week_No DESC");
    if ($prog_stmt) {
        $prog_stmt->bind_param("ii", $proj_id, $creator_student_id);
        $prog_stmt->execute();
        $progress_entries = $prog_stmt->get_result();
        $prog_stmt->close();
    }

    // 7. Fetch Meetings associated with this project / team
    $meet_stmt = $conn->prepare("SELECT DISTINCT m.Meeting_ID, m.Date, m.Time, m.Location, m.Link, u.First_Name, u.Last_Name 
                                 FROM meeting m 
                                 JOIN attends a ON m.Meeting_ID = a.Meeting_ID 
                                 LEFT JOIN user u ON a.Faculty_ID = u.User_ID 
                                 WHERE (a.Faculty_ID > 0 AND a.Faculty_ID = ?) 
                                    OR (a.Student_ID IN (SELECT User_ID FROM joins WHERE Team_ID = ?))
                                    OR a.Student_ID = ?
                                 ORDER BY m.Date DESC, m.Time DESC");
    if ($meet_stmt) {
        $meet_stmt->bind_param("iii", $faculty_id, $team_id, $creator_student_id);
        $meet_stmt->execute();
        $meetings = $meet_stmt->get_result();
        $meet_stmt->close();
    }

    // 8. Fetch Faculty Feedback
    $fb_stmt = $conn->prepare("SELECT a.Feedback, a.Meeting_ID, m.Date, u.First_Name, u.Last_Name, stu.First_Name AS Stu_First, stu.Last_Name AS Stu_Last 
                               FROM attends a 
                               JOIN meeting m ON a.Meeting_ID = m.Meeting_ID 
                               JOIN user u ON a.Faculty_ID = u.User_ID 
                               JOIN user stu ON a.Student_ID = stu.User_ID 
                               WHERE a.Feedback IS NOT NULL AND a.Feedback != '' 
                                 AND ((a.Faculty_ID > 0 AND a.Faculty_ID = ?) OR a.Student_ID = ? OR a.Student_ID IN (SELECT User_ID FROM joins WHERE Team_ID = ?))
                               ORDER BY m.Date DESC");
    if ($fb_stmt) {
        $fb_stmt->bind_param("iii", $faculty_id, $creator_student_id, $team_id);
        $fb_stmt->execute();
        $feedback_entries = $fb_stmt->get_result();
        $fb_stmt->close();
    }
}

$page_title = $project ? htmlspecialchars($project['Title']) . " — Research Workspace" : "Research Workspace — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <!-- Workspace Header & Project Switcher -->
        <div class="page-header" style="flex-wrap: wrap; gap: 1rem; align-items: flex-start;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                    <span class="badge badge-low">&#128300; Research Workspace</span>
                    <?php if ($project): ?>
                        <span class="badge badge-low">ID #<?php echo $project['Project_ID']; ?></span>
                    <?php endif; ?>
                </div>
                <h1 class="page-title" style="margin-bottom: 0.25rem;">
                    <?php echo $project ? htmlspecialchars($project['Title']) : 'Research Project Workspace'; ?>
                </h1>
                <p class="page-subtitle">Central research hub integrating project oversight, team collaboration, milestones, and supervision</p>
            </div>

            <div class="page-actions" style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                <?php if ($user_projects && $user_projects->num_rows > 1): ?>
                    <form method="GET" action="view_project.php" style="display: inline-block;">
                        <select name="id" class="form-select" onchange="this.form.submit()" style="padding: 0.4rem 0.75rem; font-size: 0.85rem;">
                            <?php 
                            $user_projects->data_seek(0);
                            while ($p_option = $user_projects->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $p_option['Project_ID']; ?>" <?php echo ((int)$p_option['Project_ID'] === $target_project_id) ? 'selected' : ''; ?>>
                                    Workspace: <?php echo htmlspecialchars(mb_strimwidth($p_option['Title'], 0, 32, "...")); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </form>
                <?php endif; ?>

                <?php if ($role === 'Student'): ?>
                    <a href="create_project.php" class="btn btn-outline">+ Propose Project</a>
                <?php elseif ($role === 'Faculty'): ?>
                    <a href="join_project.php" class="btn btn-outline">+ Supervise Project</a>
                <?php endif; ?>
                <a href="view_all_projects.php" class="btn btn-secondary">Project Directory</a>
            </div>
        </div>

        <?php if ($project): ?>
            <?php
            $status_class = 'badge-pending';
            if (strcasecmp($project['Status'], 'Active') === 0 || strcasecmp($project['Status'], 'Approved') === 0) {
                $status_class = 'badge-active';
            } elseif (strcasecmp($project['Status'], 'Completed') === 0) {
                $status_class = 'badge-completed';
            }
            $today = date('Y-m-d');
            ?>

            <!-- SECTION 1: Project Overview & Supervision Card -->
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header" style="flex-wrap: wrap; gap: 0.75rem; padding-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <span class="badge <?php echo $status_class; ?>">Status: <?php echo htmlspecialchars($project['Status']); ?></span>
                        <span class="badge badge-low">&#128394; Domain: <?php echo htmlspecialchars($project['Domain']); ?></span>
                        <span class="badge badge-low">&#128197; Registered: <?php echo htmlspecialchars($project['Creation_Date']); ?></span>
                    </div>
                    <div>
                        <span style="font-size: 0.875rem; color: var(--text-muted);">
                            Lead Researcher: <strong><?php echo htmlspecialchars($project['Creator_First'] . ' ' . $project['Creator_Last']); ?></strong>
                            (<?php echo htmlspecialchars($project['Creator_Dept']); ?>)
                        </span>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Abstract & Scope -->
                    <div style="margin-bottom: 1.5rem;">
                        <h3 style="font-size: 0.875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 0.5rem;">
                            Research Abstract & Scope
                        </h3>
                        <p style="color: var(--text-body); line-height: 1.7; background: var(--bg-main); padding: 1.15rem 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); white-space: pre-line;">
                            <?php echo htmlspecialchars($project['Description']); ?>
                        </p>
                    </div>

                    <!-- Team & Supervision Dual Columns -->
                    <div class="grid-2" style="margin-bottom: 1rem;">
                        <!-- Team Column -->
                        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                <h4 style="font-size: 0.95rem; font-weight: 600; color: var(--text-main);">
                                    Collaborative Team
                                </h4>
                                <?php if ($team_data): ?>
                                    <span class="badge badge-active"><?php echo htmlspecialchars($team_data['Team_Name']); ?></span>
                                <?php elseif ($role === 'Student'): ?>
                                    <a href="create_team.php" class="btn btn-sm btn-outline">+ Form Team</a>
                                <?php endif; ?>
                            </div>

                            <?php if ($team_data && $members && $members->num_rows > 0): ?>
                                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                    <?php 
                                    $members->data_seek(0);
                                    while ($m = $members->fetch_assoc()): 
                                    ?>
                                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.45rem 0.65rem; background: var(--bg-main); border-radius: var(--radius-sm); font-size: 0.85rem;">
                                            <div>
                                                <strong><?php echo htmlspecialchars($m['First_Name'] . ' ' . $m['Last_Name']); ?></strong>
                                                <div style="color: var(--text-muted); font-size: 0.75rem;"><?php echo htmlspecialchars($m['Email']); ?></div>
                                            </div>
                                            <span class="badge badge-low"><?php echo htmlspecialchars($m['Role']); ?></span>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php elseif ($team_data): ?>
                                <p style="color: var(--text-muted); font-size: 0.85rem;">Team registered. Members may request to join.</p>
                            <?php else: ?>
                                <p style="color: var(--text-muted); font-size: 0.85rem;">No collaborative research team formed yet.</p>
                                <?php if ($role === 'Student'): ?>
                                    <a href="create_team.php" class="btn btn-sm btn-secondary" style="margin-top: 0.35rem;">Form Research Team</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Supervisor Column -->
                        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem;">
                            <h4 style="font-size: 0.95rem; font-weight: 600; color: var(--text-main); margin-bottom: 0.75rem;">
                                Faculty Supervisor
                            </h4>
                            <?php if ($team_data && !empty($team_data['First_Name'])): ?>
                                <div style="padding: 0.85rem 1rem; background: var(--primary-light); border: 1px solid var(--primary-border); border-radius: var(--radius-md);">
                                    <div style="font-weight: 700; color: var(--primary); font-size: 1rem;">
                                        Dr. <?php echo htmlspecialchars($team_data['First_Name'] . ' ' . $team_data['Last_Name']); ?>
                                    </div>
                                    <div style="color: var(--text-muted); font-size: 0.825rem; margin-top: 0.25rem;">
                                        <?php echo htmlspecialchars(!empty($team_data['Designation']) ? $team_data['Designation'] : 'Faculty Supervisor'); ?>
                                        &bull; <?php echo htmlspecialchars($team_data['Email']); ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p style="color: var(--text-muted); font-size: 0.85rem;">No faculty supervisor assigned yet.</p>
                                <?php if ($role === 'Faculty'): ?>
                                    <a href="join_project.php" class="btn btn-sm btn-primary" style="margin-top: 0.35rem;">Supervise This Project</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: Milestone Tasks & Deliverables -->
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <h2 class="card-title">Milestone Tasks & Deliverables</h2>
                        <span class="badge badge-low"><?php echo ($tasks) ? $tasks->num_rows : 0; ?> Tasks</span>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <?php if ($role === 'Student'): ?>
                            <a href="create_task.php?project_id=<?php echo $proj_id; ?>" class="btn btn-sm btn-primary">+ Add Milestone Task</a>
                        <?php endif; ?>
                        <a href="update_task.php" class="btn btn-sm btn-outline">Task Board</a>
                    </div>
                </div>

                <div class="card-body">
                    <?php if ($tasks && $tasks->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Milestone Objective</th>
                                        <th>Assignee</th>
                                        <th>Priority</th>
                                        <th>Deadline</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $tasks->data_seek(0);
                                    while ($t = $tasks->fetch_assoc()): 
                                        $is_completed = (strcasecmp($t['Status'], 'Completed') === 0);
                                        $p_class = 'badge-low';
                                        if (strcasecmp($t['Priority'], 'High') === 0) $p_class = 'badge-high';
                                        elseif (strcasecmp($t['Priority'], 'Medium') === 0) $p_class = 'badge-medium';

                                        $s_class = $is_completed ? 'badge-completed' : ((strcasecmp($t['Status'], 'In Progress') === 0) ? 'badge-active' : 'badge-pending');

                                        // Deadline status indicator
                                        $due_tag = '';
                                        if ($is_completed) {
                                            $due_tag = '<span class="badge badge-completed">Done</span>';
                                        } elseif ($t['Deadline'] < $today) {
                                            $due_tag = '<span class="badge badge-danger">Overdue</span>';
                                        } elseif ($t['Deadline'] === $today) {
                                            $due_tag = '<span class="badge badge-warning">Due Today</span>';
                                        } else {
                                            $due_tag = '<span class="badge badge-low">Upcoming</span>';
                                        }
                                    ?>
                                        <tr>
                                            <td style="max-width: 320px; <?php echo $is_completed ? 'text-decoration: line-through; opacity: 0.75;' : 'font-weight: 600; color: var(--text-main);'; ?>">
                                                <?php echo htmlspecialchars($t['Title']); ?>
                                            </td>
                                            <td style="font-size: 0.85rem; color: var(--text-muted);">
                                                <?php echo !empty($t['First_Name']) ? htmlspecialchars($t['First_Name'] . ' ' . $t['Last_Name']) : 'Student #' . $t['Student_ID']; ?>
                                            </td>
                                            <td><span class="badge <?php echo $p_class; ?>"><?php echo htmlspecialchars($t['Priority']); ?></span></td>
                                            <td style="white-space: nowrap; font-size: 0.85rem;">
                                                &#128197; <?php echo htmlspecialchars($t['Deadline']); ?> <?php echo $due_tag; ?>
                                            </td>
                                            <td><span class="badge <?php echo $s_class; ?>"><?php echo htmlspecialchars($t['Status']); ?></span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="padding: 1.5rem 1rem;">
                            <div class="empty-icon">&#9989;</div>
                            <div class="empty-title">No Milestone Tasks Scheduled</div>
                            <div class="empty-description">Define project milestones and assign deliverables to keep research on schedule.</div>
                            <?php if ($role === 'Student'): ?>
                                <a href="create_task.php?project_id=<?php echo $proj_id; ?>" class="btn btn-sm btn-primary">+ Create First Task</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SECTION 3: Research Resources & Literature -->
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <h2 class="card-title">Research Literature & Repositories</h2>
                        <span class="badge badge-low"><?php echo ($resources) ? $resources->num_rows : 0; ?> Items</span>
                    </div>
                    <?php if ($role === 'Student'): ?>
                        <a href="add_resource.php?project_id=<?php echo $proj_id; ?>" class="btn btn-sm btn-primary">+ Share Resource</a>
                    <?php endif; ?>
                </div>

                <div class="card-body">
                    <?php if ($resources && $resources->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Resource Name / Citation</th>
                                        <th>Category</th>
                                        <th>Contributed By</th>
                                        <th>Access Link</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $resources->data_seek(0);
                                    while ($r = $resources->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <td style="font-weight: 600; color: var(--text-main); max-width: 320px;">
                                                <?php echo htmlspecialchars($r['Title']); ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-low"><?php echo htmlspecialchars($r['Type']); ?></span>
                                            </td>
                                            <td style="font-size: 0.85rem; color: var(--text-muted);">
                                                <?php echo !empty($r['First_Name']) ? htmlspecialchars($r['First_Name'] . ' ' . $r['Last_Name']) : 'Researcher #' . $r['Student_ID']; ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo htmlspecialchars($r['Link']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline">
                                                    Open Link &nearr;
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="padding: 1.5rem 1rem;">
                            <div class="empty-icon">&#128214;</div>
                            <div class="empty-title">No Literature or Datasets Shared</div>
                            <div class="empty-description">Upload paper citations, dataset repositories, or code links for your team.</div>
                            <?php if ($role === 'Student'): ?>
                                <a href="add_resource.php?project_id=<?php echo $proj_id; ?>" class="btn btn-sm btn-primary">+ Add First Resource</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SECTION 4: Weekly Research Progress Feed -->
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <h2 class="card-title">Weekly Research Progress Timeline</h2>
                        <span class="badge badge-low"><?php echo ($progress_entries) ? $progress_entries->num_rows : 0; ?> Reports</span>
                    </div>
                    <?php if ($role === 'Student'): ?>
                        <a href="submit_progress.php?project_id=<?php echo $proj_id; ?>" class="btn btn-sm btn-primary">&#9998; Submit Progress</a>
                    <?php endif; ?>
                </div>

                <div class="card-body">
                    <?php if ($progress_entries && $progress_entries->num_rows > 0): ?>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <?php 
                            $progress_entries->data_seek(0);
                            while ($p = $progress_entries->fetch_assoc()): 
                            ?>
                                <div style="border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.15rem 1.25rem; background: var(--bg-main);">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 0.5rem;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <span class="badge badge-active">Week <?php echo htmlspecialchars($p['Week_No']); ?></span>
                                            <strong style="color: var(--text-main); font-size: 0.95rem;">
                                                <?php echo htmlspecialchars($p['First_Name'] . ' ' . $p['Last_Name']); ?>
                                            </strong>
                                        </div>
                                        <div style="font-size: 0.8rem; color: var(--text-muted);">
                                            &#128197; Submitted: <?php echo htmlspecialchars($p['Submission_Date']); ?>
                                        </div>
                                    </div>
                                    <div style="color: var(--text-body); font-size: 0.9rem; line-height: 1.65; white-space: pre-line;">
                                        <?php echo htmlspecialchars($p['Progress_Update']); ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="padding: 1.5rem 1rem;">
                            <div class="empty-icon">&#128221;</div>
                            <div class="empty-title">No Progress Reports Logged</div>
                            <div class="empty-description">Record weekly milestones and experimental observations to keep your supervisor informed.</div>
                            <?php if ($role === 'Student'): ?>
                                <a href="submit_progress.php?project_id=<?php echo $proj_id; ?>" class="btn btn-sm btn-primary">&#9998; Submit First Progress Report</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SECTION 5: Supervision Meetings & Faculty Feedback -->
            <div class="grid-2" style="margin-bottom: 2rem;">
                <!-- Meetings Column -->
                <div class="card" style="margin-bottom: 0;">
                    <div class="card-header">
                        <h2 class="card-title">Supervision Meetings</h2>
                        <?php if ($role === 'Faculty'): ?>
                            <a href="schedule_meeting.php" class="btn btn-sm btn-primary">+ Schedule</a>
                        <?php else: ?>
                            <a href="view_meetings.php" class="btn btn-sm btn-outline">All Meetings</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($meetings && $meetings->num_rows > 0): ?>
                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                <?php 
                                $meetings->data_seek(0);
                                while ($m = $meetings->fetch_assoc()): 
                                    $is_upcoming = ($m['Date'] >= $today);
                                ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-md); background: var(--bg-card);">
                                        <div>
                                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.2rem;">
                                                <strong style="color: var(--text-main); font-size: 0.9rem;">Session #<?php echo $m['Meeting_ID']; ?></strong>
                                                <span class="badge <?php echo $is_upcoming ? 'badge-active' : 'badge-low'; ?>">
                                                    <?php echo $is_upcoming ? 'Upcoming' : 'Concluded'; ?>
                                                </span>
                                            </div>
                                            <div style="font-size: 0.8rem; color: var(--text-muted);">
                                                &#128197; <?php echo htmlspecialchars($m['Date']); ?> at <?php echo htmlspecialchars($m['Time']); ?> 
                                                &bull; <?php echo htmlspecialchars(!empty($m['Location']) ? $m['Location'] : 'Online'); ?>
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
                                <div class="empty-description">Research consultations scheduled with your faculty supervisor will appear here.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Feedback Column -->
                <div class="card" style="margin-bottom: 0;">
                    <div class="card-header">
                        <h2 class="card-title">Supervisor Evaluations</h2>
                        <?php if ($role === 'Faculty'): ?>
                            <a href="give_feedback.php" class="btn btn-sm btn-primary">+ Add Feedback</a>
                        <?php else: ?>
                            <a href="view_feedback.php" class="btn btn-sm btn-outline">All Feedback</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($feedback_entries && $feedback_entries->num_rows > 0): ?>
                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                <?php 
                                $feedback_entries->data_seek(0);
                                while ($fb = $feedback_entries->fetch_assoc()): 
                                ?>
                                    <div style="background: var(--bg-main); border-left: 3px solid var(--primary); padding: 0.85rem 1rem; border-radius: 0 var(--radius-md) var(--radius-md) 0;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem; font-size: 0.8rem; color: var(--text-muted);">
                                            <span>
                                                Supervisor: <strong style="color: var(--primary);">Dr. <?php echo htmlspecialchars($fb['First_Name'] . ' ' . $fb['Last_Name']); ?></strong>
                                            </span>
                                            <span>&#128197; <?php echo htmlspecialchars($fb['Date']); ?></span>
                                        </div>
                                        <div style="color: var(--text-body); font-size: 0.875rem; line-height: 1.55; white-space: pre-line;">
                                            <?php echo htmlspecialchars($fb['Feedback']); ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state" style="padding: 1.5rem 1rem;">
                                <div class="empty-icon">&#128172;</div>
                                <div class="empty-title">No Feedback Recorded</div>
                                <div class="empty-description">Constructive feedback following supervisory meetings will be summarized here.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Workspace Action Toolbar -->
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; padding: 1.25rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                <?php if ($role === 'Student'): ?>
                    <a href="submit_progress.php?project_id=<?php echo $proj_id; ?>" class="btn btn-sm btn-primary">&#9998; Submit Progress</a>
                    <a href="create_task.php?project_id=<?php echo $proj_id; ?>" class="btn btn-sm btn-outline">+ Milestone Task</a>
                    <a href="add_resource.php?project_id=<?php echo $proj_id; ?>" class="btn btn-sm btn-outline">&#128279; Share Resource</a>
                <?php elseif ($role === 'Faculty'): ?>
                    <a href="schedule_meeting.php" class="btn btn-sm btn-primary">&#128197; Schedule Meeting</a>
                    <a href="give_feedback.php" class="btn btn-sm btn-outline">&#128172; Provide Feedback</a>
                <?php endif; ?>
                <a href="view_meetings.php" class="btn btn-sm btn-outline">&#128197; Meeting Room</a>
                <a href="view_all_projects.php" class="btn btn-sm btn-secondary">Explore All Projects</a>
            </div>

        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">&#128214;</div>
                <h2 class="empty-title">No Active Project Workspace Found</h2>
                <p class="empty-description">
                    <?php if ($role === 'Student'): ?>
                        You do not have any registered research projects yet. Propose your research topic to begin collaborating with faculty supervisors and peers.
                    <?php else: ?>
                        No research project matching the specified criteria was found. Select a project from the registry to view its workspace.
                    <?php endif; ?>
                </p>
                <?php if ($role === 'Student'): ?>
                    <a href="create_project.php" class="btn btn-primary">+ Propose New Research Project</a>
                <?php else: ?>
                    <a href="view_all_projects.php" class="btn btn-primary">Browse Project Directory</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>