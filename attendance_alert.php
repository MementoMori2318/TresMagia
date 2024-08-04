<?php
include("db.php"); // Include the database connection
$card_data_file = '/var/www/html/Tresmagia_SmartLock/card_data.txt';

if (file_exists($card_data_file)) {
    $data = file_get_contents($card_data_file);
    list($card_id, $timestamp, $status) = explode(',', $data);
    
    // Clear the file after reading
    file_put_contents($card_data_file, '');
    
    echo json_encode(['card_id' => $card_id, 'timestamp' => $timestamp, 'status' => $status]);
} else {
    echo json_encode(['card_id' => null, 'timestamp' => null, 'status' => null]);
}
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            let lastShownTimestamp = 0; // Initialize with zero

            setInterval(checkForCard, 1000); // Check every second

            function checkForCard() {
                $.ajax({
                    url: 'check_card.php', // Adjust to the correct path
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        const { card_id, timestamp, status } = response;
                        if (card_id && status === 'success' && timestamp > lastShownTimestamp) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Card Detected',
                                text: `Card ID: ${card_id}`,
                                timer: 3000,
                                showConfirmButton: false
                            });

                            // Update the last shown timestamp
                            lastShownTimestamp = timestamp;
                        }
                    }
                });
            }
        });
    </script>
