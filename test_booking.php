<?php
session_start();
require_once 'dbconnect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = "";
$success = "";
$rate_per_hour = 100; // Default rate per hour

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['booking_date']) && isset($_POST['selected_slots'])) {
        $booking_date = $_POST['booking_date'];
        $selected_slots = json_decode($_POST['selected_slots'], true);
        
        if (!is_array($selected_slots) || empty($selected_slots)) {
            $error = "Please select at least one time slot.";
        } else {
            $user_id = $_SESSION['user_id'];
            
            // Check for existing bookings
            $placeholders = str_repeat('?,', count($selected_slots) - 1) . '?';
            $types = str_repeat('s', count($selected_slots));
            $stmt = $conn->prepare("SELECT booking_time FROM bookings WHERE booking_date = ? AND booking_time IN ($placeholders)");
            $params = array_merge([$booking_date], $selected_slots);
            $stmt->bind_param("s" . $types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $booked_times = [];
                while ($row = $result->fetch_assoc()) {
                    $booked_times[] = $row['booking_time'];
                }
                $error = "The following time slots are already booked: " . implode(", ", $booked_times);
            } else {
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Insert bookings
                    foreach ($selected_slots as $slot) {
                        $stmt = $conn->prepare("INSERT INTO bookings (user_id, booking_date, booking_time) VALUES (?, ?, ?)");
                        $stmt->bind_param("iss", $user_id, $booking_date, $slot);
                        $stmt->execute();
                    }
                    
                    $conn->commit();
                    $total_hours = count($selected_slots);
                    $total_cost = $total_hours * $rate_per_hour;
                    $success = "Booking successful! Total cost: ₹$total_cost";
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Error creating booking: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Booking</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .error { color: red; margin: 10px 0; }
        .success { color: green; margin: 10px 0; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .booking-form { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
        .time-slot { margin: 5px; padding: 5px; border: 1px solid #ccc; display: inline-block; cursor: pointer; }
        .time-slot.selected { background-color: #4CAF50; color: white; }
    </style>
</head>
<body>
    <h1>Test Booking System</h1>
    
    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="booking-form">
        <h2>Create Test Booking</h2>
        <form method="post" action="">
            <div>
                <label for="booking_date">Date:</label>
                <input type="date" id="booking_date" name="booking_date" required 
                       min="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div style="margin-top: 20px;">
                <label>Available Time Slots:</label><br>
                <?php
                $time_slots = ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];
                foreach ($time_slots as $slot) {
                    echo "<div class='time-slot' data-time='$slot'>$slot</div>";
                }
                ?>
                <input type="hidden" name="selected_slots" id="selected_slots">
            </div>
            
            <button type="submit" style="margin-top: 20px;">Create Booking</button>
        </form>
    </div>

    <h2>Current Bookings</h2>
    <?php
    $stmt = $conn->prepare("SELECT booking_date, booking_time FROM bookings ORDER BY booking_date, booking_time");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>Date</th><th>Time</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['booking_date']}</td><td>{$row['booking_time']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "No bookings found.";
    }
    ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const timeSlots = document.querySelectorAll('.time-slot');
            const selectedSlotsInput = document.getElementById('selected_slots');
            let selectedSlots = [];

            timeSlots.forEach(slot => {
                slot.addEventListener('click', function() {
                    const time = this.dataset.time;
                    if (this.classList.contains('selected')) {
                        this.classList.remove('selected');
                        selectedSlots = selectedSlots.filter(t => t !== time);
                    } else {
                        this.classList.add('selected');
                        selectedSlots.push(time);
                    }
                    selectedSlotsInput.value = JSON.stringify(selectedSlots);
                });
            });
        });
    </script>
</body>
</html> 