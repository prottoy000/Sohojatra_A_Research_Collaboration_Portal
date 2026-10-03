<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// 1. Fetch available filter options
$domains_list = [];
$d_res = $conn->query("SELECT DISTINCT Domain FROM project WHERE Domain IS NOT NULL AND Domain != '' ORDER BY Domain ASC");
if ($d_res) {
    while ($d_row = $d_res->fetch_assoc()) {
        $domains_list[] = $d_row['Domain'];
    }
}

$faculty_list = [];
$f_res = $conn->query("SELECT f.Faculty_ID, u.First_Name, u.Last_Name, f.Designation 
                       FROM faculty f 
                       JOIN user u ON f.Faculty_ID = u.User_ID 
                       ORDER BY u.First_Name ASC");
if ($f_res) {
    while ($f_row = $f_res->fetch_assoc()) {
        $faculty_list[] = $f_row;
    }
}

// 2. Process search and filter inputs
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$domain = isset($_GET['domain']) ? trim($_GET['domain']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$faculty_id = isset($_GET['faculty_id']) ? (int)$_GET['faculty_id'] : 0;

// 3. Build dynamic query with prepared statement
$sql = "SELECT p.Project_ID, p.Title, p.Student_ID, p.Domain, p.Description, p.Status, p.Creation_Date,
               u_stu.First_Name AS Stu_First, u_stu.Last_Name AS Stu_Last,
               t.Team_Name, u_fac.First_Name AS Fac_First, u_fac.Last_Name AS Fac_Last, f.Faculty_ID
        FROM project p
        LEFT JOIN user u_stu ON p.Student_ID = u_stu.User_ID
        LEFT JOIN team t ON p.Project_ID = t.Project_ID
        LEFT JOIN faculty f ON t.Faculty_ID = f.Faculty_ID
        LEFT JOIN user u_fac ON f.Faculty_ID = u_fac.User_ID
        WHERE 1=1";

$types = "";
$params = [];

if (!empty($search)) {
    $sql .= " AND (p.Title LIKE ? OR p.Description LIKE ?)";
    $types .= "ss";
    $search_param = "%" . $search . "%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($domain)) {
    $sql .= " AND p.Domain = ?";
    $types .= "s";
    $params[] = $domain;
}

if (!empty($status)) {
    $sql .= " AND p.Status = ?";
    $types .= "s";
    $params[] = $status;
}

if ($faculty_id > 0) {
    $sql .= " AND f.Faculty_ID = ?";
    $types .= "i";
    $params[] = $faculty_id;
}

$sql .= " ORDER BY p.Project_ID DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$back_href = 'student_dashboard.php';
if ($role === 'Faculty') {
    $back_href = 'faculty_dashboard.php';
} elseif ($role === 'Admin') {
    $back_href = 'admin_dashboard.php';
}

$page_title = "University Research Projects Registry — Sohojatra Portal";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header" style="flex-wrap: wrap; gap: 1rem; align-items: flex-start;">
            <div>
                <h1 class="page-title">University Research Projects Registry</h1>
                <p class="page-subtitle">Search, filter, and explore collaborative research projects across all academic domains</p>
            </div>
            <div class="page-actions" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <?php if ($role === 'Student'): ?>
                    <a href="create_project.php" class="btn btn-primary">+ Propose Project</a>
                <?php elseif ($role === 'Faculty'): ?>
                    <a href="join_project.php" class="btn btn-primary">+ Supervise Project</a>
                <?php endif; ?>
                <a href="<?php echo htmlspecialchars($back_href); ?>" class="btn btn-outline">&larr; Return to Dashboard</a>
            </div>
        </div>

        <!-- Search and Filter Form Card -->
        <div class="card" style="margin-bottom: 1.75rem; background: var(--bg-card); padding: 1.25rem;">
            <form method="GET" action="view_all_projects.php">
                <div class="grid-4" style="margin-bottom: 1rem;">
                    <div>
                        <label class="form-label" for="search" style="font-size: 0.825rem;">Search Title or Topic</label>
                        <input 
                            type="text" 
                            id="search" 
                            name="search" 
                            class="form-control" 
                            placeholder="e.g. Machine Learning, NLP..." 
                            value="<?php echo htmlspecialchars($search); ?>"
                        >
                    </div>

                    <div>
                        <label class="form-label" for="domain" style="font-size: 0.825rem;">Research Domain</label>
                        <select id="domain" name="domain" class="form-select">
                            <option value="">-- All Domains --</option>
                            <?php foreach ($domains_list as $d_opt): ?>
                                <option value="<?php echo htmlspecialchars($d_opt); ?>" <?php echo ($domain === $d_opt) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($d_opt); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="form-label" for="status" style="font-size: 0.825rem;">Project Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">-- All Statuses --</option>
                            <option value="Active" <?php echo ($status === 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Pending" <?php echo ($status === 'Pending') ? 'selected' : ''; ?>>Pending Approval</option>
                            <option value="Completed" <?php echo ($status === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label" for="faculty_id" style="font-size: 0.825rem;">Faculty Supervisor</label>
                        <select id="faculty_id" name="faculty_id" class="form-select">
                            <option value="0">-- All Supervisors --</option>
                            <?php foreach ($faculty_list as $f_opt): ?>
                                <option value="<?php echo $f_opt['Faculty_ID']; ?>" <?php echo ($faculty_id === (int)$f_opt['Faculty_ID']) ? 'selected' : ''; ?>>
                                    Dr. <?php echo htmlspecialchars($f_opt['First_Name'] . ' ' . $f_opt['Last_Name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 0.5rem; justify-content: flex-end; align-items: center;">
                    <?php if (!empty($search) || !empty($domain) || !empty($status) || $faculty_id > 0): ?>
                        <a href="view_all_projects.php" class="btn btn-sm btn-outline">Clear Filters</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-sm btn-primary">
                        Apply Filters &rarr;
                    </button>
                </div>
            </form>
        </div>

        <!-- Projects Results Registry -->
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Project Title</th>
                            <th>Lead Researcher</th>
                            <th>Domain</th>
                            <th>Faculty Supervisor</th>
                            <th>Status</th>
                            <th>Action</th>
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
                                <td style="font-weight: 600; color: var(--text-main); max-width: 250px;">
                                    <?php echo htmlspecialchars($row['Title']); ?>
                                    <div style="font-size: 0.775rem; color: var(--text-muted); font-weight: normal; margin-top: 0.2rem;">
                                        <?php echo htmlspecialchars(mb_strimwidth($row['Description'], 0, 80, "...")); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($row['Stu_First'])): ?>
                                        <strong><?php echo htmlspecialchars($row['Stu_First'] . ' ' . $row['Stu_Last']); ?></strong>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">ID: <?php echo htmlspecialchars($row['Student_ID']); ?></div>
                                    <?php else: ?>
                                        Student ID: <?php echo htmlspecialchars($row['Student_ID']); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-low">&#128394; <?php echo htmlspecialchars($row['Domain']); ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($row['Fac_First'])): ?>
                                        <span style="font-weight: 600; color: var(--primary); font-size: 0.875rem;">
                                            Dr. <?php echo htmlspecialchars($row['Fac_First'] . ' ' . $row['Fac_Last']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 0.825rem;">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($row['Status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view_project.php?id=<?php echo $row['Project_ID']; ?>" class="btn btn-sm btn-primary">
                                        Workspace &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">&#128269;</div>
                <h2 class="empty-title">No Projects Found</h2>
                <p class="empty-description">
                    No research projects match your search or filter criteria. Try adjusting your search query or clear filters.
                </p>
                <div style="display: flex; gap: 0.5rem; justify-content: center; margin-top: 0.5rem;">
                    <a href="view_all_projects.php" class="btn btn-secondary">Clear Filters</a>
                    <?php if ($role === 'Student'): ?>
                        <a href="create_project.php" class="btn btn-primary">+ Propose New Project</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>