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
    $link = trim($_POST['link']);
    $type = trim($_POST['type']);
    $project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;

    // Validate URL format
    if (!empty($title) && !empty($link) && !empty($type)) {
        if (!filter_var($link, FILTER_VALIDATE_URL)) {
            $message = "Please provide a valid web URL (e.g. https://github.com/... or https://arxiv.org/...)";
            $message_type = "warning";
        } else {
            $stmt = $conn->prepare("INSERT INTO resource (Title, Student_ID, Link, Type) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("siss", $title, $student_id, $link, $type);
                if ($stmt->execute()) {
                    $resource_id = $conn->insert_id;

                    // Associate resource with project in `has` table
                    if ($project_id > 0) {
                        $h_stmt = $conn->prepare("INSERT INTO has (Resource_ID, Project_ID) VALUES (?, ?)");
                        if ($h_stmt) {
                            $h_stmt->bind_param("ii", $resource_id, $project_id);
                            $h_stmt->execute();
                            $h_stmt->close();
                        }
                    }

                    $message = "Research resource shared successfully with the portal!";
                    $message_type = "success";
                } else {
                    $message = "Error sharing resource. Please try again.";
                    $message_type = "danger";
                }
                $stmt->close();
            } else {
                $message = "Error preparing resource query.";
                $message_type = "danger";
            }
        }
    } else {
        $message = "Please complete all required fields.";
        $message_type = "warning";
    }
}

$page_title = "Share Research Resource — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Share Research Resource</h1>
                <p class="page-subtitle">Contribute academic literature, open datasets, code repositories, or lab documentation</p>
            </div>
            <div class="page-actions">
                <?php if ($selected_proj_id > 0): ?>
                    <a href="view_project.php?id=<?php echo $selected_proj_id; ?>" class="btn btn-outline">&larr; Project Workspace</a>
                <?php endif; ?>
                <a href="view_resources.php" class="btn btn-outline">&larr; Resources Library</a>
            </div>
        </div>

        <div class="form-card">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <span class="alert-icon">
                        <?php echo ($message_type === 'success') ? '✓' : (($message_type === 'danger') ? '✕' : 'ℹ'); ?>
                    </span>
                    <div><?php echo htmlspecialchars($message); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="add_resource.php<?php echo ($selected_proj_id > 0) ? '?project_id=' . $selected_proj_id : ''; ?>">
                <div class="form-group">
                    <label class="form-label" for="title">Resource Title / Reference Name <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        class="form-control" 
                        placeholder="e.g. ImageNet Dataset / Attention Is All You Need Paper" 
                        required
                    >
                </div>

                <?php if ($user_projects && $user_projects->num_rows > 0): ?>
                    <div class="form-group">
                        <label class="form-label" for="project_id">Associate with Project</label>
                        <select id="project_id" name="project_id" class="form-select">
                            <option value="0">-- General Research Resource (All projects) --</option>
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
                        <label class="form-label" for="type">Resource Category <span class="required">*</span></label>
                        <select id="type" name="type" class="form-select" required>
                            <option value="Research Paper">Research Paper / Preprint</option>
                            <option value="Dataset">Dataset / Benchmark</option>
                            <option value="Code Repository">Code Repository / GitHub</option>
                            <option value="Documentation">Technical Documentation</option>
                            <option value="Tool / Library">Tool / Software Library</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="link">Resource Web Link (URL) <span class="required">*</span></label>
                        <input 
                            type="url" 
                            id="link" 
                            name="link" 
                            class="form-control" 
                            placeholder="https://..." 
                            required
                        >
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Share Resource
                    </button>
                    <a href="view_resources.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>