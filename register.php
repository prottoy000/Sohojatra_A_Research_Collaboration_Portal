<?php

session_start();
include 'db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id = (int)$_POST['user_id'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $department = trim($_POST['department']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $check_sql = "SELECT User_ID FROM User WHERE User_ID = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {

        $message = "User ID already exists.";

    } else {

        // Check if email is already registered
        $email_stmt = $conn->prepare("SELECT User_ID FROM User WHERE Email = ?");
        $email_stmt->bind_param("s", $email);
        $email_stmt->execute();

        if ($email_stmt->get_result()->num_rows > 0) {

            $message = "Email is already registered.";

        } else {

            $sql = "INSERT INTO User
                    (User_ID, First_Name, Last_Name, Department, Email, Password)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "isssss",
                $user_id,
                $first_name,
                $last_name,
                $department,
                $email,
                $password
            );

            if ($stmt->execute()) {

                if ($role == "Student") {

                    $sql = "INSERT INTO Student (Student_ID, Project_ID, Batch, Join_ID) VALUES (?, 0, '', 0)";
                    $r_stmt = $conn->prepare($sql);
                    $r_stmt->bind_param("i", $user_id);
                    $r_stmt->execute();
                    $r_stmt->close();
                } elseif ($role == "Faculty") {

                    $sql = "INSERT INTO Faculty (Faculty_ID, Designation) VALUES (?, '')";
                    $r_stmt = $conn->prepare($sql);
                    $r_stmt->bind_param("i", $user_id);
                    $r_stmt->execute();
                    $r_stmt->close();
                } elseif ($role == "Admin") {

                    $sql = "INSERT INTO Admin (Admin_ID) VALUES (?)";
                    $r_stmt = $conn->prepare($sql);
                    $r_stmt->bind_param("i", $user_id);
                    $r_stmt->execute();
                    $r_stmt->close();
                }

                $message = "Registration successful! You can now login.";

            } else {

                $message = "Registration failed.";

            }
            $stmt->close();
        }
        $email_stmt->close();
    }
    $check_stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Sohojatra Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">

    <div class="auth-card auth-card-wide">
        <div class="auth-header">
            <div class="auth-brand">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
                <span>Sohojatra</span>
            </div>
            <p class="auth-tagline">Create an academic account to collaborate on research</p>
        </div>

        <?php if (!empty($message)): ?>
            <?php 
            $is_success = (strpos($message, 'successful') !== false);
            $alert_class = $is_success ? 'alert-success' : 'alert-danger';
            $alert_icon = $is_success ? '✓' : '✕';
            ?>
            <div class="alert <?php echo $alert_class; ?>">
                <span class="alert-icon"><?php echo $alert_icon; ?></span>
                <div><?php echo htmlspecialchars($message); ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="user_id">University User ID <span class="required">*</span></label>
                    <input 
                        type="number" 
                        id="user_id" 
                        name="user_id" 
                        class="form-control" 
                        placeholder="e.g. 1015" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="role">Portal Role <span class="required">*</span></label>
                    <select id="role" name="role" class="form-select" required>
                        <option value="">Select your role</option>
                        <option value="Student">Student Researcher</option>
                        <option value="Faculty">Faculty Supervisor</option>
                        <option value="Admin">System Administrator</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="first_name">First Name <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="first_name" 
                        name="first_name" 
                        class="form-control" 
                        placeholder="e.g. Alex" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="last_name">Last Name <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="last_name" 
                        name="last_name" 
                        class="form-control" 
                        placeholder="e.g. Rivera" 
                        required
                    >
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="department">Department / Faculty <span class="required">*</span></label>
                <input 
                    type="text" 
                    id="department" 
                    name="department" 
                    class="form-control" 
                    placeholder="e.g. Computer Science & Engineering" 
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Institutional Email <span class="required">*</span></label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    placeholder="e.g. arivera@university.edu" 
                    required 
                    autocomplete="email"
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Account Password <span class="required">*</span></label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="Choose a strong password" 
                    required 
                    autocomplete="new-password"
                >
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1rem;">
                Complete Registration
            </button>
        </form>

        <div class="auth-footer">
            Already have an account? 
            <a href="login.php" style="font-weight: 600;">Sign in here</a>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>