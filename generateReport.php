<?php
require 'vendor/autoload.php'; // Load PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include("db.php");
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit;
}

// Get schedule ID, date, and email from POST data
$scheduleId = $_POST['schedule_id'];
$attendanceDate = $_POST['attendance_date'];
$reportEmail = $_POST['report_email'];

// Query to fetch attendance and schedule info
$attendanceQuery = "
    SELECT 
        u.name,
        u.id,
        u.user_id,
        CONCAT(u.year_section, ' - ') AS year_course_section,
        a.date,
        a.time_in,
        a.time_out,
        su.subject_name,
        se.section_name
    FROM 
        attendance a
        JOIN users u ON a.users_id = u.id
        JOIN schedules s ON a.schedules_id = s.id
        JOIN subject su ON s.subject_id = su.subject_id
        JOIN section se ON s.section_id = se.section_id
    WHERE 
        a.schedules_id = ? 
        AND a.date = ?
";

$stmt = $conn->prepare($attendanceQuery);
$stmt->bind_param("is", $scheduleId, $attendanceDate);
$stmt->execute();
$result = $stmt->get_result();

// Fetch the first row to get subject and section names
$firstRow = $result->fetch_assoc();
$subjectName = $firstRow['subject_name'];
$sectionName = $firstRow['section_name'];
$sectionSubject = $sectionName . ' - ' . $subjectName;

// Reset result pointer to the beginning
$result->data_seek(0);

// Create a new spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Attendance');

// Set subject and section as the title at the top
$sheet->setCellValue('A1', '' . $sectionSubject);
$sheet->mergeCells('A1:F1');
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Set header row
$sheet->setCellValue('A2', 'Name');
$sheet->setCellValue('B2', 'Student ID');
$sheet->setCellValue('C2', 'Year Course & Section');
$sheet->setCellValue('D2', 'Date');
$sheet->setCellValue('E2', 'Time In');
$sheet->setCellValue('F2', 'Time Out');

// Apply styles to the header
$headerStyle = $sheet->getStyle('A2:F2');
$headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$headerStyle->getFont()->setBold(true);
$headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('ADD8E6'); // Light-blue background

// Set column widths
$sheet->getColumnDimension('A')->setWidth(20);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(25);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(15);
$sheet->getColumnDimension('F')->setWidth(15);

// Add data rows
$row = 3;
while ($attendance = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $row, $attendance['name']);
    $sheet->setCellValue('B' . $row, $attendance['user_id']);
    $sheet->setCellValue('C' . $row, $attendance['year_course_section']);
    $sheet->setCellValue('D' . $row, date('F d, Y', strtotime($attendance['date'])));
    $sheet->setCellValue('E' . $row, date('h:i A', strtotime($attendance['time_in'])));
    $sheet->setCellValue('F' . $row, $attendance['time_out'] ? date('h:i A', strtotime($attendance['time_out'])) : '');
    $row++;
}

// Determine the action: download or email
$action = $_POST['action'];

// Properly formatted filename with section and subject
$filename = 'Attendance_Report_' . str_replace(' ', '_', $sectionSubject) . '_' . $attendanceDate . '.xlsx';

if ($action === 'download') {
    // Generate Excel file for download
    $writer = new Xlsx($spreadsheet);

    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit;
} elseif ($action === 'email' && !empty($reportEmail)) {
    // Generate Excel file for emailing
    $writer = new Xlsx($spreadsheet);
    $tempFilePath = tempnam(sys_get_temp_dir(), $filename);
    $writer->save($tempFilePath);

    // Send the file via email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com'; // Specify your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@example.com'; // Your SMTP username
        $mail->Password = 'your-password'; // Your SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('your-email@example.com', 'Your Name');
        $mail->addAddress($reportEmail); // Add the email from the form

        $mail->isHTML(true);
        $mail->Subject = 'Attendance Report';
        $mail->Body = 'Please find the attached attendance report for ' . $sectionSubject . ' on ' . date('F d, Y', strtotime($attendanceDate)) . '.';
        $mail->addAttachment($tempFilePath, $filename);

        $mail->send();
        echo 'Report emailed successfully to ' . htmlspecialchars($reportEmail) . '.';
    } catch (Exception $e) {
        echo 'Email could not be sent. Mailer Error: ' . $mail->ErrorInfo;
    }

    // Clean up
    unlink($tempFilePath);
}
?>
