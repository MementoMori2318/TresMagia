<?php
include 'db.php'; // Include your DB connection

if (isset($_POST['section_id'])) {
    $section_id = $_POST['section_id'];

    // Fetch section details based on section_id
    $sectionQuery = "SELECT course, year, section FROM section WHERE section_id = '$section_id'";
    $sectionResult = $conn->query($sectionQuery);

    if ($sectionResult->num_rows > 0) {
        $section = $sectionResult->fetch_assoc();
        $course = $section['course'];
        $year = $section['year'];
        $sectionName = $section['section'];

        // Fetch users that match the section (students) and all faculty
        $userQuery = "
            SELECT u.id, u.name, u.role, u.year, u.course, u.section 
            FROM users u 
            WHERE (u.role = 'Student' AND u.course = '$course' AND u.year = '$year' AND u.section = '$sectionName')
            OR u.role = 'Faculty'
        ";
        $userResult = $conn->query($userQuery);

        if ($userResult->num_rows > 0) {
            while ($row = $userResult->fetch_assoc()) {
                echo "<tr>";
                echo "<td><input type='checkbox' name='selected_users[]' value='{$row['id']}' class='user-checkbox'></td>";
                echo "<td>" . htmlspecialchars($row['role']) . "</td>";
                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['year']) . "</td>";
                echo "<td>" . htmlspecialchars($row['course']) . "</td>";
                echo "<td>" . htmlspecialchars($row['section']) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='6'>No users available for the selected section</td></tr>";
        }
    } else {
        echo "<tr><td colspan='6'>Section not found</td></tr>";
    }
} else {
    echo "Section ID not set in POST";
}
?>
