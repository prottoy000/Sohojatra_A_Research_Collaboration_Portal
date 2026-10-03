<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "Student") {
    header("Location: login.php");
    exit();
}

$message = "";
$message_type = "info";

$faculty_sql = "SELECT f.Faculty_ID, f.Designation, u.First_Name, u.Last_Name
                FROM Faculty f
                JOIN User u ON f.Faculty_ID = u.User_ID
                ORDER BY u.First_Name, u.Last_Name";
$faculty_result = mysqli_query($conn, $faculty_sql);

$project_sql = "SELECT Project_ID, Title FROM Project ORDER BY Project_ID DESC";
$project_result = mysqli_query($conn, $project_sql);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $team_name = trim($_POST['team_name']);
    $project_id = (int)$_POST['project_id'];
    $faculty_id = (int)$_POST['faculty_id'];
    $student_id = (int)$_SESSION['user_id'];

    if (!empty($team_name) && $project_id > 0 && $faculty_id > 0) {
        $sql = "INSERT INTO Team (Creation_Date, Faculty_ID, Team_Name, Project_ID) VALUES (CURDATE(), ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isi", $faculty_id, $team_name, $project_id);

        if (mysqli_stmt_execute($stmt)) {
            $team_id = mysqli_insert_id($conn);

            $join_sql = "INSERT INTO Joins (User_ID, Team_ID, Role, Date) VALUES (?, ?, 'Leader', CURDATE())";
            $join_stmt = mysqli_prepare($conn, $join_sql);
            mysqli_stmt_bind_param($join_stmt, "ii", $student_id, $team_id);
            mysqli_stmt_execute($join_stmt);
            mysqli_stmt_close($join_stmt);

            $message = "Team created successfully! You are assigned as the Team Leader.";
            $message_type = "success";
        } else {
            $message = "Error creating research team. Please try again.";
            $message_type = "danger";
        }
        mysqli_stmt_close($stmt);
    } else {
        $message = "Please complete all required fields.";
        $message_type = "warning";
    }
}

$page_title = "Form Research Team — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Form a Research Team</h1>
                <p class="page-subtitle">Establish a collaborative student team, link your research project, and assign a faculty mentor</p>
            </div>
            <div class="page-actions">
                <a href="view_team.php" class="btn btn-outline">&larr; My Teams</a>
            </div>
        </div>

        <div class="form-card">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <span class="alert-icon">
                        <?php echo ($message_type === 'success') ? '✓' : (($message_type === 'danger') ? '✕' : '⚠'); ?>
                    </span>
                    <div><?php echo htmlspecialchars($message); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="create_team.php">
                <div class="form-group">
                    <label class="form-label" for="team_name">Team Name <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="team_name" 
                        name="team_name" 
                        class="form-control" 
                        placeholder="e.g. Vision Lab Group A" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="project_id">Associated Research Project <span class="required">*</span></label>
                    <select id="project_id" name="project_id" class="form-select" required>
                        <option value="">-- Select Project --</option>
                        <?php while ($proj = mysqli_fetch_assoc($project_result)): ?>
                            <option value="<?php echo htmlspecialchars($proj['Project_ID']); ?>">
                                #<?php echo htmlspecialchars($proj['Project_ID']); ?> &bull; <?php echo htmlspecialchars($proj['Title']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="faculty_id">Faculty Supervisor <span class="required">*</span></label>
                    <select id="faculty_id" name="faculty_id" class="form-select" required>
                        <option value="">-- Select Faculty Supervisor --</option>
                        <?php while ($faculty = mysqli_fetch_assoc($faculty_result)): ?>
                            <option value="<?php echo htmlspecialchars($faculty['Faculty_ID']); ?>">
                                Dr. <?php echo htmlspecialchars($faculty['First_Name'] . ' ' . $faculty['Last_Name']); ?> 
                                (<?php echo htmlspecialchars(!empty($faculty['Designation']) ? $faculty['Designation'] : 'Faculty'); ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <div class="form-text">
                        The faculty supervisor will receive meeting requests and provide evaluations.
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Create Team
                    </button>
                    <a href="view_team.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
