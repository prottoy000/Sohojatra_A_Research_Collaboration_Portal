<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Redirect to role-specific dashboard if role is set
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'Student') {
        header("Location: student_dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'Faculty') {
        header("Location: faculty_dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'Admin') {
        header("Location: admin_dashboard.php");
        exit();
    }
}

$page_title = "User Profile — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="page-title">User Profile</h1>
                <p class="page-subtitle">Your registered university research credentials</p>
            </div>
            <div class="page-actions">
                <a href="logout.php" class="btn btn-outline">Sign Out</a>
            </div>
        </div>

        <div class="card" style="max-width: 600px; margin: 0 auto;">
            <div class="card-header">
                <h2 class="card-title">Profile Information</h2>
            </div>
            <div class="card-body">
                <p style="margin-bottom: 0.75rem;">
                    <strong>Full Name:</strong> 
                    <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . (isset($_SESSION['last_name']) ? $_SESSION['last_name'] : '')); ?>
                </p>
                <p style="margin-bottom: 0.75rem;">
                    <strong>University User ID:</strong> 
                    <?php echo htmlspecialchars($_SESSION['user_id']); ?>
                </p>
                <p style="margin-bottom: 0.75rem;">
                    <strong>Department:</strong> 
                    <?php echo htmlspecialchars(isset($_SESSION['department']) ? $_SESSION['department'] : 'Not Specified'); ?>
                </p>
                <p style="margin-bottom: 0.75rem;">
                    <strong>Email:</strong> 
                    <?php echo htmlspecialchars(isset($_SESSION['email']) ? $_SESSION['email'] : ''); ?>
                </p>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>