<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "Admin") {
    header("Location: login.php");
    exit();
}

$sql = "SELECT
            meeting.Meeting_ID,
            meeting.Date,
            meeting.Time,
            meeting.Location,
            meeting.Link,
            COUNT(attends.Student_ID) AS Student_Count
        FROM meeting
        LEFT JOIN attends ON meeting.Meeting_ID = attends.Meeting_ID
        GROUP BY
            meeting.Meeting_ID,
            meeting.Date,
            meeting.Time,
            meeting.Location,
            meeting.Link
        ORDER BY meeting.Date DESC, meeting.Time DESC";

$result = $conn->query($sql);

$page_title = "Meetings Registry — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Meetings & Seminars Registry</h1>
                <p class="page-subtitle">Central oversight of all scheduled research syncs and student attendance across faculties</p>
            </div>
            <div class="page-actions">
                <a href="admin_dashboard.php" class="btn btn-outline">&larr; Admin Dashboard</a>
            </div>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Meeting ID</th>
                            <th>Scheduled Date</th>
                            <th>Time</th>
                            <th>Location / Room</th>
                            <th>Meeting Room Link</th>
                            <th>Student Attendees</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?php echo htmlspecialchars($row['Meeting_ID']); ?></strong></td>
                                <td style="white-space: nowrap;">&#128197; <?php echo htmlspecialchars($row['Date']); ?></td>
                                <td style="font-weight: 600; color: var(--primary);">&#128347; <?php echo htmlspecialchars($row['Time']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars(!empty($row['Location']) ? $row['Location'] : 'Online'); ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['Link'])): ?>
                                        <a href="<?php echo htmlspecialchars($row['Link']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline">
                                            Open Room &nearr;
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-low">
                                        <?php echo htmlspecialchars($row['Student_Count']); ?> Students
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">&#128197;</div>
                <h2 class="empty-title">No Meetings Scheduled</h2>
                <p class="empty-description">There are no faculty research meetings registered in the portal yet.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>