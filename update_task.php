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
$today = date('Y-m-d');

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['task_id'], $_POST['status'])) {
    $task_id = (int)$_POST['task_id'];
    $status = trim($_POST['status']);

    $stmt = $conn->prepare("UPDATE tasks SET Status = ? WHERE Task_ID = ? AND Student_ID = ?");
    if ($stmt) {
        $stmt->bind_param("sii", $status, $task_id, $student_id);
        if ($stmt->execute()) {
            $message = "Task status updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating task status.";
            $message_type = "danger";
        }
        $stmt->close();
    }
}

// Fetch tasks with linked project information if present
$stmt = $conn->prepare("SELECT t.Task_ID, t.Status, t.Priority, t.Title, t.Deadline, p.Project_ID, p.Title AS Project_Title 
                        FROM tasks t 
                        LEFT JOIN contains c ON t.Task_ID = c.Task_ID 
                        LEFT JOIN project p ON c.Project_ID = p.Project_ID 
                        WHERE t.Student_ID = ? 
                        ORDER BY (t.Status = 'Completed') ASC, t.Deadline ASC");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$page_title = "Milestone Tasks Board — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Research Tasks Board</h1>
                <p class="page-subtitle">Track deliverables, manage milestone deadlines, and update progress states</p>
            </div>
            <div class="page-actions">
                <a href="create_task.php" class="btn btn-primary">+ Create New Task</a>
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
                            <th>Task Objective</th>
                            <th>Project Workspace</th>
                            <th>Priority</th>
                            <th>Target Deadline</th>
                            <th>Current Status</th>
                            <th>Update Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php 
                            $is_done = (strcasecmp($row['Status'], 'Completed') === 0);
                            $p_class = 'badge-low';
                            if (strcasecmp($row['Priority'], 'High') === 0) $p_class = 'badge-high';
                            elseif (strcasecmp($row['Priority'], 'Medium') === 0) $p_class = 'badge-medium';

                            $s_class = $is_done ? 'badge-completed' : ((strcasecmp($row['Status'], 'In Progress') === 0) ? 'badge-active' : 'badge-pending');

                            // Deadline alert tag
                            $due_tag = '';
                            if ($is_done) {
                                $due_tag = '<span class="badge badge-completed">Done</span>';
                            } elseif ($row['Deadline'] < $today) {
                                $due_tag = '<span class="badge badge-danger">Overdue</span>';
                            } elseif ($row['Deadline'] === $today) {
                                $due_tag = '<span class="badge badge-warning">Due Today</span>';
                            } else {
                                $due_tag = '<span class="badge badge-low">Upcoming</span>';
                            }
                            ?>
                            <tr>
                                <td style="max-width: 300px; <?php echo $is_done ? 'text-decoration: line-through; opacity: 0.7;' : 'font-weight: 600; color: var(--text-main);'; ?>">
                                    <?php echo htmlspecialchars($row['Title']); ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['Project_Title'])): ?>
                                        <a href="view_project.php?id=<?php echo $row['Project_ID']; ?>" class="badge badge-low" style="text-decoration: none;">
                                            &#128300; <?php echo htmlspecialchars(mb_strimwidth($row['Project_Title'], 0, 24, "...")); ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 0.8rem;">General Task</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $p_class; ?>">
                                        <?php echo htmlspecialchars($row['Priority']); ?>
                                    </span>
                                </td>
                                <td style="white-space: nowrap; font-size: 0.85rem;">
                                    &#128197; <?php echo htmlspecialchars($row['Deadline']); ?> <?php echo $due_tag; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $s_class; ?>">
                                        <?php echo htmlspecialchars($row['Status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: flex; gap: 0.5rem; align-items: center; margin: 0;">
                                        <input type="hidden" name="task_id" value="<?php echo htmlspecialchars($row['Task_ID']); ?>">
                                        <select name="status" class="form-select" style="padding: 0.35rem 0.5rem; font-size: 0.825rem; width: auto;">
                                            <option value="Pending" <?php if ($row['Status'] === 'Pending') echo 'selected'; ?>>Pending</option>
                                            <option value="In Progress" <?php if ($row['Status'] === 'In Progress') echo 'selected'; ?>>In Progress</option>
                                            <option value="Completed" <?php if ($row['Status'] === 'Completed') echo 'selected'; ?>>Completed</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-secondary">
                                            Save
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
                <div class="empty-icon">&#10003;</div>
                <h2 class="empty-title">No Tasks Assigned Yet</h2>
                <p class="empty-description">
                    Stay organized and keep your research milestones on track by scheduling project tasks.
                </p>
                <a href="create_task.php" class="btn btn-primary">+ Add First Research Task</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>