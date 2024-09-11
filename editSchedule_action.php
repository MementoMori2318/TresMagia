<?php
include("db.php"); // Include the database connection
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit;
}

// Edit Schedule 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $day_of_week = $_POST['day_of_week'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $subject_id = $_POST['subject_id'];
    $section_id = $_POST['section_id'];
    $selectedUsers = isset($_POST['selected_users']) ? $_POST['selected_users'] : []; // Capture selected users

    // Debug statement to verify the POST data
    error_log("Update request received for ID: $id");

    // Update the schedule in the database
    $stmt = $conn->prepare("UPDATE schedules SET day_of_week = ?, start_time = ?, end_time = ?, subject_id = ?, section_id = ? WHERE id = ?");
    $stmt->bind_param("ssssii", $day_of_week, $start_time, $end_time, $subject_id, $section_id, $id);

    if ($stmt->execute()) {
        // Clear existing user associations
        $deleteStmt = $conn->prepare("DELETE FROM user_schedules WHERE schedules_id = ?");
        $deleteStmt->bind_param("i", $id);
        $deleteStmt->execute();
        $deleteStmt->close();

        // Insert new associations if any users are selected
        if (!empty($selectedUsers)) {
            $insertStmt = $conn->prepare("INSERT INTO user_schedules (users_id, schedules_id) VALUES (?, ?)");
            if ($insertStmt === false) {
                die("Error preparing statement for user schedules: " . $conn->error);
            }

            foreach ($selectedUsers as $userId) {
                $insertStmt->bind_param("ii", $userId, $id);
                if (!$insertStmt->execute()) {
                    // Handle error for individual insert
                    error_log("Error inserting user_schedule for user ID: $userId - " . $insertStmt->error);
                }
            }

            $insertStmt->close();
        }

        $success_message = "Schedule updated successfully!";
        header("Location: schedule.php?success_message=" . urlencode($success_message));
    } else {
        $error_message = "Error updating schedule: " . $stmt->error;
        header("Location: schedule.php?error_message=" . urlencode($error_message));
    }

    $stmt->close();
    $conn->close();
}
?>
