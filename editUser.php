<?php
include("db.php");
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit;
}

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch user data based on user ID
$query_user = "SELECT * FROM users WHERE id = ?";
$stmt_user = $conn->prepare($query_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();

if (!$user) {
    echo "User not found!";
    exit;
}

$stmt_user->close();

// Fetch all available schedules with subject and section names
$sql_schedules = "SELECT s.id, s.day_of_week, s.start_time, s.end_time, sub.subject_name, sec.section_name 
                  FROM schedules s
                  JOIN subject sub ON s.subject_id = sub.subject_id
                  JOIN section sec ON s.section_id = sec.section_id";
$result_schedules = $conn->query($sql_schedules);

// Fetch user's existing schedules
$query_user_schedules = "SELECT schedules_id FROM user_schedules WHERE users_id = ?";
$stmt_user_schedules = $conn->prepare($query_user_schedules);
$stmt_user_schedules->bind_param("i", $user_id);
$stmt_user_schedules->execute();
$result_user_schedules = $stmt_user_schedules->get_result();

$user_schedules = [];
while ($row = $result_user_schedules->fetch_assoc()) {
    $user_schedules[] = $row['schedules_id'];
}
$stmt_user_schedules->close();
// Error handling and reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Edit User - Tresmagia SmartLock</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
   
</head>
<body class="sb-nav-fixed">
<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <!-- Navbar Brand -->
    <!-- Sidebar Toggle -->
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
    <!-- Navbar Search -->
    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0"></form>
    <!-- Navbar -->
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
                <div class="small">Logged in as: <?php echo $_SESSION['name']; ?></div>
            </div>
        </nav>
    </div>
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="mt-4">Edit User</h1>
                </div>
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-user-edit me-1"></i>
                        Edit User Details
                    </div>
                    <div class="card-body">
                    <form action="action_page.php" method="POST">
    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
    <!-- User Details Section -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="form-floating mb-3 mb-md-0">
                <input class="form-control" id="inputName" name="name" type="text" placeholder="Enter Name" value="<?php echo htmlspecialchars($user['name']); ?>" required/>
                <label for="inputName">Name</label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-floating">
                <select class="form-select form-control" aria-label="Default select example" id="inputUserRole" name="userRole" required>
                    <option value="student" <?php if ($user['role'] == 'Student') echo 'selected'; ?>>Student</option>
                    <option value="staff" <?php if ($user['role'] == 'Staff') echo 'selected'; ?>>Staff</option>
                    <option value="faculty" <?php if ($user['role'] == 'Faculty') echo 'selected'; ?>>Faculty</option>
                    <option value="admin" <?php if ($user['role'] == 'Admin') echo 'selected'; ?>>Admin</option>
                </select>
                <label for="inputUserRole">User Type</label>
            </div>
        </div>
    </div>
    <div class="form-floating mb-3">
        <input class="form-control" id="inputUserId" name="userId" type="text" placeholder="C21102307" value="<?php echo htmlspecialchars($user['user_id']); ?>" required/>
        <label for="inputUserId">User ID</label>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="form-floating mb-3">
                <input class="form-control" id="inputCardUid" name="inputCardUid" type="text" placeholder="Card UID" value="<?php echo htmlspecialchars($user['cards_uid']); ?>" required/>
                <label for="inputCardUid">Tap Card</label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-floating mb-3 mb-md-0">
                <input class="form-control" id="inputYearSection" name="inputYearSection" type="text" placeholder="BSIT - 3C" value="<?php echo htmlspecialchars($user['year_section']); ?>" />
                <label for="inputYearSection">Year & Section</label>
            </div>
        </div>
    </div>
    <div class="form-floating mb-3">
        <input class="form-control" id="inputEmail" name="email" type="email" placeholder="name@example.com" value="<?php echo htmlspecialchars($user['email']); ?>" required/>
        <label for="inputEmail">Email address</label>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="form-floating mb-3 mb-md-0">
                <input class="form-control" id="inputPassword" name="password" type="password" placeholder="Create a password" value="<?php echo htmlspecialchars($user['password']); ?>" />
                <label for="inputPassword">Password</label>
            </div>
        </div>
    </div>

    <!-- User Schedules Section -->
    <div class="row mb-3">
    <label class="col-form-label">User Schedules:</label>
    <div class="col">
        <div class="form-check">
        <div class="control">
                <input class="form-control mb-3" type="text" placeholder="Search" id="search" />
                <span class="icon is-small is-left">
                    <span class="searchIcon"></span>
                </span>
            </div>
            <?php
            if ($result_schedules->num_rows > 0) {
                while ($row = $result_schedules->fetch_assoc()) {
                    $checked = in_array($row['id'], $user_schedules) ? 'checked' : '';

                    // Convert start and end times to 12-hour format
                    $start_time = new DateTime($row['start_time']);
                    $end_time = new DateTime($row['end_time']);
                    $formatted_start_time = $start_time->format('g:i A');
                    $formatted_end_time = $end_time->format('g:i A');

                    echo '<div class="form-check">
                            <input class="form-check-input" type="checkbox" value="' . $row['id'] . '" id="schedule_' . $row['id'] . '" name="userSchedule[]" ' . $checked . '>
                            <label class="form-check-label" for="schedule_' . $row['id'] . '">
                                ' . htmlspecialchars($row['day_of_week']) . ' ' . htmlspecialchars($formatted_start_time) . ' - ' . htmlspecialchars($formatted_end_time) . ' (Subject: ' . htmlspecialchars($row['subject_name']) . ', Section: ' . htmlspecialchars($row['section_name']) . ')
                            </label>
                        </div>';
                }
            } else {
                echo '<p>No schedules available</p>';
            }
            ?>

        </div>
    </div>
</div>

    <!-- Submit and Delete Buttons -->
    <div class="mt-4 mb-0">
        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-block">Edit User</button>
        </div>
    </div>
   
</form>

                    </div>
                </div>
            </div>
        </main>
        <footer class="py-4 bg-light mt-auto">
            <div class="container-fluid px-4">
                <div class="d-flex align-items-center justify-content-between small">
                    <div class="text-muted">Copyright &copy; Tresmagia 2024</div>
                </div>
            </div>
        </footer>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/js/all.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="js/scripts.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script src="js/datatables-simple-demo.js"></script>

<script>
 
document.addEventListener('DOMContentLoaded', function() {
        var inputUserRole = document.getElementById('inputUserRole');
        var inputYearSection = document.getElementById('inputYearSection');
        var inputPassword = document.getElementById('inputPassword');

        function handleUserRoleChange() {
            var userRole = inputUserRole.value;

            if (userRole === 'student') {
                inputYearSection.disabled = false;
            } else {
                inputYearSection.value = "";  // Set to empty string when not student
                inputYearSection.disabled = true;
            }

            if (userRole === 'admin') {
                inputPassword.disabled = false;
            } else {
                inputPassword.disabled = true;
            }
        }

        inputUserRole.addEventListener('change', handleUserRoleChange);

        // Initial call to set the correct state on page load
        handleUserRoleChange();
    });
    const search = document.getElementById("search");
    const labels = document.querySelectorAll("#checkboxes2 > label");

    search.addEventListener("input", () => {
        const searchValue = search.value.toLowerCase();
        labels.forEach((element) => {
            const labelContent = element.textContent.toLowerCase();
            element.style.display = labelContent.includes(searchValue) ? "block" : "none";
        });
    });
</script>

</body>
</html>
