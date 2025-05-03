<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include debug utilities
require_once 'debug_utils.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log session data
debug_log('Session data in admin_dashboard.php: ' . print_r($_SESSION, true));

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    debug_log('User not authorized. Redirecting to login.');
    header('Location: login.php');
    exit();
}

require_once 'dbconnect.php';
require_once 'admin_dashboard_functions.php';

// Log booking stats after including functions file
debug_log('Booking stats in admin_dashboard.php: ' . print_r($GLOBALS['booking_stats'], true));

// The booking stats are already available from admin_dashboard_functions.php
// No need to define getBookingStats() function here
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TBS</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            color: #333;
        }

        .navbar {
            background: rgba(0, 0, 0, 0.2) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .main-content {
            padding: 20px;
            margin-top: 20px;
        }

        .stat-card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .card {
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .card-header {
            background: rgba(0, 0, 0, 0.05);
            border-bottom: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 15px 15px 0 0 !important;
            padding: 15px 20px;
        }

        .card-body {
            padding: 20px;
        }

        .table {
            background: white;
            border-radius: 10px;
        }

        .refresh-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            padding: 12px 24px;
            border-radius: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            background: white;
        }

        .badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 500;
        }

        .stats-card h5 {
            color: rgba(0, 0, 0, 0.7);
            font-size: 1.1rem;
            margin-bottom: 15px;
        }

        .stats-card h2 {
            font-size: 2.2rem;
            font-weight: 600;
            margin: 0;
        }

        .stats-card i {
            margin-right: 10px;
        }

        @media (max-width: 768px) {
            .stats-card {
                margin-bottom: 15px;
            }
            .main-content {
                padding: 15px;
                margin-top: 15px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="img/ArenaLOGO.png" alt="TBS Logo" height="40" class="d-inline-block align-text-top me-2">
                Admin Dashboard
            </a>
            <div class="d-flex">
                <a href="Home_page.html" class="btn btn-outline-light me-2">Home</a>
                <a href="logout.php" class="btn btn-outline-light">Logout</a>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <div class="card stat-card">
                        <div class="card-body">
                            <h5 class="card-title">Total Bookings</h5>
                            <h2 class="card-text"><?php echo $GLOBALS['booking_stats']['total_bookings']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card stat-card">
                        <div class="card-body">
                            <h5 class="card-title">Today's Occupancy Rate</h5>
                            <h2 class="card-text"><?php echo $GLOBALS['booking_stats']['occupancy_rate']; ?>%</h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card stat-card">
                        <div class="card-body">
                            <h5 class="card-title">Turf Distribution by Location</h5>
                            <canvas id="turfDistributionChart"></canvas>
                            <script>
                                // Ensure we have data before creating the chart
                                const turfData = <?php echo json_encode($GLOBALS['booking_stats']['turfs_by_location'] ?? []); ?>;
                                if (Object.keys(turfData).length > 0) {
                                    const ctx = document.getElementById('turfDistributionChart').getContext('2d');
                                    new Chart(ctx, {
                                        type: 'bar',
                                        data: {
                                            labels: Object.keys(turfData),
                                            datasets: [{
                                                label: 'Bookings by Location',
                                                data: Object.values(turfData),
                                                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                                borderColor: 'rgba(54, 162, 235, 1)',
                                                borderWidth: 1
                                            }]
                                        },
                                        options: {
                                            scales: {
                                                y: {
                                                    beginAtZero: true
                                                }
                                            }
                                        }
                                    });
                                } else {
                                    document.getElementById('turfDistributionChart').innerHTML = 'No turf distribution data available';
                                }
                            </script>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card stat-card">
                        <div class="card-body">
                            <h5 class="card-title">Recent Bookings</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Location</th>
                                            <th>Turf</th>
                                            <th>User</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($GLOBALS['booking_stats']['recent_bookings'] as $booking): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($booking['booking_date']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['booking_time']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['location_name']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['turf_name']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $booking['payment_status'] === 'paid' ? 'bg-success' : 'bg-warning'; ?>">
                                                    <?php echo ucfirst(htmlspecialchars($booking['payment_status'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button class="refresh-btn" onclick="location.reload()">
        <i class="fas fa-sync-alt"></i> Refresh Data
    </button>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html> 