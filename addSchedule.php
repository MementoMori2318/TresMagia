<?php
include("db.php");
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit;
}

// Query to select subjects
$subjectSql = "SELECT subject_id, subject_name FROM subject";
$subjectResult = $conn->query($subjectSql);

// Query to select sections
$sectionSql = "SELECT section_id, section_name FROM section";
$sectionResult = $conn->query($sectionSql);

// Query to select users
$userSql = "SELECT id, role, cards_uid, name, year_section FROM users";
$userResult = $conn->query($userSql);
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
    <style>
        .btn-open-popup {
          
            padding: 12px 24px;
            font-size: 18px;
            background-color: green;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-open-popup:hover {
            background-color: #4caf50;
        }

        .overlay-container {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .popup-box {
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.4);
            width: 320px;
            text-align: center;
            opacity: 0;
            transform: scale(0.8);
            animation: fadeInUp 0.5s ease-out forwards;
        }

        .form-container {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            margin-bottom: 10px;
            font-size: 16px;
            color: #444;
            text-align: left;
        }

        .form-input {
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            width: 100%;
            box-sizing: border-box;
        }

        .btn-submit,
        .btn-close-popup {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .btn-submit {
            background-color: green;
            color: #fff;
        }

        .btn-close-popup {
            margin-top: 12px;
            background-color: #e74c3c;
            color: #fff;
        }

        .btn-submit:hover,
        .btn-close-popup:hover {
            background-color: #4caf50;
        }

        /* Keyframes for fadeInUp animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Animation for popup */
        .overlay-container.show {
            display: flex;
            opacity: 1;
        }
        
        .autocomplete-suggestions {
            display: none;
            border: 1px solid #e0e0e0;
            max-height: 150px;
            overflow-y: auto;
            background: #fff;
            position: absolute;
            z-index: 999;
            width: calc(100% - 2px);
        }
        .autocomplete-suggestion {
            padding: 8px;
            cursor: pointer;
        }
        .autocomplete-suggestion:hover {
            background: #f0f0f0;
        }
    </style>
</head>
<body class="sb-nav-fixed">
<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
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
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="mt-4">Add Schedule</h1>
                    <button class="btn-open-popup" onclick="togglePopupSubject()"> 
                       Add Subject 
                    </button>   
                    <button class="btn-open-popup" onclick="togglePopupSection()"> 
                       Add Section 
                    </button>   
                </div>
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-table me-1"></i>
                        Add Schedule
                    </div>
                    <div class="card-body">
                    <form action="action_page.php" method="POST">
                            <input type="hidden" name="add_schedule" value="1">
                            <div class="form-floating mb-3">
                                <select class="form-select form-control" aria-label="Default select example" id="inputDayOfWeek" name="day_of_week">
                                    <option value="Monday">Monday</option>
                                    <option value="Tuesday">Tuesday</option>
                                    <option value="Wednesday">Wednesday</option>
                                    <option value="Thursday">Thursday</option>
                                    <option value="Friday">Friday</option>
                                    <option value="Saturday">Saturday</option>
                                    <option value="Sunday">Sunday</option>
                                </select>
                                <label for="inputDayOfWeek">Day of Week</label>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input class="form-control" id="inputStartTime" name="inputStartTime" type="time" placeholder="8:00" value="" required/>
                                        <label for="inputStartTime">Start Time</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating mb-3 mb-md-0">
                                        <input class="form-control" id="inputEndTime" name="inputEndTime" type="time" placeholder="8:00" value="" required/>
                                        <label for="inputEndTime">End Time</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3 mb-md-0">
                                            <select class="form-select form-control" id="inputSubject" name="subject" required>
                                                <option value="">Select a subject</option>
                                                <?php
                                                if ($subjectResult->num_rows > 0) {
                                                    // Output data of each row
                                                    while($row = $subjectResult->fetch_assoc()) {
                                                        echo '<option value="' . $row["subject_id"] . '">' . $row["subject_name"] . '</option>';
                                                    }
                                                } else {
                                                    echo '<option value="">No subjects available</option>';
                                                }
                                                ?>
                                            </select>
                                            <label for="inputSubject">Subject</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating mb-3 mb-md-0">
                                            <select class="form-select form-control" id="inputSection" name="section" required>
                                                <option value="">Select a section</option>
                                                <?php
                                                if ($sectionResult->num_rows > 0) {
                                                    // Output data of each row
                                                    while($row = $sectionResult->fetch_assoc()) {
                                                        echo '<option value="' . $row["section_id"] . '">' . $row["section_name"] . '</option>';
                                                    }
                                                } else {
                                                    echo '<option value="">No sections available</option>';
                                                }
                                                ?>
                                            </select>
                                            <label for="inputSection">Section</label>
                                        </div>
                                    </div>
                                </div>
                                 <!-- New User Selection Table -->
                        <h3 class="mt-4">Pick Users to Use this Schedule</h3>
                       
                            <table id="datatablesSimple">
                                <thead>
                                    <tr>
                                        <th data-sortable="false">
                                            <input type="checkbox" id="checkAll" style="position: absulote; z-index: 1; margin-right: 5px;">
                                            Select All
                                        </th>
                                        <th>User Role</th>
                                        <th>Card UID</th>
                                        <th>Name</th>
                                        <th>Year Course & Section</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($userResult->num_rows > 0) {
                                        while ($row = $userResult->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td><input type='checkbox' name='selected_users[]' value='{$row['id']}' class='user-checkbox'></td>";
                                            echo "<td>" . htmlspecialchars($row['role']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['cards_uid']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['year_section']) . "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5'>No users available</td></tr>";
                                    }
                                    ?>
                                </tbody>
                                
                            </table>
                            
                            <div class="mt-4 mb-0">
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-block">Add Schedule</button>
                                </div>
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

<!-- Popup Overlay -->
<div id="popupSubject" class="overlay-container">
    <div class="popup-box">
        <h2 style="color: green;">Add Subject</h2>
        <form action="action_page.php" method="POST" class="form-container">
        <input type="hidden" name="add_subject" value="1">
            <label class="form-label" for="subject">Subject Name:</label>
            <input class="form-input" type="text" placeholder="Enter Subject Name" id="subject_name" name="subject_name" required>
           
            <button class="btn-submit" type="submit">Submit</button>
        </form>
        <button class="btn-close-popup" onclick="togglePopupSubject()">Close</button>
    </div>
</div>
<div id="popupSection" class="overlay-container">
    <div class="popup-box">
        <h2 style="color: green;">Add Section</h2>
        <form action="action_page.php" method="POST" class="form-container">
        <input type="hidden" name="add_section" value="1">
            <label class="form-label" for="sectionName">Section Name:</label>
            <input class="form-input" type="text" placeholder="Enter Section Name" id="section_name" name="section_name" required>

            <label class="form-label" for="course">Course Name:</label>
            <select class="form-input" id="course" name="course" required>
                <option value="BSIT">BSIT</option>
                <option value="BSCS">BSCS</option>
                <option value="BSIS">BSIS</option>
            </select>

            <label class="form-label" for="year">Year:</label>
            <select class="form-input" id="year" name="year" required>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
            </select>

            <label class="form-label" for="section">Section:</label>
            <select class="form-input" id="section" name="section" required>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="C">C</option>
                <option value="D">D</option>
                <option value="E">E</option>
                <option value="F">F</option>
                <option value="G">G</option>
                <option value="H">H</option>
            </select>                            
            <button class="btn-submit" type="submit">Submit</button>
        </form>
        <button class="btn-close-popup" onclick="togglePopupSection()">Close</button>
    </div>
</div>
<script>
function togglePopupSubject() {
    const overlay = document.getElementById('popupSubject');
    overlay.classList.toggle('show');  
}

function togglePopupSection() {
    const overlay = document.getElementById('popupSection');   
    overlay.classList.toggle('show');
}

document.getElementById('checkAll').addEventListener('change', function() {
        var checkboxes = document.querySelectorAll('.user-checkbox');
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
</body>
</html>
