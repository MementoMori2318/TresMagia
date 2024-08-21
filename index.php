<?php
include("db.php");
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit;
}

$count_query = "SELECT COUNT(*) AS user_count FROM users";
$count_result = mysqli_query($conn, $count_query);
$user_count = mysqli_fetch_assoc($count_result)['user_count'];

$student_count_query = "SELECT COUNT(*) AS student_count FROM users WHERE role = 'Student'";
$student_count_result = mysqli_query($conn, $student_count_query);
$student_count = mysqli_fetch_assoc($student_count_result)['student_count'];

$staff_count_query = "SELECT COUNT(*) AS staff_count FROM users WHERE role IN ('Faculty', 'Admin', 'Staff')";
$staff_count_result = mysqli_query($conn, $staff_count_query);
$staff_count = mysqli_fetch_assoc($staff_count_result)['staff_count'];

$currentDayOfWeek = date('l'); // Full textual representation of the day (e.g., Monday)
$currentTime = date('H:i:s'); 

$scheduleQuery = "SELECT s.id, s.day_of_week, s.start_time, s.end_time, su.subject_name, se.section_name
                 FROM schedules s
                 INNER JOIN subject su ON s.subject_id = su.subject_id
                 INNER JOIN section se ON s.section_id = se.section_id
                 WHERE s.day_of_week = ? 
                 AND ? BETWEEN s.start_time AND s.end_time
                 LIMIT 1";

$stmt = $conn->prepare($scheduleQuery);
$stmt->bind_param("ss", $currentDayOfWeek, $currentTime);
$stmt->execute();
$result = $stmt->get_result();

$logDate = isset($_POST['log_date']) ? $_POST['log_date'] : null;
            
if ($logDate) {
    $logsQuery = "SELECT l.id, l.log_type, l.event_type, l.event_description, l.date_logged, u.name, u.role, u.cards_uid 
                  FROM logs l
                  INNER JOIN users u ON l.users_id = u.id
                  WHERE DATE(l.date_logged) = ?";
    $stmt = $conn->prepare($logsQuery);
    $stmt->bind_param("s", $logDate);
} else {
    // Fetch all logs or the most recent logs closest to the current date
    $logsQuery = "SELECT l.id, l.log_type, l.event_type, l.event_description, l.date_logged, u.name, u.role, u.cards_uid 
                  FROM logs l
                  INNER JOIN users u ON l.users_id = u.id
                  ORDER BY l.date_logged DESC
                  LIMIT 100"; // Adjust the LIMIT as needed
    $stmt = $conn->prepare($logsQuery);
}

$stmt->execute();
$logsResult = $stmt->get_result();
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
<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark"s>
    <!-- Navbar Brand-->
    
    <!-- Sidebar Toggle-->
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
    <!-- Navbar Search-->
    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
       
    </form>
    <!-- Navbar-->
    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user fa-fw"></i></a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                
                <li><a class="dropdown-item" href="login.php">Logout</a></li>
            </ul>
        </li>
    </ul>
</nav>
<br><br>
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
                    </a><a class="nav-link" href="attendance.php">
                        <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                        Attendance
                    </a></a>  <a class="nav-link" href="schedule.php">
                        <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                       Manage Schedules
                    </a>
                </div>
            </div>
            <div class="sb-sidenav-footer">
                  <div class="small">Logged in as: <?php echo $_SESSION['name']; ?></div>
               
            </div>
        </nav>
    </div>
    <div id="layoutSidenav_content">
        <main>
        <div class="container-fluid px-4">

<!-- Page Heading -->

    <h1>Dashboard</h1>


<!-- Content Row -->
<div class="row">
    <!-- Active Card Example -->
    <?php
    if ($result->num_rows > 0) {
        // Fetch the current schedule
        $row = $result->fetch_assoc();
    
        $id = $row['id'];
        $dayOfWeek = $row['day_of_week'];
        $startTime = $row['start_time'];
        $endTime = $row['end_time'];
        $subjectName = $row['subject_name'];
        $sectionName = $row['section_name'];
    
        // Convert start time to 12-hour format
        $startTimeObj = new DateTime($startTime);
        $startTimeFormatted = $startTimeObj->format('h:i A');
    
        // Convert end time to 12-hour format
        $endTimeObj = new DateTime($endTime);
        $endTimeFormatted = $endTimeObj->format('h:i A');
    
        // Display the current schedule information in the HTML structure
        echo '<div class="col-xl-3 col-md-8 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body"> 
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Active</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">' . $dayOfWeek . '</div>    
                                <div class="h5 mb-0 font-weight-bold text-gray-800">' . $subjectName . '</div>
                                <div class="h6 mb-0 text-gray-600">' . $sectionName . '</div>
                                <div class="h6 mb-0 text-gray-600">' . $startTimeFormatted . ' - ' . $endTimeFormatted . '</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';
    } else {
        
        echo '<div class="col-xl-3 col-md-8 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body"> 
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            No schedule found for the current time.</div>
                       
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>';
    }
?>

    <!-- Number of Users Card Example -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Number of Users</div>
                            <div class="h1 mb-0 font-weight-bold text-gray-800"><?php echo $user_count; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Number of Students Card Example -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Number of Students</div>
                            <div class="h1 mb-0 font-weight-bold text-gray-800"><?php echo $student_count; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-graduation-cap fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Number of Faculty Card Example -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Number of Faculty</div>
                        <div class="h1 mb-0 font-weight-bold text-gray-800"><?php echo $staff_count; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

<div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-table me-1"></i>
                        Recently Logged-in Users
                        <form method="POST" class="d-inline-block float-end">
                            <input type="date" name="log_date" class="form-control form-control-sm me-2" 
                                value="<?php echo isset($_POST['log_date']) ? htmlspecialchars($_POST['log_date']) : ''; ?>" 
                                onchange="this.form.submit()" style="width: auto;">
                        </form>
                    </div>
                    <div class="card-body">
                        
                        <table id="datatablesSimple">
                            <thead>
                                <tr>
                                    <th>Log Type</th>
                                    <th>Event Type</th>
                                    <th>Description</th>
                                    <th>Date Logged</th>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Card UID</th>
                                </tr>
                            </thead>
                            
                            <tbody>
                            <?php
                                if ($logsResult->num_rows > 0) {
                                    while ($row = mysqli_fetch_assoc($logsResult)) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($row['log_type']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['event_type']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['event_description']) . '</td>';
                                        echo '<td>' . htmlspecialchars(date("F j, Y, g:i a", strtotime($row['date_logged']))) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['role']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['cards_uid']) . '</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="7">No logs found.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    
                
<!-- End of Main Content -->
            
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="js/scripts.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
<script src="assets/demo/chart-area-demo.js"></script>
<script src="assets/demo/chart-bar-demo.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script src="js/datatables-simple-demo.js"></script>
</body>
</html>
