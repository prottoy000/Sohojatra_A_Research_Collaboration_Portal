<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "Faculty") {
    header("Location: login.php");
    exit();
}

$faculty_id = (int)$_SESSION['user_id'];
$message = "";
$error = "";

$sql = "SELECT t.Team_ID, t.Team_Name, p.Title
        FROM team t
        INNER JOIN project p ON t.Project_ID = p.Project_ID
        WHERE t.Faculty_ID = ?
        ORDER BY t.Team_Name";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$teams = $stmt->get_result();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $date = trim($_POST['date']);
    $time = trim($_POST['time']);
    $location = trim($_POST['location']);
    $link = trim($_POST['link']);
    $team_id = (int)$_POST['team_id'];

    if (empty($date) || empty($time) || empty($link) || $team_id <= 0) {
        $error = "Please fill in all required fields (Date, Time, Link, and Team).";
    } else {
        $sql = "SELECT Team_ID FROM team WHERE Team_ID = ? AND Faculty_ID = ?";
        $check_stmt = $conn->prepare($sql);
        $check_stmt->bind_param("ii", $team_id, $faculty_id);
        $check_stmt->execute();
        $check = $check_stmt->get_result();

        if ($check->num_rows === 0) {
            $error = "Unauthorized or invalid team selection.";
        } else {
            $ins_sql = "INSERT INTO meeting (Time, Date, Location, Link) VALUES (?, ?, ?, ?)";
            $ins_stmt = $conn->prepare($ins_sql);
            $ins_stmt->bind_param("ssss", $time, $date, $location, $link);

            if ($ins_stmt->execute()) {
                $meeting_id = $conn->insert_id;

                // Add all team members to attends
                $stu_sql = "SELECT User_ID FROM joins WHERE Team_ID = ?";
                $stu_stmt = $conn->prepare($stu_sql);
                $stu_stmt->bind_param("i", $team_id);
                $stu_stmt->execute();
                $students = $stu_stmt->get_result();

                $att_sql = "INSERT INTO attends (Student_ID, Faculty_ID, Meeting_ID, Feedback) VALUES (?, ?, ?, '')";
                $att_stmt = $conn->prepare($att_sql);

                while ($student = $students->fetch_assoc()) {
                    $s_id = (int)$student['User_ID'];
                    $att_stmt->bind_param("iii", $s_id, $faculty_id, $meeting_id);
                    $att_stmt->execute();
                }
                $att_stmt->close();
                $stu_stmt->close();

                $message = "Research supervision meeting scheduled successfully! Invitations linked to team members.";
            } else {
                $error = "Failed to schedule meeting. Please try again.";
            }
            $ins_stmt->close();
        }
        $check_stmt->close();
    }
}

$page_title = "Schedule Meeting — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Schedule Supervision Meeting</h1>
                <p class="page-subtitle">Organize milestone syncs, review presentations, and thesis guidance sessions</p>
            </div>
            <div class="page-actions">
                <a href="view_meetings.php" class="btn btn-outline">&larr; Meeting Room Log</a>
            </div>
        </div>

        <div class="form-card">
            <?php if (!empty($message)): ?>
                <div class="alert alert-success">
                    <span class="alert-icon">✓</span>
                    <div><?php echo htmlspecialchars($message); ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <span class="alert-icon">✕</span>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="schedule_meeting.php">
                <div class="form-group">
                    <label class="form-label" for="team_id">Target Research Team <span class="required">*</span></label>
                    <select id="team_id" name="team_id" class="form-select" required>
                        <option value="">-- Select Supervised Team --</option>
                        <?php if ($teams && $teams->num_rows > 0): ?>
                            <?php while ($team = $teams->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($team['Team_ID']); ?>">
                                    <?php echo htmlspecialchars($team['Team_Name'] . ' — ' . $team['Title']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="date">Meeting Date <span class="required">*</span></label>
                        <input 
                            type="date" 
                            id="date" 
                            name="date" 
                            class="form-control" 
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="time">Meeting Time <span class="required">*</span></label>
                        <input 
                            type="time" 
                            id="time" 
                            name="time" 
                            class="form-control" 
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="location">Physical Location / Room</label>
                    <input 
                        type="text" 
                        id="location" 
                        name="location" 
                        class="form-control" 
                        placeholder="e.g. Lab 402, Dept of CSE (or leave blank if online)"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="link">Meeting Link (Google Meet / Zoom) <span class="required">*</span></label>
                    <input 
                        type="url" 
                        id="link" 
                        name="link" 
                        class="form-control" 
                        placeholder="https://meet.google.com/..." 
                        required
                    >
                    <div class="form-text">
                        All student members of the selected team will have immediate access to this link in their dashboard.
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Schedule Meeting
                    </button>
                    <a href="view_meetings.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
