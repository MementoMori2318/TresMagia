<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("db.php");
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit;
}

// Get current day and time
$currentDayOfWeek = date('l'); // Full textual representation of the day (e.g., Monday)
$currentTime = date('H:i:s'); 

// Handle schedule selection from dropdown
$selectedScheduleId = isset($_POST['schedule']) ? $_POST['schedule'] : null;

// Query to get the current schedule
$scheduleQuery = "SELECT s.id, s.day_of_week, s.start_time, s.end_time, su.subject_name, se.section_name
                 FROM schedules s
                 INNER JOIN subject su ON s.subject_id = su.subject_id
                 INNER JOIN section se ON s.section_id = se.section_id
                 WHERE s.day_of_week = ? 
                 AND ? BETWEEN s.start_time AND s.end_time
                 LIMIT 1";

$stmt = $conn->prepare($scheduleQuery);

if ($stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}

$stmt->bind_param("ss", $currentDayOfWeek, $currentTime);
$stmt->execute();
$scheduleResult = $stmt->get_result();
$currentScheduleId = null;
$scheduleInfo = null;

if ($scheduleResult->num_rows > 0) {
    $scheduleInfo = $scheduleResult->fetch_assoc();
    $currentScheduleId = $scheduleInfo['id'];
}

// If no schedule is selected, use the current schedule
$activeScheduleId = $selectedScheduleId ? $selectedScheduleId : $currentScheduleId;

// Default to current date if no date is selected
$selectedDate = isset($_POST['attendance_date']) ? $_POST['attendance_date'] : date('Y-m-d');
$selectedScheduleId = isset($_POST['schedule']) && !empty($_POST['schedule']) ? $_POST['schedule'] : $currentScheduleId;

// Adjust the query to filter by the selected date and schedule
$attendanceQuery = "
    SELECT 
        a.date,
        a.time_in,
        a.time_out,
        u.name,
        u.id,
        u.user_id,
        CONCAT(u.year_section, ' - ') AS year_course_section
    FROM 
        attendance a
        JOIN users u ON a.users_id = u.id
    WHERE 
        a.schedules_id = ? 
        AND a.date = ?
";

$attendanceStmt = $conn->prepare($attendanceQuery);

if ($attendanceStmt === false) {
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}

$attendanceStmt->bind_param("is", $selectedScheduleId, $selectedDate);
$attendanceStmt->execute();
$attendanceResult = $attendanceStmt->get_result();

// Query to get all schedules for the dropdown
$scheduleDropdownQuery = "SELECT s.id, CONCAT(s.day_of_week, ' ', s.start_time, '-', s.end_time, ' ', su.subject_name, ' ', se.section_name) AS schedule_info
                          FROM schedules s
                          INNER JOIN subject su ON s.subject_id = su.subject_id
                          INNER JOIN section se ON s.section_id = se.section_id";

