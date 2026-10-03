<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "Admin") {
    header("Location: login.php");
    exit();
}

$sql = "SELECT u.User_ID, u.First_Name, u.Last_Name, u.Department, u.Email,
               CASE 
                   WHEN s.Student_ID IS NOT NULL THEN 'Student'
                   WHEN f.Faculty_ID IS NOT NULL THEN 'Faculty'
                   WHEN a.Admin_ID IS NOT NULL THEN 'Admin'
                   ELSE 'User'
               END AS Role
        FROM user u
        LEFT JOIN student s ON u.User_ID = s.Student_ID
        LEFT JOIN faculty f ON u.User_ID = f.Faculty_ID
        LEFT JOIN admin a ON u.User_ID = a.Admin_ID
        ORDER BY u.User_ID ASC";

$result = $conn->query($sql);

$page_title = "Manage Users Registry — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="page-title">University User Directory</h1>
                <p class="page-subtitle">Central registry of student researchers, faculty mentors, and system administrators</p>
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
                            <th>User ID</th>
                            <th>Full Name</th>
                            <th>Academic Department</th>
                            <th>Institutional Email</th>
                            <th>Portal Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php 
                            $r_class = 'badge-low';
                            if ($row['Role'] === 'Faculty') $r_class = 'badge-active';
                            elseif ($row['Role'] === 'Admin') $r_class = 'badge-pending';
                            elseif ($row['Role'] === 'Student') $r_class = 'badge-completed';
                            ?>
                            <tr>
                                <td><strong>#<?php echo htmlspecialchars($row['User_ID']); ?></strong></td>
                                <td style="font-weight: 600; color: var(--text-main);">
                                    <?php echo htmlspecialchars($row['First_Name'] . ' ' . $row['Last_Name']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['Department']); ?></td>
                                <td style="color: var(--text-muted); font-size: 0.875rem;">
                                    <?php echo htmlspecialchars($row['Email']); ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $r_class; ?>">
                                        <?php echo htmlspecialchars($row['Role']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">&#128101;</div>
                <h2 class="empty-title">No Registered Users</h2>
                <p class="empty-description">There are no user accounts found in the portal database.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>