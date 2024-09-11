<?php
session_start();
include("db.php");

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (isset($_POST['save_excel_data'])) {
    $fileName = $_FILES['import_file']['name'];
    $file_ext = pathinfo($fileName, PATHINFO_EXTENSION);

    $allowed_ext = ['xls', 'csv', 'xlsx'];

    if (in_array($file_ext, $allowed_ext)) {
        $inputFileNamePath = $_FILES['import_file']['tmp_name'];
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileNamePath);
        $data = $spreadsheet->getActiveSheet()->toArray();

        $errorMessages = [];
        $hasCriticalError = false;

        // Skip the header row by starting the loop from index 1 instead of 0
        foreach ($data as $index => $row) {
            if ($index == 0) continue; // Skip the header row

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue; // Skip this row if all columns are empty
            }

            $name = trim($row[0]);
            $user_id = trim($row[1]);
            $year = trim($row[2]); // Changed from year_section to year
            $course = trim($row[3]); // Added course
            $email = trim($row[4]);
            $password = !empty(trim($row[5])) ? password_hash(trim($row[5]), PASSWORD_BCRYPT) : null; // Encrypt password if not empty
            $role = trim($row[6]);
            $cards_uid = trim($row[7]);

            // Check if the required fields are not empty
            if (empty($user_id) || empty($email)) {
                // Skip this row due to missing required fields (but do not consider it a critical error)
                continue; 
            }

            // Check if the user already exists
            $checkQuery = "SELECT * FROM users WHERE user_id = '$user_id' OR email = '$email'";
            $checkResult = mysqli_query($conn, $checkQuery);

            if (mysqli_num_rows($checkResult) == 0) {
                // Handle the case where Faculty role doesn't need year and course
                if ($role === 'Faculty') {
                    $year = null;  // Set year to null for Faculty
                    $course = null;  // Set course to null for Faculty
                } elseif ($role === 'Student') {
                    // Ensure year and course are required for students
                    if (empty($year) || empty($course)) {
                        // Skip this row if year or course is missing for students
                        $errorMessages[] = "Row $index skipped: Year or Course is missing for Student.";
                        continue;
                    }
                }

                // Construct the SQL query, handling optional fields correctly
                $userQuery = "INSERT INTO users (name, user_id, year, course, email, password, role, cards_uid) 
                              VALUES ('$name', '$user_id', " . 
                              ($year ? "'$year'" : "NULL") . ", " . 
                              ($course ? "'$course'" : "NULL") . ", '$email', " . 
                              ($password ? "'$password'" : "NULL") . ", '$role', '$cards_uid')";

                // Execute the SQL query
                $result = mysqli_query($conn, $userQuery);

                // Check if query executed successfully
                if (!$result) {
                    $hasCriticalError = true;
                }
            } else {
                // Skip duplicate entries without marking it as a critical error
                continue;
            }
        }

        // If there are no critical errors, consider the import successful
        if (!$hasCriticalError) {
            $_SESSION['message'] = "Successfully Imported with some duplicates or empty rows skipped.";
            // Redirect with success message
            header('Location: usersList.php?import_success_message=' . urlencode($_SESSION['message']));
        } else {
            $_SESSION['message'] = "There was an issue with some entries. Please review the data and try again.";
            // Redirect with error message
            header('Location: usersList.php?import_error_message=' . urlencode($_SESSION['message']));
        }
        exit(0);
    } else {
        $_SESSION['message'] = "Invalid file format. Please upload a valid Excel file.";
        // Redirect with error message
        header('Location: usersList.php?import_error_message=' . urlencode($_SESSION['message']));
        exit(0);
    }
}
?>
