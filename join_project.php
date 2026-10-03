<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "Faculty") {
    header("Location: login.php");
    exit();
}

$faculty_id = (int)$_SESSION['user_id'];
$message = "";
$message_type = "info";

$sql = "SELECT p.Project_ID, p.Title, p.Domain, u.First_Name, u.Last_Name 
        FROM Project p 
        LEFT JOIN User u ON p.Student_ID = u.User_ID 
        ORDER BY p.Project_ID DESC";
$result = mysqli_query($conn, $sql);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;

    if ($project_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM Team WHERE Project_ID = ? AND Faculty_ID = ?");
        $stmt->bind_param("ii", $project_id, $faculty_id);
        $stmt->execute();
        $check = $stmt->get_result();

        if ($check->num_rows > 0) {
            $message = "You are already registered as a supervisor for this project.";
            $message_type = "warning";
        } else {
            $ins = $conn->prepare("INSERT INTO Team (Project_ID, Faculty_ID, Creation_Date) VALUES (?, ?, CURDATE())");
            $ins->bind_param("ii", $project_id, $faculty_id);
            if ($ins->execute()) {
                $message = "Successfully registered as faculty supervisor for this project!";
                $message_type = "success";
            } else {
                $message = "Error registering supervision. Please try again.";
                $message_type = "danger";
            }
            $ins->close();
        }
        $stmt->close();
    } else {
        $message = "Please select a valid research project.";
        $message_type = "warning";
    }
}

$page_title = "Supervise Research Project — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Supervise Research Project</h1>
                <p class="page-subtitle">Select an active student research initiative to mentor and oversee</p>
            </div>
            <div class="page-actions">
                <a href="my_projects.php" class="btn btn-outline">&larr; My Supervised Projects</a>
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

            <form method="POST" action="join_project.php">
                <div class="form-group">
                    <label class="form-label" for="project_id">Select Project to Supervise <span class="required">*</span></label>
                    <select id="project_id" name="project_id" class="form-select" required>
                        <option value="">-- Choose from available projects --</option>
                        <?php if ($result && mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <option value="<?php echo htmlspecialchars($row['Project_ID']); ?>">
                                    #<?php echo htmlspecialchars($row['Project_ID']); ?> &bull; 
                                    <?php echo htmlspecialchars($row['Title']); ?> 
                                    (Domain: <?php echo htmlspecialchars($row['Domain']); ?>)
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                    <div class="form-text">
                        Supervising a project automatically establishes your oversight connection in the portal.
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Confirm Supervision
                    </button>
                    <a href="my_projects.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
