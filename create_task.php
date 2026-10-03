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

// Fetch student's affiliated projects (as creator or team member)
$user_projects = [];
$p_stmt = $conn->prepare("SELECT DISTINCT p.Project_ID, p.Title 
                          FROM project p 
                          LEFT JOIN team t ON p.Project_ID = t.Project_ID 
                          LEFT JOIN joins j ON t.Team_ID = j.Team_ID 
                          WHERE p.Student_ID = ? OR j.User_ID = ? 
                          ORDER BY p.Project_ID DESC");
if ($p_stmt) {
    $p_stmt->bind_param("ii", $student_id, $student_id);
    $p_stmt->execute();
    $user_projects = $p_stmt->get_result();
}

$selected_proj_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : (isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST['title']);
    $priority = trim($_POST['priority']);
    $deadline = trim($_POST['deadline']);
    $project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;

    if (!empty($title) && !empty($priority) && !empty($deadline)) {
        $stmt = $conn->prepare("INSERT INTO tasks (Status, Priority, Title, Student_ID, Deadline) VALUES ('Pending', ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssis", $priority, $title, $student_id, $deadline);
            if ($stmt->execute()) {
                $task_id = $conn->insert_id;

                // Associate task with project in `contains` table
                if ($project_id > 0) {
                    $c_stmt = $conn->prepare("INSERT INTO contains (Task_ID, Project_ID) VALUES (?, ?)");
                    if ($c_stmt) {
                        $c_stmt->bind_param("ii", $task_id, $project_id);
                        $c_stmt->execute();
                        $c_stmt->close();
                    }
                }

                $message = "Research milestone task created successfully!";
                $message_type = "success";
            } else {
                $message = "Error creating task. Please try again.";
                $message_type = "danger";
            }
            $stmt->close();
        } else {
            $message = "Error preparing task statement.";
            $message_type = "danger";
        }
    } else {
        $message = "Please complete all required fields.";
        $message_type = "warning";
    }
}

$page_title = "Create Task — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Add Milestone Task</h1>
                <p class="page-subtitle">Schedule an actionable deliverable, set priority, and establish a deadline</p>
            </div>
            <div class="page-actions">
                <?php if ($selected_proj_id > 0): ?>
                    <a href="view_project.php?id=<?php echo $selected_proj_id; ?>" class="btn btn-outline">&larr; Project Workspace</a>
                <?php endif; ?>
                <a href="update_task.php" class="btn btn-outline">&larr; Task Board</a>
            </div>
        </div>

        <div class="form-card">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <span class="alert-icon">
                        <?php echo ($message_type === 'success') ? '✓' : (($message_type === 'danger') ? '✕' : '⚠'); ?>
                    </span>
                    <div><?php echo htmlspecialchars($message); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="create_task.php<?php echo ($selected_proj_id > 0) ? '?project_id=' . $selected_proj_id : ''; ?>">
                <div class="form-group">
                    <label class="form-label" for="title">Task Description / Objective <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        class="form-control" 
                        placeholder="e.g. Conduct comparative benchmark on Transformer architectures" 
                        required
                    >
                </div>

                <?php if ($user_projects && $user_projects->num_rows > 0): ?>
                    <div class="form-group">
                        <label class="form-label" for="project_id">Associate with Project</label>
                        <select id="project_id" name="project_id" class="form-select">
                            <option value="0">-- General Research Task (No specific project) --</option>
                            <?php 
                            $user_projects->data_seek(0);
                            while ($proj = $user_projects->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $proj['Project_ID']; ?>" <?php echo ($selected_proj_id === (int)$proj['Project_ID']) ? 'selected' : ''; ?>>
                                    Project #<?php echo $proj['Project_ID']; ?>: <?php echo htmlspecialchars($proj['Title']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="priority">Priority Level <span class="required">*</span></label>
                        <select id="priority" name="priority" class="form-select" required>
                            <option value="High">High Priority</option>
                            <option value="Medium" selected>Medium Priority</option>
                            <option value="Low">Low Priority</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="deadline">Target Deadline <span class="required">*</span></label>
                        <input 
                            type="date" 
                            id="deadline" 
                            name="deadline" 
                            class="form-control" 
                            required
                        >
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Create Milestone Task
                    </button>
                    <a href="update_task.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>