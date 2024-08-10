<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule</title>
    <link rel="stylesheet" href="css/schedule.css">
    <!-- Codyhouse CSS -->
</head>
<body>
    <?php
    include("db.php");

    // Fetch schedule data from the database
    $sql = "SELECT schedules.id, schedules.day_of_week, schedules.start_time, schedules.end_time, 
    subject.subject_name, section.section_name 
    FROM schedules 
    JOIN subject ON schedules.subject_id = subject.subject_id 
    JOIN section ON schedules.section_id = section.section_id";

    $result = $conn->query($sql);

    // Group schedule data by day of the week
    $schedulesByDay = [];
    while ($row = $result->fetch_assoc()) {
        $schedulesByDay[$row['day_of_week']][] = $row;
    }

    // Define the days of the week in order
    $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    echo '<div class="cd-schedule loading">';
    echo '<div class="timeline"><ul>';
    for ($hour = 7; $hour <= 19; $hour++) {
        for ($min = 0; $min < 60; $min += 30) {
            $formattedHour = $hour % 12;
            $formattedHour = $formattedHour ? $formattedHour : 12; // Adjust zero hour to 12
            $formattedTime = sprintf('%d:%02d', $formattedHour, $min);
            echo '<li><span>' . $formattedTime . '</span></li>';
        }
    }
    echo '</ul></div>';

    echo '<div class="events"><ul class="wrap">';

    foreach ($daysOfWeek as $day) {
        echo '<li class="events-group schedText">';
        echo '<div class="top-info"><span>' . $day . '</span></div>';
        echo '<ul>';

        if (isset($schedulesByDay[$day])) {
            foreach ($schedulesByDay[$day] as $schedule) {
                $startTime = date('H:i', strtotime($schedule['start_time']));
                $endTime = date('H:i', strtotime($schedule['end_time']));
                $subject = htmlspecialchars($schedule['subject_name']);
                $section = htmlspecialchars($schedule['section_name']);
                $scheduleId = $schedule['id']; // Ensure you have the schedule ID available

                echo "<li class='single-event' style='font-size: 20px;' data-start='$startTime' data-end='$endTime' data-content='event-$subject' data-event='event-$scheduleId'>";
                echo "<a href='editSchedule.php?id=$scheduleId'>"; // Set the href attribute to link to the edit page
                echo "<em class='event-name' style='font-size: 20px;'>$subject - $section</em>";
                echo "</a></li>";
            }
        }

        echo '</ul></li>';
    }

    echo '</ul></div>';
    echo '</div>';
    ?>

    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Include schedule.js after jQuery -->
    <script src="js/schedule.js"></script>
</body>
</html>
