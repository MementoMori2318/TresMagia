// check_card.php
<?php
include("db.php");

$cards_uid = $_GET['cards_uid'];
$response = array('isRegistered' => false);

$query = "SELECT id FROM users WHERE cards_uid = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $cards_uid);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    $response['isRegistered'] = true;
}

mysqli_stmt_close($stmt);
echo json_encode($response);
?>
