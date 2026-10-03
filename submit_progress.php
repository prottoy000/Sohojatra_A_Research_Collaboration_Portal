<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "Student") {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
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
    $p_stmt->bind_param("ii", $user_id, $user_id);
    $p_stmt->execute();
    $user_projects = $p_stmt->get_result();
}

$selected_proj_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : (isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $week_no = isset($_POST['week_no']) ? (int)$_POST['week_no'] : 0;
    $progress_update = isset($_POST['progress_update']) ? trim($_POST['progress_update']) : '';
    $project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;

    if ($week_no > 0 && !empty($progress_update)) {
        // Check if progress for this week already exists for this student
        $check_stmt = $conn->prepare("SELECT Week_No FROM progress WHERE Week_No = ? AND User_ID = ?");
        $check_stmt->bind_param("ii", $week_no, $user_id);
        $check_stmt->execute();
        $check_res = $check_stmt->get_result();

        $saved_ok = false;
        if ($check_res->num_rows > 0) {
            $update_stmt = $conn->prepare("UPDATE progress SET Submission_Date = CURDATE(), Progress_Update = ? WHERE Week_No = ? AND User_ID = ?");
            $update_stmt->bind_param("sii", $progress_update, $week_no, $user_id);
            if ($update_stmt->execute()) {
                $saved_ok = true;
                $message = "Research progress for Week " . $week_no . " updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating progress. Please try again.";
                $message_type = "danger";
            }
            $update_stmt->close();
        } else {
            $insert_stmt = $conn->prepare("INSERT INTO progress (Week_No, User_ID, Submission_Date, Progress_Update) VALUES (?, ?, CURDATE(), ?)");
            $insert_stmt->bind_param("iis", $week_no, $user_id, $progress_update);
            if ($insert_stmt->execute()) {
                $saved_ok = true;
                $message = "Weekly research progress submitted successfully!";
                $message_type = "success";
            } else {
                $message = "Error submitting progress.";
                $message_type = "danger";
            }
            $insert_stmt->close();
        }
        $check_stmt->close();

        // Associate progress with project in `tracks` table
        if ($saved_ok && $project_id > 0) {
            $tr_check = $conn->prepare("SELECT Project_ID FROM tracks WHERE Project_ID = ? AND User_ID = ? AND Week_No = ?");
            if ($tr_check) {
                $tr_check->bind_param("iii", $project_id, $user_id, $week_no);
                $tr_check->execute();
                if ($tr_check->get_result()->num_rows === 0) {
                    $tr_ins = $conn->prepare("INSERT INTO tracks (Project_ID, User_ID, Week_No) VALUES (?, ?, ?)");
                    if ($tr_ins) {
                        $tr_ins->bind_param("iii", $project_id, $user_id, $week_no);
                        $tr_ins->execute();
                        $tr_ins->close();
                    }
                }
                $tr_check->close();
            }
        }
    } else {
        $message = "Please provide both the week number and detailed progress notes.";
        $message_type = "warning";
    }
}

$page_title = "Submit Weekly Progress — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Submit Weekly Research Progress</h1>
                <p class="page-subtitle">Log milestone achievements, experiments conducted, blockers, and next steps</p>
            </div>
            <div class="page-actions">
                <?php if ($selected_proj_id > 0): ?>
                    <a href="view_project.php?id=<?php echo $selected_proj_id; ?>" class="btn btn-outline">&larr; Project Workspace</a>
                <?php endif; ?>
                <a href="view_progress.php" class="btn btn-outline">&larr; Progress History</a>
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

            <form method="POST" action="submit_progress.php<?php echo ($selected_proj_id > 0) ? '?project_id=' . $selected_proj_id : ''; ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="week_no">Research Week Number <span class="required">*</span></label>
                        <input 
                            type="number" 
                            id="week_no" 
                            name="week_no" 
                            min="1" 
                            max="52" 
                            class="form-control" 
                            placeholder="e.g. 1" 
                            required
                        >
                        <div class="form-text">
                            Submitting a week number that already exists will safely update your report for that week.
                        </div>
                    </div>

                    <?php if ($user_projects && $user_projects->num_rows > 0): ?>
                        <div class="form-group">
                            <label class="form-label" for="project_id">Associate with Project</label>
                            <select id="project_id" name="project_id" class="form-select">
                                <option value="0">-- General Progress (Not linked to specific project) --</option>
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
                </div>

                <div class="form-group">
                    <label class="form-label" for="progress_update">Weekly Report & Research Summary <span class="required">*</span></label>
                    <textarea 
                        id="progress_update" 
                        name="progress_update" 
                        class="form-textarea" 
                        style="min-height: 160px;" 
                        placeholder="Summarize papers reviewed, dataset preprocessing, experiments run, code commits, and challenges encountered this week..." 
                        required
                    ></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Submit Weekly Progress
                    </button>
                    <a href="view_progress.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>