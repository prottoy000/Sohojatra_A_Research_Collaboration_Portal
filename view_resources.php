<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

$sql = "SELECT Resource.Resource_ID, Resource.Title, Resource.Student_ID, Resource.Link, Resource.Type,
               User.First_Name, User.Last_Name
        FROM Resource
        LEFT JOIN User ON Resource.Student_ID = User.User_ID
        ORDER BY Resource.Resource_ID DESC";

$result = mysqli_query($conn, $sql);

$page_title = "Research Resources Library — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Research Resources & Library</h1>
                <p class="page-subtitle">Central collection of papers, open datasets, technical documentation, and codebases</p>
            </div>
            <div class="page-actions">
                <?php if ($role === 'Student'): ?>
                    <a href="add_resource.php" class="btn btn-primary">+ Share New Resource</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Resource Title</th>
                            <th>Category</th>
                            <th>Shared By</th>
                            <th>Direct Access</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><strong>#<?php echo htmlspecialchars($row['Resource_ID']); ?></strong></td>
                                <td style="font-weight: 600; color: var(--text-main); max-width: 320px;">
                                    <?php echo htmlspecialchars($row['Title']); ?>
                                </td>
                                <td>
                                    <span class="badge badge-low">
                                        <?php echo htmlspecialchars($row['Type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($row['First_Name'])): ?>
                                        <strong><?php echo htmlspecialchars($row['First_Name'] . ' ' . $row['Last_Name']); ?></strong>
                                        <div style="font-size: 0.775rem; color: var(--text-muted);">Student ID: <?php echo htmlspecialchars($row['Student_ID']); ?></div>
                                    <?php else: ?>
                                        Student ID: <?php echo htmlspecialchars($row['Student_ID']); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($row['Link']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline">
                                        &#128279; Open Resource &nearr;
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">&#128218;</div>
                <h2 class="empty-title">No Resources Shared Yet</h2>
                <p class="empty-description">
                    Upload and share literature, datasets, or reference links to assist your research team.
                </p>
                <?php if ($role === 'Student'): ?>
                    <a href="add_resource.php" class="btn btn-primary">+ Share First Resource</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>