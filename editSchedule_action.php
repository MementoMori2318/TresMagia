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

    // Debug statement to verify the POST data
    error_log("Update request received for ID: $id");

    // Update the schedule in the database
    $stmt = $conn->prepare("UPDATE schedules SET day_of_week = ?, start_time = ?, end_time = ?, subject_id = ?, section_id = ? WHERE id = ?");
    $stmt->bind_param("ssssii", $day_of_week, $start_time, $end_time, $subject_id, $section_id, $id);

    if ($stmt->execute()) {
        $success_message = "Schedule updated successfully!";
        header("Location: schedule.php?success_message=" . urlencode($success_message));
    } else {
        $error_message = "Error updating schedule: " . $stmt->error;
        header("Location: schedule.php?error_message=" . urlencode($error_message));
    }

    $stmt->close();
    $conn->close();
}


// END Edit Schedule
?>
