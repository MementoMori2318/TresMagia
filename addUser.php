<?php
include("db.php");
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit;
}
$sql = "SELECT s.id, s.day_of_week, s.start_time, s.end_time, sub.subject_name, sec.section_name 
        FROM schedules s
        JOIN subject sub ON s.subject_id = sub.subject_id
        JOIN section sec ON s.section_id = sec.section_id";
$result = $conn->query($sql);

$searchTerm = '';
if (isset($_POST['search'])) {
    $searchTerm = $_POST['search'];
}


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
                });
            }
        });
    
        function updateCardData() {
    fetch('card_data.txt?' + new Date().getTime()) // Adding a timestamp to prevent caching
        .then(response => response.text()) // Parse as text
        .then(data => {
            if (data.trim() && data.trim() !== "None") {
                document.getElementById('inputCardUid').value = data.trim();
                console.log("Card ID:", data.trim());

                // Check if the card ID is already registered
                fetch('check_card.php?cards_uid=' + data.trim())
                    .then(response => response.json())
                    .then(result => {
                        if (!result.isRegistered) {
                            var unregisteredCardModal = new bootstrap.Modal(document.getElementById('unregisteredCardModal'));
                            unregisteredCardModal.show();
                        }
                    })
                    .catch(error => console.error("Error checking card:", error));

                setTimeout(clearCardData, 5000);  // Clear the card data after 5 seconds
            }
            setTimeout(updateCardData, 100); // Repeat every 100ms
        })
        .catch(error => {
            console.error("Error fetching card data:", error);
            setTimeout(updateCardData, 100); // Repeat every 100ms even if there is an error
        });
}


    function clearCardData() {
        fetch('addUser.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'clear_card_data=true'
        }).then(response => {
            if (response.ok) {
                console.log("Card data cleared.");
            }
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
        updateCardData();
    });

// Call updateCardData to start the loop
updateCardData();
    </script>
    <style>
    .form-select {
        height: auto;
    }
    #inputSchedule {
        display: block;
        width: 100%;
        height: auto;
        padding: .375rem 1.75rem .375rem .75rem;
        font-size: 1rem;
        font-weight: 400;
        line-height: 1.5;
        color: #495057;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid #ced4da;
        border-radius: .25rem;
        transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
    }
