<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "Faculty") {
    header("Location: login.php");
    exit();
}

$faculty_id = (int)$_SESSION['user_id'];

$sql = "SELECT 
            p.Project_ID,
            p.Title,
            p.Domain,
            p.Description,
            p.Status,
            p.Creation_Date,
            t.Team_ID,
            t.Team_Name
        FROM Project p
        JOIN Team t ON p.Project_ID = t.Project_ID
        WHERE t.Faculty_ID = ?
        ORDER BY p.Project_ID DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$result = $stmt->get_result();

$page_title = "Supervised Research Projects — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Supervised Research Projects</h1>
                <p class="page-subtitle">Undergraduate and graduate research projects currently under your academic mentorship</p>
            </div>
            <div class="page-actions">
                <a href="join_project.php" class="btn btn-primary">+ Supervise Existing Project</a>
            </div>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Project ID</th>
                            <th>Project Title</th>
                            <th>Research Team</th>
                            <th>Domain</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php 
                            $status_class = 'badge-pending';
                            if (strcasecmp($row['Status'], 'Active') === 0 || strcasecmp($row['Status'], 'Approved') === 0) {
                                $status_class = 'badge-active';
                            } elseif (strcasecmp($row['Status'], 'Completed') === 0) {
                                $status_class = 'badge-completed';
                            }
                            ?>
                            <tr>
                                <td><strong>#<?php echo htmlspecialchars($row['Project_ID']); ?></strong></td>
                                <td style="font-weight: 600; color: var(--text-main); max-width: 240px;">
                                    <?php echo htmlspecialchars($row['Title']); ?>
                                </td>
                                <td>
                                    <span class="badge badge-low"><?php echo htmlspecialchars($row['Team_Name']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($row['Domain']); ?></td>
                                <td style="max-width: 280px; font-size: 0.85rem; color: var(--text-muted); line-height: 1.4;">
                                    <?php echo htmlspecialchars(mb_strimwidth($row['Description'], 0, 110, "...")); ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($row['Status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="view_project.php?id=<?php echo $row['Project_ID']; ?>" class="btn btn-sm btn-primary">Workspace</a>
                                        <a href="schedule_meeting.php" class="btn btn-sm btn-outline">Meet</a>
                                        <a href="give_feedback.php" class="btn btn-sm btn-secondary">Review</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">&#128218;</div>
                <h2 class="empty-title">No Supervised Projects Found</h2>
                <p class="empty-description">
                    You are not currently linked as the supervisor for any student projects. You can browse and supervise existing projects now.
                </p>
                <a href="join_project.php" class="btn btn-primary">+ Supervise an Existing Project</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>