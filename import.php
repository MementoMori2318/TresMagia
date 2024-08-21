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
        $success = true;

        // Skip the header row by starting the loop from index 1 instead of 0
        foreach ($data as $index => $row) {
            if ($index == 0) continue; // Skip the header row

            $name = trim($row[0]);
            $user_id = trim($row[1]);
            $year_section = trim($row[2]);
            $email = trim($row[3]);
            $password = password_hash(trim($row[4]), PASSWORD_BCRYPT); // Encrypt the password
            $role = trim($row[5]);
            $cards_uid = trim($row[6]);

            // Check if the user already exists
            $checkQuery = "SELECT * FROM users WHERE user_id = '$user_id' OR email = '$email'";
            $checkResult = mysqli_query($conn, $checkQuery);

            if (mysqli_num_rows($checkResult) == 0) {
                // Construct the SQL query
                $userQuery = "INSERT INTO users (name, user_id, year_section, email, password, role, cards_uid) 
                              VALUES ('$name', '$user_id', '$year_section', '$email', '$password', '$role', '$cards_uid')";

                // Execute the SQL query
                $result = mysqli_query($conn, $userQuery);

                // Check if query executed successfully
                if (!$result) {
                    $errorMessages[] = "Error inserting User ID: $user_id. The Excel file may be incorrect or contain invalid data.";
                    $success = false;
                }
            } else {
                $errorMessages[] = "Skipping duplicate entry for User ID: $user_id or Email: $email";
                $success = false;
            }
        }

        if ($success) {
            $_SESSION['message'] = "Successfully Imported";
            // Redirect with success message
            header('Location: usersList.php?import_success_message=' . urlencode($_SESSION['message']));
        } else {
            $_SESSION['message'] = "There was an issue with your file. Please check the file format or content and try again.";
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
