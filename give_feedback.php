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

// Fetch student meeting sessions for this faculty
$sql = "SELECT
            attends.Meeting_ID,
            attends.Student_ID,
            meeting.Date,
            meeting.Time,
            meeting.Location,
            user.First_Name,
            user.Last_Name
        FROM attends
        INNER JOIN meeting ON attends.Meeting_ID = meeting.Meeting_ID
        INNER JOIN user ON attends.Student_ID = user.User_ID
        WHERE attends.Faculty_ID = ?
        ORDER BY meeting.Date DESC, meeting.Time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$meetings = $stmt->get_result();

// Fetch recent student progress updates from supervised teams for context
$recent_progress = [];
$p_stmt = $conn->prepare("SELECT p.Week_No, p.User_ID, p.Submission_Date, p.Progress_Update, 
                                 u.First_Name, u.Last_Name, tm.Team_Name, prj.Title AS Project_Title 
                          FROM progress p 
                          JOIN user u ON p.User_ID = u.User_ID 
                          JOIN joins j ON p.User_ID = j.User_ID 
                          JOIN team tm ON j.Team_ID = tm.Team_ID 
                          JOIN project prj ON tm.Project_ID = prj.Project_ID 
                          WHERE tm.Faculty_ID = ? 
                          ORDER BY p.Submission_Date DESC LIMIT 5");
if ($p_stmt) {
    $p_stmt->bind_param("i", $faculty_id);
    $p_stmt->execute();
    $recent_progress = $p_stmt->get_result();
    $p_stmt->close();
}

$preselected_key = '';
if (isset($_GET['meeting_id'], $_GET['student_id'])) {
    $preselected_key = (int)$_GET['meeting_id'] . '_' . (int)$_GET['student_id'];
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['meeting_student'])) {
    $parts = explode('_', $_POST['meeting_student']);

    if (count($parts) === 2) {
        $meeting_id = (int)$parts[0];
        $student_id = (int)$parts[1];
        $feedback = trim(isset($_POST['feedback']) ? $_POST['feedback'] : '');

        if ($meeting_id > 0 && $student_id > 0 && !empty($feedback)) {
            $check_sql = "SELECT Student_ID FROM attends WHERE Student_ID = ? AND Faculty_ID = ? AND Meeting_ID = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("iii", $student_id, $faculty_id, $meeting_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows === 1) {
                $update_sql = "UPDATE attends SET Feedback = ? WHERE Student_ID = ? AND Faculty_ID = ? AND Meeting_ID = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("siii", $feedback, $student_id, $faculty_id, $meeting_id);

                if ($update_stmt->execute()) {
                    $message = "Constructive research evaluation and feedback submitted successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error submitting feedback. Please try again.";
                    $message_type = "danger";
                }
                $update_stmt->close();
            } else {
                $message = "Invalid meeting and student association.";
                $message_type = "danger";
            }
            $check_stmt->close();
        } else {
            $message = "Please select a student session and provide written evaluation notes.";
            $message_type = "warning";
        }
    } else {
        $message = "Invalid session selection.";
        $message_type = "warning";
    }
}

$page_title = "Provide Research Feedback — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header" style="flex-wrap: wrap; gap: 1rem; align-items: flex-start;">
            <div>
                <h1 class="page-title">Provide Research Feedback & Evaluation</h1>
                <p class="page-subtitle">Review student progress reports and deliver constructive guidance on thesis milestones</p>
            </div>
            <div class="page-actions">
                <a href="view_feedback.php" class="btn btn-outline">&larr; Feedback History</a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <span class="alert-icon">
                    <?php echo ($message_type === 'success') ? '✓' : (($message_type === 'danger') ? '✕' : '⚠'); ?>
                </span>
                <div><?php echo htmlspecialchars($message); ?></div>
            </div>
        <?php endif; ?>

        <div class="grid-2" style="align-items: start; margin-bottom: 2rem;">
            <!-- Feedback Form Card -->
            <div class="card" style="margin-bottom: 0;">
                <div class="card-header">
                    <h2 class="card-title">Supervisory Feedback Form</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="give_feedback.php">
                        <div class="form-group">
                            <label class="form-label" for="meeting_student">Select Student & Meeting Session <span class="required">*</span></label>
                            <select id="meeting_student" name="meeting_student" class="form-select" required>
                                <option value="">-- Choose student session --</option>
                                <?php if ($meetings && $meetings->num_rows > 0): ?>
                                    <?php while ($row = $meetings->fetch_assoc()): ?>
                                        <?php $key = $row['Meeting_ID'] . '_' . $row['Student_ID']; ?>
                                        <option value="<?php echo htmlspecialchars($key); ?>" <?php echo ($preselected_key === $key) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($row['First_Name'] . ' ' . $row['Last_Name']); ?> &bull; 
                                            Session #<?php echo htmlspecialchars($row['Meeting_ID']); ?> (<?php echo htmlspecialchars($row['Date']); ?>)
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="feedback">Evaluation Remarks & Next Steps <span class="required">*</span></label>
                            <textarea 
                                id="feedback" 
                                name="feedback" 
                                class="form-textarea" 
                                style="min-height: 180px;" 
                                placeholder="Detail methodology critique, experimental recommendations, literature gaps, and expectations for the upcoming research cycle..." 
                                required
                            ></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                Submit Feedback
                            </button>
                            <a href="view_feedback.php" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Student Progress Context Sidebar -->
            <div class="card" style="margin-bottom: 0;">
                <div class="card-header">
                    <h2 class="card-title">Recent Student Progress Reports</h2>
                </div>
                <div class="card-body">
                    <?php if ($recent_progress && $recent_progress->num_rows > 0): ?>
                        <div style="display: flex; flex-direction: column; gap: 0.85rem;">
                            <?php while ($pr = $recent_progress->fetch_assoc()): ?>
                                <div style="padding: 0.85rem 1rem; border: 1px solid var(--border-color); border-radius: var(--radius-md); background: var(--bg-main);">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem; flex-wrap: wrap;">
                                        <div>
                                            <span class="badge badge-active">Week <?php echo htmlspecialchars($pr['Week_No']); ?></span>
                                            <strong style="color: var(--text-main); font-size: 0.9rem; margin-left: 0.25rem;">
                                                <?php echo htmlspecialchars($pr['First_Name'] . ' ' . $pr['Last_Name']); ?>
                                            </strong>
                                        </div>
                                        <span style="font-size: 0.775rem; color: var(--text-muted);">&#128197; <?php echo htmlspecialchars($pr['Submission_Date']); ?></span>
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--primary); margin-bottom: 0.35rem;">
                                        <?php echo htmlspecialchars($pr['Team_Name']); ?> &bull; <?php echo htmlspecialchars($pr['Project_Title']); ?>
                                    </div>
                                    <p style="font-size: 0.825rem; color: var(--text-body); line-height: 1.5; margin: 0; white-space: pre-line;">
                                        <?php echo htmlspecialchars(mb_strimwidth($pr['Progress_Update'], 0, 160, "...")); ?>
                                    </p>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="padding: 1.5rem 1rem;">
                            <div class="empty-icon">&#128221;</div>
                            <div class="empty-title">No Progress Logs Available</div>
                            <div class="empty-description">Student progress submissions from your supervised teams will appear here.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>