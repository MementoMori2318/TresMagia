<?php
include("db.php"); // Include the database connection
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit;
}
    // ADD User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_card_data'])) {
    file_put_contents('/var/www/html/Tresmagia_SmartLock/card_data.txt', '');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    include("db.php");
    session_start();

    // Capture and sanitize form data
    $name = htmlspecialchars(trim($_POST['name']));
    $userId = htmlspecialchars(trim($_POST['userId']));
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $plain_password = htmlspecialchars(trim($_POST['password']));
    $userRole = htmlspecialchars(trim($_POST['userRole']));
    $cardUid = htmlspecialchars(trim($_POST['inputCardUid']));
    $selected_schedules = isset($_POST['userSchedule']) ? $_POST['userSchedule'] : [];

    // Additional logic to handle optional fields
    $year = ($userRole === 'admin') ? NULL : htmlspecialchars(trim($_POST['inputYear']));
    $course = ($userRole === 'admin') ? NULL : htmlspecialchars(trim($_POST['inputCourse']));
    $section = ($userRole === 'admin') ? NULL : htmlspecialchars(trim($_POST['inputSection']));

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format");
    }

    // Validate user role
    $valid_roles = ['student', 'staff', 'faculty', 'admin'];
    if (!in_array($userRole, $valid_roles)) {
        die("Invalid user role");
    }

    // Hash the password
    $password = password_hash($plain_password, PASSWORD_DEFAULT);

    try {
        $conn->begin_transaction();

        // Check if user already exists
        $stmt_check_user = $conn->prepare("SELECT id FROM users WHERE user_id = ?");
        $stmt_check_user->bind_param("s", $userId);
        $stmt_check_user->execute();
        $result_check_user = $stmt_check_user->get_result();

        if ($result_check_user->num_rows > 0) {
            $user_row = $result_check_user->fetch_assoc();
            $user_id = $user_row['id'];
        } else {
            // Insert new user with year, course, and section (using NULL if admin)
            $stmt_insert_user = $conn->prepare("INSERT INTO users (name, user_id, year, course, section, email, password, role, cards_uid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt_insert_user === false) {
                throw new Exception("Error preparing statement for inserting user: " . $conn->error);
            }

            // Use 'i' for integer types and pass NULL if admin
            $stmt_insert_user->bind_param("sssssssss", $name, $userId, $year, $course, $section, $email, $password, $userRole, $cardUid);

            if (!$stmt_insert_user->execute()) {
                throw new Exception("Error inserting user: " . $stmt_insert_user->error);
            }

            $user_id = $stmt_insert_user->insert_id;
            $stmt_insert_user->close();
        }

        // Insert selected schedules into user_schedules table
        $stmt_insert_schedule = $conn->prepare("INSERT INTO user_schedules (users_id, schedules_id) VALUES (?, ?)");
        if ($stmt_insert_schedule === false) {
            throw new Exception("Error preparing statement for inserting user schedule: " . $conn->error);
        }

        foreach ($selected_schedules as $schedule_id) {
            // Check for existing schedule to avoid duplicates
            $stmt_check_schedule = $conn->prepare("SELECT id FROM user_schedules WHERE users_id = ? AND schedules_id = ?");
            $stmt_check_schedule->bind_param("ii", $user_id, $schedule_id);
            $stmt_check_schedule->execute();
            $result_check_schedule = $stmt_check_schedule->get_result();

            if ($result_check_schedule->num_rows == 0) {
                $stmt_insert_schedule->bind_param("ii", $user_id, $schedule_id);
                if (!$stmt_insert_schedule->execute()) {
                    throw new Exception("Error inserting user schedule: " . $stmt_insert_schedule->error);
                }
            }
            $stmt_check_schedule->close();
        }

        $stmt_insert_schedule->close();

        $conn->commit();
        $conn->close();

        header("Location: usersList.php?success_message=" . urlencode("User added successfully"));
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
}
    //  END ADD User

    // Add Section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_section'])) {
    // Capture and sanitize form data
    $section_name = htmlspecialchars($_POST['section_name']);
    $course = htmlspecialchars($_POST['course']);
    $year = htmlspecialchars($_POST['year']);
    $section = htmlspecialchars($_POST['section']);

    // Validate that section_name is not empty
    if (empty($section_name) || empty($course) || empty($year) || empty($section)) {
        die("All fields are required.");
    }

    // Prepare SQL and bind parameters
    $stmt = $conn->prepare("INSERT INTO `section` (`section_name`, `course`, `year`, `section`) VALUES (?, ?, ?, ?)");
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("ssss", $section_name, $course, $year, $section);

    // Execute the query
    if ($stmt->execute()) {
        $success_message = "Section added successfully";
        // Close the statement and connection
        $stmt->close();
        $conn->close();

        // Redirect to addSchedule.php after successful insertion
        header("Location: addSchedule.php?success_message=" . urlencode($success_message));
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
        // End Add Section

        // Add Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST'  && isset($_POST['add_subject'])) {
    // Capture and sanitize form data
    $subject_name = htmlspecialchars($_POST['subject_name']);

    // Validate that subject_name is not empty
    if (empty($subject_name)) {
        die("Subject name is required.");
    }

    // Prepare SQL and bind parameters
    $stmt = $conn->prepare("INSERT INTO `subject` (`subject_name`) VALUES (?)");
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("s", $subject_name);

    // Execute the query
    if ($stmt->execute()) {
        $success_message = "Subject added successfully";
        // Close the statement and connection
        $stmt->close();
        $conn->close();

        // Redirect to the same page after successful insertion
        header("Location: addSchedule.php?success_message=" . urlencode($success_message));
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
        // END Add Subject

        // Start Add Schedule

// Handle form submission for adding a schedule
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_schedule'])) {
    // Capture and sanitize form data
    $dayOfWeek = htmlspecialchars($_POST['day_of_week']);
    $startTime = htmlspecialchars($_POST['inputStartTime']);
    $endTime = htmlspecialchars($_POST['inputEndTime']);
    $subjectName = htmlspecialchars($_POST['subject']);
    $sectionName = htmlspecialchars($_POST['section']);
    $selectedUsers = isset($_POST['selected_users']) ? $_POST['selected_users'] : []; // Capture selected users

    // Prepare SQL to get subject_id based on subject_name
    $stmt = $conn->prepare("SELECT subject_id FROM subject WHERE subject_id = ?");
    $stmt->bind_param("s", $subjectName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $subjectRow = $result->fetch_assoc();
        $subjectId = $subjectRow['subject_id'];
    } else {
        // Handle case where subject does not exist
        $error_message = 'Subject with name ' . $subjectName . ' not found in database.';
        header("Location: addSchedule.php?error_message=" . urlencode($error_message));
        exit();
    }

    // Prepare SQL to get section_id based on section_name
    $stmt = $conn->prepare("SELECT section_id FROM section WHERE section_id = ?");
    $stmt->bind_param("s", $sectionName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $sectionRow = $result->fetch_assoc();
        $sectionId = $sectionRow['section_id'];
    } else {
        // Handle case where section does not exist
        $error_message = 'Section with name ' . $sectionName . ' not found in database.';
        header("Location: addSchedule.php?error_message=" . urlencode($error_message));
        exit();
    }

    // Prepare SQL and bind parameters for INSERT query into schedules
    $stmt = $conn->prepare("INSERT INTO schedules (day_of_week, start_time, end_time, subject_id, section_id) VALUES (?, ?, ?, ?, ?)");
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("ssssi", $dayOfWeek, $startTime, $endTime, $subjectId, $sectionId);

    // Execute the query to insert schedule
    if ($stmt->execute()) {
        // Get the last inserted schedule_id
        $scheduleId = $stmt->insert_id;

        // Insert user_schedules records for selected users
        if (!empty($selectedUsers)) {
            $stmt_user_schedule = $conn->prepare("INSERT INTO user_schedules (users_id, schedules_id) VALUES (?, ?)");
            if ($stmt_user_schedule === false) {
                die("Error preparing statement for user schedules: " . $conn->error);
            }

            foreach ($selectedUsers as $userId) {
                $stmt_user_schedule->bind_param("ii", $userId, $scheduleId);
                if (!$stmt_user_schedule->execute()) {
                    echo "Error: " . $stmt_user_schedule->error;
                }
            }

            // Close the user_schedules statement
            $stmt_user_schedule->close();
        }

        // Close the schedules statement and connection
        $stmt->close();
        $conn->close();

        // Redirect to the same page after successful insertion
        $success_message = "Schedule added successfully";
        header("Location: schedule.php?success_message=" . urlencode($success_message));
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
// END Add Schedule

// Edit user 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['user_id'])) {

    // Initialize variables with form data
    $user_id = $_POST['user_id'];
    $name = $_POST['name'];
    $userRole = $_POST['userRole'];
    $userId = $_POST['userId'];
    $inputCardUid = $_POST['inputCardUid'];
    $inputYear = isset($_POST['inputYear']) ? $_POST['inputYear'] : null; // Year field
    $inputCourse = isset($_POST['inputCourse']) ? $_POST['inputCourse'] : null; // Course field
    $inputSection = isset($_POST['inputSection']) ? $_POST['inputSection'] : null; // Section field
    $email = $_POST['email'];
    $plain_password = $_POST['password']; // Note: This is the plain text password

    // Validate and hash the password if it's not empty
    $password = '';
    if (!empty($plain_password)) {
        $password = password_hash($plain_password, PASSWORD_DEFAULT);
    } else {
        // Password remains unchanged
        if (isset($_POST['current_password'])) {
            $password = $_POST['current_password']; // Assuming you have a hidden input for current password
        }
    }

    // Prepare and execute UPDATE query for users table
    $query_user = "UPDATE users SET name=?, role=?, user_id=?, cards_uid=?, year=?, course=?, section=?, email=?, password=? WHERE id=?";
    $stmt_user = $conn->prepare($query_user);
    $stmt_user->bind_param("sssssssssi", $name, $userRole, $userId, $inputCardUid, $inputYear, $inputCourse, $inputSection, $email, $password, $user_id);

    if (!$stmt_user->execute()) {
        echo "Error updating user: " . $stmt_user->error;
        exit;
    }

    // Update user schedules
    // Start a transaction for atomicity
    $conn->begin_transaction();

    try {
        // Delete all existing schedules for the user
        $query_delete_all = "DELETE FROM user_schedules WHERE users_id=?";
        $stmt_delete_all = $conn->prepare($query_delete_all);
        $stmt_delete_all->bind_param("i", $user_id);
        $stmt_delete_all->execute();
        $stmt_delete_all->close();

        // Insert new schedules if any are selected
        if (isset($_POST['userSchedule']) && !empty($_POST['userSchedule'])) {
            $query_insert = "INSERT INTO user_schedules (users_id, schedules_id) VALUES (?, ?)";
            $stmt_insert = $conn->prepare($query_insert);
            $stmt_insert->bind_param("ii", $user_id, $schedule_id);

            foreach ($_POST['userSchedule'] as $schedule_id) {
                $stmt_insert->execute();
            }

            $stmt_insert->close();
        }

        // Commit transaction
        $conn->commit();

        // Redirect with success message
        $success_message = "User edited successfully";
        header("Location: usersList.php?success_message=" . urlencode($success_message));
        exit;

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo "Error updating user schedules: " . $e->getMessage();
    }

    // Close statement
    $stmt_user->close();
}

    // End Edit user 


    // Delete User
  // Check if action is delete and ID is set
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Delete related logs
        $query_logs = "DELETE FROM logs WHERE users_id = ?";
        $stmt_logs = $conn->prepare($query_logs);
        if ($stmt_logs) {
            $stmt_logs->bind_param("i", $user_id);
            $stmt_logs->execute();
            $stmt_logs->close();
        } else {
            throw new Exception("Failed to prepare delete statement for logs: " . $conn->error);
        }

        // Delete related attendance records
        $query_attendance = "DELETE FROM attendance WHERE users_id = ?";
        $stmt_attendance = $conn->prepare($query_attendance);
        if ($stmt_attendance) {
            $stmt_attendance->bind_param("i", $user_id);
            $stmt_attendance->execute();
            $stmt_attendance->close();
        } else {
            throw new Exception("Failed to prepare delete statement for attendance: " . $conn->error);
        }

        // Delete related user schedules
        $query_user_schedules = "DELETE FROM user_schedules WHERE users_id = ?";
        $stmt_user_schedules = $conn->prepare($query_user_schedules);
        if ($stmt_user_schedules) {
            $stmt_user_schedules->bind_param("i", $user_id);
            $stmt_user_schedules->execute();
            $stmt_user_schedules->close();
        } else {
            throw new Exception("Failed to prepare delete statement for user schedules: " . $conn->error);
        }

        // Delete the user
        $query_user = "DELETE FROM users WHERE id = ?";
        $stmt_user = $conn->prepare($query_user);
        if ($stmt_user) {
            $stmt_user->bind_param("i", $user_id);
            $stmt_user->execute();
            $stmt_user->close();
        } else {
            throw new Exception("Failed to prepare delete statement for user: " . $conn->error);
        }

        // Commit the transaction
        $conn->commit();

        // Redirect with success message
        header('Location: usersList.php?success_message=User+deleted+successfully');
        exit;
    } catch (Exception $e) {
        // Rollback the transaction
        $conn->rollback();

        // Redirect with error message
        header('Location: usersList.php?error_message=' . urlencode($e->getMessage()));
        exit;
    }
}
 // END Delete user

 
?>