</style>
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
                <h1 class="mt-4">Add User</h1>
                <ol class="breadcrumb mb-4">
                    <li class="breadcrumb-item active">Tap ID to the RFID reader to register your the ID </li>
                </ol>
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-table me-1"></i>
                        Add User
                    </div>
                    <div class="card-body">
                   

    <form action="action_page.php" method="POST">
    <input type="hidden" name="add_user" value="1">
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="form-floating mb-3 mb-md-0">
                <input class="form-control" id="inputName" name="name" type="text" placeholder="Enter Name" required/>
                <label for="inputName">Name</label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-floating">
                <select class="form-select form-control" aria-label="Default select example" id="inputUserRole" name="userRole" required>
                    <option value="student">Student</option>
                    <option value="staff">Staff</option>
                    <option value="faculty">Faculty</option>
                    <option value="admin">Admin</option>
                </select>
                <label for="inputUserRole">User Type</label>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="form-floating mb-3">
                <input class="form-control" id="inputUserId" name="userId" type="text" placeholder="C21102307" required/>
                <label for="inputUserId">User ID</label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-floating mb-3">
                <input class="form-control" id="inputCardUid" name="inputCardUid" type="text" placeholder="Card UID" required/>
                <label for="inputCardUid">Tap Card</label>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="form-floating mb-3">               
                <select class="form-select form-control" aria-label="Default select example" id="inputYear" name="inputYear" required>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                </select>
                <label for="inputYear">Year</label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-floating mb-3 mb-md-0">     
                <select class="form-select form-control" aria-label="Default select example" id="inputCourse" name="inputCourse" required>
                    <option value="BSIT">BSIT</option>
                    <option value="BSIS">BSIS</option>
                    <option value="BSCS">BSCS</option>
                </select>
                <label for="inputCourse">Course</label>
            </div>
        </div>
    </div>
    <div class="form-floating mb-3">
        <input class="form-control" id="inputEmail" name="email" type="email" placeholder="name@example.com" required/>
        <label for="inputEmail">Email address</label>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="form-floating mb-3 mb-md-0">
                <input class="form-control" id="inputPassword" name="password" type="password" placeholder="Create a password"/>
                <label for="inputPassword">Password</label>
            </div>
        </div>
    </div>
    <div class="form-floating mb-3">
    <div id="checkboxes2">
    <div class="control">
        <input class="input form-control mb-3" type="text" placeholder="Search for Schedules" id="search" />
        <span class="icon is-small is-left">
            <span class="searchIcon"></span>
        </span>
    </div>
    <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $start_time_12hr = date("g:i A", strtotime($row["start_time"]));
                $end_time_12hr = date("g:i A", strtotime($row["end_time"]));
                echo '<label for="schedule_' . $row["id"] . '" class="select_label d-block mb-2">
                        <input type="checkbox" class="me-2" value="' . $row["id"] . '" name="userSchedule[]" id="schedule_' . $row["id"] . '" /> ' . $row["day_of_week"] . ' ' . $start_time_12hr . ' - ' . $end_time_12hr . ' (Subject: ' . $row["subject_name"] . ', Section: ' . $row["section_name"] . ')
                        <span class="select_label-icon"></span>
                    </label>';
            }
        } else {
            echo '<label>No schedules available</label>';
        }
    ?>
</div>
    </div>
    <div class="mt-4 mb-0">
        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-block">Add User</button>
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



<?php include('card_id_modal.php'); ?>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="js/scripts.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
<script src="assets/demo/chart-area-demo.js"></script>
<script src="assets/demo/chart-bar-demo.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script src="js/datatables-simple-demo.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var inputUserRole = document.getElementById('inputUserRole');
    var inputYear = document.getElementById('inputYear');
    var inputCourse = document.getElementById('inputCourse');
    var inputPassword = document.getElementById('inputPassword');

    function handleUserRoleChange() {
        var userRole = inputUserRole.value;

        // If user role is 'admin', disable and clear year and course fields
        if (userRole === 'admin') {
            inputYear.value = "";   // Clear the year field
            inputCourse.value = ""; // Clear the course field
            inputYear.disabled = true;  // Disable the year field
            inputCourse.disabled = true; // Disable the course field
            inputPassword.disabled = false; // Enable password field for admin
        } else {
            // If not admin, enable year and course fields
            inputYear.disabled = false;
            inputCourse.disabled = false;
            inputPassword.disabled = true;  // Disable password field for non-admins
        }
    }

    inputUserRole.addEventListener('change', handleUserRoleChange);

    // Initial call to set the correct state on page load
    handleUserRoleChange();
});

    const search = document.getElementById("search");
const labels = document.querySelectorAll("#checkboxes2 > label");

search.addEventListener("input", () => Array.from(labels).forEach((element) => element.style.display = element.childNodes[1].id.toLowerCase().includes(search.value.toLowerCase()) ? "inline" : "none"))
</script>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const search = document.getElementById("search");
        const labels = document.querySelectorAll("#checkboxes2 > label");

        search.addEventListener("input", () => {
            const query = search.value.toLowerCase().trim();
            console.log("Search query:", query); // Debugging: Check the search query
            labels.forEach((label) => {
                const text = label.textContent.toLowerCase().trim();
                console.log("Label text:", text); // Debugging: Check the label text
                if (text.includes(query)) {
                    console.log(`Match found: ${text}`);
                    label.style.display = "block";
                } else {
                    console.log(`No match: ${text}`);
                    label.style.display = "none";
                }
            });
        });
    });
</script>


</body>
</html>
