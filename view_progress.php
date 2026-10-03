<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

$sql = "SELECT p.Week_No,
               p.User_ID,
               p.Submission_Date,
               p.Progress_Update,
               u.First_Name,
               u.Last_Name,
               u.Department,
               prj.Project_ID,
               prj.Title AS Project_Title
        FROM progress p
        JOIN user u ON p.User_ID = u.User_ID
        LEFT JOIN tracks tr ON p.User_ID = tr.User_ID AND p.Week_No = tr.Week_No
        LEFT JOIN project prj ON tr.Project_ID = prj.Project_ID
        ORDER BY p.Week_No DESC, p.Submission_Date DESC";

$result = mysqli_query($conn, $sql);

$page_title = "Weekly Research Progress Timeline — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header" style="flex-wrap: wrap; gap: 1rem; align-items: flex-start;">
            <div>
                <h1 class="page-title">Weekly Research Progress Timeline</h1>
                <p class="page-subtitle">Chronological progression of research experiments, literature findings, and deliverables</p>
            </div>
            <div class="page-actions">
                <?php if ($role === 'Student'): ?>
                    <a href="submit_progress.php" class="btn btn-primary">&#9998; Submit Weekly Progress</a>
                <?php elseif ($role === 'Faculty'): ?>
                    <a href="give_feedback.php" class="btn btn-primary">+ Provide Evaluation & Feedback</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <!-- Timeline Container -->
            <div style="position: relative; padding-left: 2rem; border-left: 2px solid var(--border-color); display: flex; flex-direction: column; gap: 2rem; margin-left: 0.75rem;">
                <?php while ($progress = mysqli_fetch_assoc($result)): ?>
                    <div style="position: relative;">
                        <!-- Timeline Node Dot -->
                        <div style="position: absolute; left: -2.6rem; top: 1.25rem; width: 1.15rem; height: 1.15rem; border-radius: 50%; background: var(--primary); border: 3px solid #ffffff; box-shadow: 0 0 0 2px var(--primary-light);"></div>

                        <div class="card" style="margin-bottom: 0;">
                            <div class="card-header" style="flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                                <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                                    <span class="badge badge-active" style="font-size: 0.85rem; padding: 0.35rem 0.75rem;">
                                        Research Week <?php echo htmlspecialchars($progress['Week_No']); ?>
                                    </span>
                                    <div>
                                        <strong style="color: var(--text-main); font-size: 1rem;">
                                            <?php echo htmlspecialchars($progress['First_Name'] . ' ' . $progress['Last_Name']); ?>
                                        </strong>
                                        <span style="color: var(--text-muted); font-size: 0.825rem;">
                                            &bull; <?php echo htmlspecialchars($progress['Department']); ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($progress['Project_Title'])): ?>
                                        <a href="view_project.php?id=<?php echo $progress['Project_ID']; ?>" class="badge badge-low" style="text-decoration: none;">
                                            &#128300; <?php echo htmlspecialchars(mb_strimwidth($progress['Project_Title'], 0, 24, "...")); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div style="color: var(--text-muted); font-size: 0.825rem;">
                                    &#128197; Submitted: <?php echo htmlspecialchars($progress['Submission_Date']); ?>
                                </div>
                            </div>

                            <div class="card-body">
                                <div style="line-height: 1.7; color: var(--text-body); background: var(--bg-main); padding: 1.15rem 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); white-space: pre-line;">
                                    <?php echo nl2br(htmlspecialchars($progress['Progress_Update'])); ?>
                                </div>

                                <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
                                    <?php if ($role === 'Faculty'): ?>
                                        <a href="give_feedback.php" class="btn btn-sm btn-outline">
                                            Evaluate & Feedback &rarr;
                                        </a>
                                    <?php elseif ($role === 'Student' && (int)$progress['User_ID'] === $user_id): ?>
                                        <a href="submit_progress.php" class="btn btn-sm btn-secondary">
                                            Update Report
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">&#128221;</div>
                <h2 class="empty-title">No Progress Submissions Recorded</h2>
                <p class="empty-description">
                    No weekly research progress reports have been logged in the portal yet.
                </p>
                <?php if ($role === 'Student'): ?>
                    <a href="submit_progress.php" class="btn btn-primary">&#9998; Submit First Progress Report</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>