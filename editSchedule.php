<?php
include("db.php");
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit;
}

// Get the schedule ID from the URL parameter
$scheduleId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch the schedule data from the database
$sql = "SELECT * FROM schedules WHERE id = $scheduleId";
$result = $conn->query($sql);
$schedule = $result->fetch_assoc();

// Fetch all subjects
$subjectSql = "SELECT * FROM subject";
$subjects = $conn->query($subjectSql);

// Fetch all sections
$sectionSql = "SELECT * FROM section";
$sections = $conn->query($sectionSql);

// Fetch users assigned to the current schedule
$assignedUsersSql = "
    SELECT u.id, u.role, u.cards_uid, u.name, u.year_section 
    FROM users u
    JOIN user_schedules us ON u.id = us.users_id
    WHERE us.schedules_id = $scheduleId";
$assignedUsersResult = $conn->query($assignedUsersSql);

// Fetch all users except those with the 'Admin' role
$allUsersSql = "SELECT * FROM users WHERE role != 'Admin'";
$allUsersResult = $conn->query($allUsersSql);
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });

            // Check if success message exists in URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const successMessage = urlParams.get('success_message');

            if (successMessage) {
                Toast.fire({
                    icon: 'success',
                    title: successMessage
                }).then(() => {
                    // Remove the success_message parameter from the URL
                    history.replaceState(null, null, window.location.pathname);
                });
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Check if error message exists in URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const errorMessage = urlParams.get('error_message');

            if (errorMessage) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage,
                }).then(() => {
                    // Remove the error_message parameter from the URL
                    history.replaceState(null, null, window.location.pathname);
                });
            }
        });
    </script>
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
                <div class="small">Logged in as: <?php echo $_SESSION['name']; ?></div>
            </div>
        </nav>
    </div>
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="mt-4">Edit Schedule</h1>
                </div>
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-table me-1"></i>
                        Edit Schedule
                    </div>
                    <div class="card-body">
                        <form action="editSchedule_action.php" method="POST">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($schedule['id']); ?>">
                            <div class="form-floating mb-3">
                                <select class="form-select form-control" aria-label="Default select example" id="day_of_week" name="day_of_week" required>
                                    <option value="Monday" <?php if ($schedule['day_of_week'] == 'Monday') echo 'selected'; ?>>Monday</option>
                                    <option value="Tuesday" <?php if ($schedule['day_of_week'] == 'Tuesday') echo 'selected'; ?>>Tuesday</option>
                                    <option value="Wednesday" <?php if ($schedule['day_of_week'] == 'Wednesday') echo 'selected'; ?>>Wednesday</option>
                                    <option value="Thursday" <?php if ($schedule['day_of_week'] == 'Thursday') echo 'selected'; ?>>Thursday</option>
                                    <option value="Friday" <?php if ($schedule['day_of_week'] == 'Friday') echo 'selected'; ?>>Friday</option>
                                    <option value="Saturday" <?php if ($schedule['day_of_week'] == 'Saturday') echo 'selected'; ?>>Saturday</option>
                                    <option value="Sunday" <?php if ($schedule['day_of_week'] == 'Sunday') echo 'selected'; ?>>Sunday</option>
                                </select>
                                <label for="inputDayOfWeek">Day of Week</label>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="time" class="form-control" id="start_time" name="start_time" value="<?php echo htmlspecialchars($schedule['start_time']); ?>" required>
                                        <label for="inputStartTime">Start Time</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating mb-3 mb-md-0">
                                        <input type="time" class="form-control" id="end_time" name="end_time" value="<?php echo htmlspecialchars($schedule['end_time']); ?>" required>
                                        <label for="inputEndTime">End Time</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3 mb-md-0">
                                        <select class="form-select form-control" id="subject_id" name="subject_id" required>
                                            <option value="">Select a subject</option>
                                            <?php while ($subject = $subjects->fetch_assoc()) { ?>
                                                <option value="<?php echo $subject['subject_id']; ?>" <?php if ($subject['subject_id'] == $schedule['subject_id']) echo 'selected'; ?>>
                                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                        <label for="inputSubject">Subject</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating mb-3 mb-md-0">
                                        <select class="form-select form-control" id="section_id" name="section_id" required>
                                            <option value="">Select a section</option>
                                            <?php while ($section = $sections->fetch_assoc()) { ?>
                                                <option value="<?php echo $section['section_id']; ?>" <?php if ($section['section_id'] == $schedule['section_id']) echo 'selected'; ?>>
                                                    <?php echo htmlspecialchars($section['section_name']); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                        <label for="inputSection">Section</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Table with Shared ID but Toggle Body Content -->
                            <h4>Users of this Schedule</h4>
                            <table id="datatablesSimple">
                                <thead>
                                    <tr>
                                        <th data-sortable="false">
                                            <input type="checkbox" id="checkAll">
                                            Select All
                                        </th>
                                        <th>User Role</th>
                                        <th>Card UID</th>
                                        <th>Name</th>
                                        <th>Year Course & Section</th>
                                    </tr>
                                </thead>
                                <tbody id="assignedUsersBody">
                                    <?php
                                    if ($assignedUsersResult->num_rows > 0) {
                                        while ($row = $assignedUsersResult->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td><input type='checkbox' name='selected_users[]' value='{$row['id']}' class='user-checkbox' checked></td>";
                                            echo "<td>" . htmlspecialchars($row['role']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['cards_uid']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['year_section']) . "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5'>No users assigned to this schedule</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>

                            <!-- Submit and Cancel Buttons -->
                            <div class="d-flex justify-content-between mt-4 mb-0">
                                <button type="submit" class="btn btn-primary btn-block">Edit Schedule</button>
                                <button type="button" class="btn btn-danger btn-block" onclick="confirmDelete(<?php echo $scheduleId; ?>)">Delete Schedule</button>
                                <a href="schedule.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>

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

<script>
document.getElementById('toggleAssignedUsers').addEventListener('change', function() {
    var assignedUsersBody = document.getElementById('assignedUsersBody');
    var allUsersBody = document.getElementById('allUsersBody');

    if (this.checked) {
        assignedUsersBody.style.display = 'table-row-group';  // Show assigned users content
        allUsersBody.style.display = 'none';  // Hide all users content
    } else {
        assignedUsersBody.style.display = 'none';  // Hide assigned users content
        allUsersBody.style.display = 'table-row-group';  // Show all users content
    }
});

document.getElementById('checkAll').addEventListener('change', function() {
    var checkboxes = document.querySelectorAll('#assignedUsersBody .user-checkbox, #allUsersBody .user-checkbox');
    for (var checkbox of checkboxes) {
        checkbox.checked = this.checked;
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="js/scripts.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
<script src="assets/demo/chart-area-demo.js"></script>
<script src="assets/demo/chart-bar-demo.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script src="js/datatables-simple-demo.js"></script>
<script>
    function confirmDelete(scheduleId) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'You will not be able to recover this schedule!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'No, cancel!',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Proceed with deletion
                window.location.href = `scheduleDelete.php?action=delete&id=${scheduleId}`;
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                Swal.fire(
                    'Cancelled',
                    'Your schedule is safe :)',
                    'error'
                );
            }
        });
    }
</script>
</body>
</html>
