<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

if ($role === "Student") {
    $sql = "SELECT
                attends.Meeting_ID,
                meeting.Date,
                meeting.Time,
                meeting.Location,
                attends.Faculty_ID,
                attends.Feedback,
                u.First_Name,
                u.Last_Name
            FROM attends
            INNER JOIN meeting ON attends.Meeting_ID = meeting.Meeting_ID
            INNER JOIN user u ON attends.Faculty_ID = u.User_ID
            WHERE attends.Student_ID = ?
              AND attends.Feedback IS NOT NULL
              AND attends.Feedback != ''
            ORDER BY meeting.Date DESC, meeting.Time DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif ($role === "Faculty") {
    $sql = "SELECT
                attends.Meeting_ID,
                meeting.Date,
                meeting.Time,
                meeting.Location,
                attends.Student_ID,
                attends.Feedback,
                u.First_Name,
                u.Last_Name
            FROM attends
            INNER JOIN meeting ON attends.Meeting_ID = meeting.Meeting_ID
            INNER JOIN user u ON attends.Student_ID = u.User_ID
            WHERE attends.Faculty_ID = ?
              AND attends.Feedback IS NOT NULL
              AND attends.Feedback != ''
            ORDER BY meeting.Date DESC, meeting.Time DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Admin fallback
    header("Location: admin_dashboard.php");
    exit();
}

$page_title = "Research Feedback — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Research Evaluations & Feedback</h1>
                <p class="page-subtitle">Formal supervisory remarks, thesis evaluations, and project milestones feedback</p>
            </div>
            <div class="page-actions">
                <?php if ($role === 'Faculty'): ?>
                    <a href="give_feedback.php" class="btn btn-primary">+ Provide New Feedback</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="card" style="margin-bottom: 0;">
                        <div class="card-header" style="flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span class="badge badge-active">Meeting #<?php echo htmlspecialchars($row['Meeting_ID']); ?></span>
                                <span class="badge badge-low">&#128197; <?php echo htmlspecialchars($row['Date']); ?></span>
                            </div>
                            <div style="font-size: 0.875rem; color: var(--text-muted);">
                                <?php if ($role === 'Student'): ?>
                                    Supervisor: <strong style="color: var(--primary);">Dr. <?php echo htmlspecialchars($row['First_Name'] . ' ' . $row['Last_Name']); ?></strong>
                                <?php else: ?>
                                    Student: <strong style="color: var(--text-main);"><?php echo htmlspecialchars($row['First_Name'] . ' ' . $row['Last_Name']); ?></strong> (ID: <?php echo htmlspecialchars($row['Student_ID']); ?>)
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card-body">
                            <div style="background: var(--bg-main); border-left: 4px solid var(--primary); padding: 1.15rem 1.25rem; border-radius: 0 var(--radius-md) var(--radius-md) 0; color: var(--text-body); line-height: 1.65; white-space: pre-line;">
                                <?php echo nl2br(htmlspecialchars($row['Feedback'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">&#128172;</div>
                <h2 class="empty-title">No Feedback Recorded Yet</h2>
                <p class="empty-description">
                    <?php echo ($role === 'Student') ? 'Your supervisor has not recorded formal written feedback for your meeting sessions yet.' : 'You have not submitted written feedback for any student meetings yet.'; ?>
                </p>
                <?php if ($role === 'Faculty'): ?>
                    <a href="give_feedback.php" class="btn btn-primary">+ Provide First Feedback</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>