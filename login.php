<?php

session_start();
include 'db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM User WHERE Email = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $user = $result->fetch_assoc();

        $password_matches = false;

        if (password_verify($password, $user['Password'])) {
            $password_matches = true;
        } elseif ($password === $user['Password']) {
            // Legacy demo account with plaintext password: authenticate and automatically upgrade to secure hash
            $password_matches = true;
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            $upgrade_stmt = $conn->prepare("UPDATE User SET Password = ? WHERE User_ID = ?");
            if ($upgrade_stmt) {
                $upgrade_stmt->bind_param("si", $new_hash, $user['User_ID']);
                $upgrade_stmt->execute();
                $upgrade_stmt->close();
            }
        }

        if ($password_matches) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['User_ID'];
            $_SESSION['first_name'] = $user['First_Name'];
            $_SESSION['last_name'] = $user['Last_Name'];
            $_SESSION['department'] = $user['Department'];
            $_SESSION['email'] = $user['Email'];

            $user_id = $user['User_ID'];

            $sql = "SELECT * FROM Student WHERE Student_ID = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            $student_result = $stmt->get_result();

            if ($student_result->num_rows == 1) {

                $_SESSION['role'] = "Student";

                header("Location: student_dashboard.php");
                exit();
            }

            $sql = "SELECT * FROM Faculty WHERE Faculty_ID = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            $faculty_result = $stmt->get_result();

            if ($faculty_result->num_rows == 1) {

                $_SESSION['role'] = "Faculty";

                header("Location: faculty_dashboard.php");
                exit();
            }

            $sql = "SELECT * FROM Admin WHERE Admin_ID = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            $admin_result = $stmt->get_result();

            if ($admin_result->num_rows == 1) {

                $_SESSION['role'] = "Admin";

                header("Location: admin_dashboard.php");
                exit();
            }

            $message = "User role not found.";

        } else {

            $message = "Invalid email or password.";
        }

    } else {

        $message = "Invalid email or password.";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Sohojatra Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">

    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-brand">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
                <span>Sohojatra</span>
            </div>
            <p class="auth-tagline">University Research Collaboration Portal</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-danger">
                <span class="alert-icon">&#x2715;</span>
                <div><?php echo htmlspecialchars($message); ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label class="form-label" for="email">Academic Email</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    placeholder="e.g. student@univ.edu" 
                    required 
                    autocomplete="email"
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="Enter your password" 
                    required 
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1rem;">
                Sign In
            </button>
        </form>

        <div class="auth-footer">
            Don't have an account yet? 
            <a href="register.php" style="font-weight: 600;">Register here</a>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>

