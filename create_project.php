<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "Student") {
    header("Location: login.php");
    exit();
}

$message = "";
$message_type = "info";

$admin_sql = "SELECT a.Admin_ID, u.First_Name, u.Last_Name 
              FROM Admin a 
              JOIN User u ON a.Admin_ID = u.User_ID 
              ORDER BY u.First_Name, u.Last_Name";
$admin_result = mysqli_query($conn, $admin_sql);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $domain = trim($_POST['domain']);
    $admin_id = (int)$_POST['admin_id'];
    $description = trim($_POST['description']);
    $status = trim($_POST['status']);
    $student_id = (int)$_SESSION['user_id'];

    if (!empty($title) && !empty($domain) && $admin_id > 0) {
        $stmt = $conn->prepare("INSERT INTO Project (Title, Student_ID, Domain, Admin_ID, Description, Status, Creation_Date) VALUES (?, ?, ?, ?, ?, ?, CURDATE())");
        if ($stmt) {
            $stmt->bind_param("sisiss", $title, $student_id, $domain, $admin_id, $description, $status);
            if ($stmt->execute()) {
                $message = "Research project proposal submitted successfully!";
                $message_type = "success";
            } else {
                $message = "Error creating project. Please try again.";
                $message_type = "danger";
            }
            $stmt->close();
        } else {
            $message = "Error preparing project submission query.";
            $message_type = "danger";
        }
    } else {
        $message = "Please fill in all required project fields.";
        $message_type = "warning";
    }
}

$page_title = "Create Project — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Submit Research Project Proposal</h1>
                <p class="page-subtitle">Initiate a university collaborative research project and register oversight</p>
            </div>
            <div class="page-actions">
                <a href="view_project.php" class="btn btn-outline">&larr; My Projects</a>
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

            <form method="POST" action="create_project.php">
                <div class="form-group">
                    <label class="form-label" for="title">Project Title <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        class="form-control" 
                        placeholder="e.g. Autonomous Multi-Agent Path Planning in Dynamic Environments" 
                        required
                    >
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="domain">Research Domain / Topic <span class="required">*</span></label>
                        <input 
                            type="text" 
                            id="domain" 
                            name="domain" 
                            class="form-control" 
                            placeholder="e.g. Artificial Intelligence, Bioinformatics" 
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="admin_id">Assigned Reviewer / Admin <span class="required">*</span></label>
                        <select id="admin_id" name="admin_id" class="form-select" required>
                            <option value="">Select Reviewing Administrator</option>
                            <?php while ($admin = mysqli_fetch_assoc($admin_result)): ?>
                                <option value="<?php echo htmlspecialchars($admin['Admin_ID']); ?>">
                                    <?php echo htmlspecialchars($admin['First_Name'] . ' ' . $admin['Last_Name']); ?> (ID: <?php echo htmlspecialchars($admin['Admin_ID']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Initial Status <span class="required">*</span></label>
                    <select id="status" name="status" class="form-select" required>
                        <option value="Active">Active / In Progress</option>
                        <option value="Pending" selected>Pending Review</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Abstract & Problem Description</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        class="form-textarea" 
                        placeholder="Describe the research objectives, scope, methodology, and expected academic contributions..."
                        required
                    ></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Submit Research Project
                    </button>
                    <a href="view_project.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
