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

        $count = 0;
        foreach ($data as $row) {
            if ($count > 0) {
                $name = $row[0];
                $user_id = $row[1];
                $year_section = $row[2];
                $email = $row[3];
                $password = password_hash($row[4], PASSWORD_BCRYPT); // Encrypt the password
                $role = $row[5];
                $cards_uid = $row[6];

                // Check if the user already exists
                $checkQuery = "SELECT * FROM users WHERE user_id = '$user_id' OR email = '$email'";
                $checkResult = mysqli_query($conn, $checkQuery);

                if (mysqli_num_rows($checkResult) == 0) {
                    // Construct the SQL query
                    $userQuery = "INSERT INTO users (name, user_id, year_section, email, password, role, cards_uid) 
                                  VALUES ('$name', '$user_id', '$year_section', '$email', '$password', '$role', '$cards_uid')";

                    // Echo or log the SQL query for debugging
                    echo "SQL Query: " . $userQuery . "<br>";

                    // Execute the SQL query
                    $result = mysqli_query($conn, $userQuery);

                    // Check if query executed successfully
                    if (!$result) {
                        echo "Error: " . mysqli_error($conn);
                        exit();
                    }
                } else {
                    echo "Skipping duplicate entry for User ID: $user_id or Email: $email<br>";
                }
            } else {
                $count = 1;
            }
        }

        $_SESSION['message'] = "Successfully Imported";
        // Use import_success_message for success
        header('Location: usersList.php?import_success_message=' . urlencode($_SESSION['message']));
        exit(0);
    } else {
        $_SESSION['message'] = "Invalid File Format ";
        // Use import_error_message for error
        header('Location: usersList.php?import_error_message=' . urlencode($_SESSION['message']));
        exit(0);
    }
}
?>
