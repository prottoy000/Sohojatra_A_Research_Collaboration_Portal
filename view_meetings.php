<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$today = date('Y-m-d');

if ($role === "Student") {
    $sql = "SELECT
                m.Meeting_ID,
                m.Date,
                m.Time,
                m.Location,
                m.Link,
                u.First_Name,
                u.Last_Name
            FROM meeting m
            INNER JOIN attends a ON m.Meeting_ID = a.Meeting_ID
            INNER JOIN user u ON a.Faculty_ID = u.User_ID
            WHERE a.Student_ID = ?
            ORDER BY m.Date DESC, m.Time DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif ($role === "Faculty") {
    $sql = "SELECT
                m.Meeting_ID,
                m.Date,
                m.Time,
                m.Location,
                m.Link,
                GROUP_CONCAT(CONCAT(u.First_Name, ' ', u.Last_Name) SEPARATOR ', ') AS Student_Names
            FROM meeting m
            INNER JOIN attends a ON m.Meeting_ID = a.Meeting_ID
            INNER JOIN user u ON a.Student_ID = u.User_ID
            WHERE a.Faculty_ID = ?
            GROUP BY m.Meeting_ID, m.Date, m.Time, m.Location, m.Link
            ORDER BY m.Date DESC, m.Time DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Admin: redirect to admin view meetings
    header("Location: admin_view_meetings.php");
    exit();
}

// Split into upcoming and past meetings
$upcoming_meetings = [];
$past_meetings = [];

if ($result && $result->num_rows > 0) {
    while ($m = $result->fetch_assoc()) {
        if ($m['Date'] >= $today) {
            $upcoming_meetings[] = $m;
        } else {
            $past_meetings[] = $m;
        }
    }
}

$page_title = "Research Meetings & Consultations — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header" style="flex-wrap: wrap; gap: 1rem; align-items: flex-start;">
            <div>
                <h1 class="page-title">Supervision & Team Consultations</h1>
                <p class="page-subtitle">Schedule, track, and attend research consultations, lab seminars, and project reviews</p>
            </div>
            <div class="page-actions">
                <?php if ($role === 'Faculty'): ?>
                    <a href="schedule_meeting.php" class="btn btn-primary">+ Schedule New Meeting</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($upcoming_meetings) || !empty($past_meetings)): ?>
            <!-- Section 1: Upcoming Consultations -->
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <h2 class="card-title">Upcoming Supervision Consultations</h2>
                        <span class="badge badge-active"><?php echo count($upcoming_meetings); ?> Scheduled</span>
                    </div>
                </div>

                <div class="card-body">
                    <?php if (!empty($upcoming_meetings)): ?>
                        <div class="grid-2">
                            <?php foreach ($upcoming_meetings as $row): ?>
                                <div style="border: 1px solid var(--primary-border); border-radius: var(--radius-md); background: var(--bg-card); padding: 1.15rem; display: flex; flex-direction: column; justify-content: space-between;">
                                    <div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <span class="badge badge-active">Session #<?php echo htmlspecialchars($row['Meeting_ID']); ?></span>
                                                <span class="badge badge-low">&#128197; <?php echo htmlspecialchars($row['Date']); ?></span>
                                            </div>
                                            <div style="font-weight: 700; color: var(--primary); font-size: 0.95rem;">
                                                &#128347; <?php echo htmlspecialchars($row['Time']); ?>
                                            </div>
                                        </div>

                                        <div style="display: flex; flex-direction: column; gap: 0.4rem; font-size: 0.85rem; margin-bottom: 1rem;">
                                            <div>
                                                <strong>Location / Room:</strong> 
                                                <span style="color: var(--text-muted);"><?php echo htmlspecialchars(!empty($row['Location']) ? $row['Location'] : 'Online'); ?></span>
                                            </div>
                                            <?php if ($role === "Student"): ?>
                                                <div>
                                                    <strong>Supervisor:</strong> 
                                                    <span style="color: var(--primary); font-weight: 600;">Dr. <?php echo htmlspecialchars($row['First_Name'] . ' ' . $row['Last_Name']); ?></span>
                                                </div>
                                            <?php else: ?>
                                                <div>
                                                    <strong>Attending Students:</strong> 
                                                    <span style="color: var(--text-body);"><?php echo htmlspecialchars($row['Student_Names']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 0.75rem; border-top: 1px solid var(--border-color);">
                                        <?php if (!empty($row['Link'])): ?>
                                            <a href="<?php echo htmlspecialchars($row['Link']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary">
                                                Join Video Room &nearr;
                                            </a>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 0.825rem;">Physical Session</span>
                                        <?php endif; ?>

                                        <?php if ($role === 'Faculty'): ?>
                                            <a href="give_feedback.php" class="btn btn-sm btn-outline">
                                                Feedback
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="padding: 1.5rem 1rem;">
                            <div class="empty-icon">&#128197;</div>
                            <div class="empty-title">No Upcoming Consultations</div>
                            <div class="empty-description">There are no upcoming meetings currently on your calendar.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Section 2: Past Consultation Archive -->
            <?php if (!empty($past_meetings)): ?>
                <div class="card" style="margin-bottom: 2rem;">
                    <div class="card-header">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <h2 class="card-title">Past Consultation Archive</h2>
                            <span class="badge badge-low"><?php echo count($past_meetings); ?> Concluded</span>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Session ID</th>
                                        <th>Date & Time</th>
                                        <th>Participants</th>
                                        <th>Room / Location</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($past_meetings as $row): ?>
                                        <tr>
                                            <td><strong>#<?php echo htmlspecialchars($row['Meeting_ID']); ?></strong></td>
                                            <td>
                                                &#128197; <?php echo htmlspecialchars($row['Date']); ?> at <?php echo htmlspecialchars($row['Time']); ?>
                                            </td>
                                            <td>
                                                <?php if ($role === "Student"): ?>
                                                    Dr. <?php echo htmlspecialchars($row['First_Name'] . ' ' . $row['Last_Name']); ?>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars(mb_strimwidth($row['Student_Names'], 0, 40, "...")); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars(!empty($row['Location']) ? $row['Location'] : 'Online'); ?></td>
                                            <td><span class="badge badge-low">Concluded</span></td>
                                            <td>
                                                <?php if ($role === 'Faculty'): ?>
                                                    <a href="give_feedback.php" class="btn btn-sm btn-outline">
                                                        Add Feedback
                                                    </a>
                                                <?php else: ?>
                                                    <a href="view_feedback.php" class="btn btn-sm btn-outline">
                                                        View Remarks
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">&#128197;</div>
                <h2 class="empty-title">No Meetings Found</h2>
                <p class="empty-description">
                    <?php echo ($role === 'Faculty') ? 'You have not scheduled any research check-in meetings yet.' : 'Your faculty supervisor has not scheduled any meetings for your team yet.'; ?>
                </p>
                <?php if ($role === 'Faculty'): ?>
                    <a href="schedule_meeting.php" class="btn btn-primary">+ Schedule First Meeting</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>