$dropdownResult = $conn->query($scheduleDropdownQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Tresmagia SmartLock</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body class="sb-nav-fixed">
<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <!-- Navbar Brand-->
    <!-- Sidebar Toggle-->
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
    <!-- Navbar Search-->
    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0"></form>
    <!-- Navbar-->
    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user fa-fw"></i></a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
            </ul>
        </li>
    </ul>
</nav>
<div id="layoutSidenav">
    <div id="layoutSidenav_nav">
        <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
            <div class="sb-sidenav-menu">
                <div class="nav">
                    <div class="sb-sidenav-menu-heading">Core</div>
                    <a class="nav-link" href="index.php">
                        <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                        Dashboard
                    </a>
                    <div class="sb-sidenav-menu-heading">Addons</div>
                    <a class="nav-link" href="usersList.php">
                        <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                       Manage Users
                    </a>
                    <a class="nav-link" href="studentList.php">
                        <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                        Student
                    </a>
                    <a class="nav-link" href="teacherList.php">
                        <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                        Faculty
                    </a>
                    <a class="nav-link" href="attendance.php">
                        <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                        Attendance
                    </a>
                    <a class="nav-link" href="schedule.php">
                        <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                       Manage Schedules
                    </a>
                </div>
            </div>
            <div class="sb-sidenav-footer">
                  <div class="small">Logged in as: <?php echo htmlspecialchars($_SESSION['name']); ?></div>
            </div>
        </nav>
    </div>
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="mt-4">Student Attendance</h1>
                
                <!-- Wrap the form inside a div and use Bootstrap classes to adjust size and spacing -->
                <div class="d-inline-block me-2">
                <form method="post" action="">
                    <div class="form-group mb-0 d-flex align-items-center">
                        <!-- Schedule Dropdown -->
                        <select id="scheduleSelect" name="schedule" class="form-select form-select-sm me-2" onchange="this.form.submit()" style="width: auto;">
                            <option value="">-- Show Current Schedule --</option>
                            <?php
                            if ($dropdownResult->num_rows > 0) {
                                while ($row = $dropdownResult->fetch_assoc()) {
                                    $selected = ($row['id'] == $activeScheduleId) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($row['id']) . "' $selected>" . htmlspecialchars($row['schedule_info']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                        
                        <!-- Date Input -->
                        <input type="date" name="attendance_date" class="form-control form-control-sm me-2" value="<?php echo isset($_POST['attendance_date']) ? htmlspecialchars($_POST['attendance_date']) : ''; ?>" onchange="this.form.submit()" style="width: auto;">
                    </div>
                </form>
                </div>

                <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#generateReportModal">
                    <i class="fas fa-download fa-sm text-white-50"></i> Generate Attendance Report
                </a>
            </div>
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-table me-1"></i>
                        Student Attendance
                    </div>
                   
                    <div class="card-body">
                        <table id="datatablesSimple">
                            <thead>
                                <tr>
                                    <th>NAME</th>
                                    <th>STUDENT ID</th>
                                    <th>YEAR COURSE & SECTION</th>
                                    <th>DATE</th>
                                    <th>TIME IN</th>
                                    <th>TIME OUT</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                                if ($attendanceResult->num_rows > 0) {
                                    while ($row = $attendanceResult->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['year_course_section']) . "</td>";
                                        echo "<td>" . date('F d, Y', strtotime($row['date'])) . "</td>";
                                        echo "<td>" . date('h:i A', strtotime($row['time_in'])) . "</td>";
                                        echo "<td>" . ($row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '') . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6'>No attendance records found for the selected schedule.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
        <footer class="py-4 bg-light mt-auto">
            <div class="container-fluid px-4">
                <div class="d-flex align-items-center justify-content-between small">
                    <div class="text-muted">Copyright &copy; SmartLock 2024</div>
                    <div>
                        <a href="aboutus.php">About Us</a>
                        &middot;
                        <a href="#">Privacy Policy</a>
                        &middot;
                        <a href="#">Terms &amp; Conditions</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>
<!-- Modal HTML -->
<div class="modal fade" id="generateReportModal" tabindex="-1" aria-labelledby="generateReportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="generateReportModalLabel">Generate Attendance Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Would you like to download the Attendance report for this schedule</p>
                <form id="reportForm" method="post" action="generateReport.php">
                    <div class="mb-3">
                        <!-- <label for="reportEmail" class="form-label">Email to:</label> -->
                        <!-- <input type="email" class="form-control" id="reportEmail" name="report_email" placeholder="Enter email address"> -->
                    </div>
                    <input type="hidden" name="schedule_id" value="<?php echo htmlspecialchars($selectedScheduleId); ?>">
                    <input type="hidden" name="attendance_date" value="<?php echo htmlspecialchars($selectedDate); ?>">
                </form>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary" form="reportForm" name="action" value="download">Download Now</button>
                <!-- <button type="submit" class="btn btn-secondary" form="reportForm" name="action" value="email">Email Report</button> -->
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="js/scripts.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
<script src="assets/demo/chart-area-demo.js"></script>
<script src="assets/demo/chart-bar-demo.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script src="js/datatables-simple-demo.js"></script>
</body>
</html>
