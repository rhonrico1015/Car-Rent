<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!is_admin()) {
    redirect('../login.php');
}

$page_title = "Calendar View - CarRent Pro";
$current_page = 'admin_calendar';

// Get current month/year or from URL parameters
$current_month = $_GET['month'] ?? date('n');
$current_year = $_GET['year'] ?? date('Y');

// Get all rentals for the current month
$rentals = [];
try {
    $stmt = $pdo->prepare("
        SELECT r.*, u.name as user_name, c.make, c.model, c.year, c.color
        FROM rentals r 
        LEFT JOIN users u ON r.user_id = u.id 
        LEFT JOIN cars c ON r.car_id = c.id 
        WHERE MONTH(r.start_date) = ? AND YEAR(r.start_date) = ?
           OR MONTH(r.end_date) = ? AND YEAR(r.end_date) = ?
        ORDER BY r.start_date
    ");
    $stmt->execute([$current_month, $current_year, $current_month, $current_year]);
    $rentals = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error fetching rentals: " . $e->getMessage();
}

// Get all cars for the calendar
$cars = [];
try {
    $stmt = $pdo->query("SELECT id, make, model, year, color FROM cars ORDER BY make, model");
    $cars = $stmt->fetchAll();
} catch (PDOException $e) {
    // Handle error
}

// Calculate calendar days
$first_day = mktime(0, 0, 0, $current_month, 1, $current_year);
$days_in_month = date('t', $first_day);
$start_day = date('w', $first_day); // 0 = Sunday

// Month names
$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

// Navigation
$prev_month = $current_month - 1;
$prev_year = $current_year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $current_month + 1;
$next_year = $current_year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .bg-primary { background-color: #7c3aed; }
        .text-primary { color: #7c3aed; }
        .border-primary { border-color: #7c3aed; }
        .hover\:bg-primary:hover { background-color: #7c3aed; }
        
        .calendar-day {
            min-height: 100px;
            position: relative;
        }
        
        .rental-item {
            font-size: 0.75rem;
            padding: 2px 4px;
            margin: 1px 0;
            border-radius: 3px;
            cursor: pointer;
        }
        
        .rental-pending { background-color: #fef3c7; color: #92400e; }
        .rental-confirmed { background-color: #dbeafe; color: #1e40af; }
        .rental-active { background-color: #d1fae5; color: #065f46; }
        .rental-completed { background-color: #f3f4f6; color: #374151; }
        .rental-cancelled { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="../dashboard.php" class="text-xl font-bold text-primary">CarRent Pro</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="../dashboard.php" class="text-gray-700 hover:text-primary">Dashboard</a>
                    <a href="cars.php" class="text-gray-700 hover:text-primary">Cars</a>
                    <a href="rentals.php" class="text-gray-700 hover:text-primary">Rentals</a>
                    <a href="calendar.php" class="text-primary font-semibold">Calendar</a>
                    <a href="users.php" class="text-gray-700 hover:text-primary">Users</a>
                    <a href="../logout.php" class="text-gray-700 hover:text-primary">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Booking Calendar</h1>
            <div class="flex items-center space-x-4">
                <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" 
                   class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">
                    <i class="fas fa-chevron-left mr-2"></i>Previous
                </a>
                <h2 class="text-xl font-semibold"><?php echo $month_names[$current_month] . ' ' . $current_year; ?></h2>
                <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" 
                   class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">
                    Next<i class="fas fa-chevron-right ml-2"></i>
                </a>
            </div>
        </div>

        <!-- Legend -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <h3 class="text-lg font-semibold mb-3">Status Legend</h3>
            <div class="flex flex-wrap gap-4">
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-yellow-200 rounded mr-2"></div>
                    <span class="text-sm">Pending</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-blue-200 rounded mr-2"></div>
                    <span class="text-sm">Confirmed</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-green-200 rounded mr-2"></div>
                    <span class="text-sm">Active</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-gray-200 rounded mr-2"></div>
                    <span class="text-sm">Completed</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-red-200 rounded mr-2"></div>
                    <span class="text-sm">Cancelled</span>
                </div>
            </div>
        </div>

        <!-- Calendar Grid -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <!-- Calendar Header -->
            <div class="grid grid-cols-7 bg-gray-50 border-b">
                <div class="p-3 text-center font-semibold text-gray-700">Sunday</div>
                <div class="p-3 text-center font-semibold text-gray-700">Monday</div>
                <div class="p-3 text-center font-semibold text-gray-700">Tuesday</div>
                <div class="p-3 text-center font-semibold text-gray-700">Wednesday</div>
                <div class="p-3 text-center font-semibold text-gray-700">Thursday</div>
                <div class="p-3 text-center font-semibold text-gray-700">Friday</div>
                <div class="p-3 text-center font-semibold text-gray-700">Saturday</div>
            </div>

            <!-- Calendar Body -->
            <div class="grid grid-cols-7">
                <?php
                // Empty cells for days before the first day of the month
                for ($i = 0; $i < $start_day; $i++) {
                    echo '<div class="calendar-day border border-gray-200 bg-gray-50"></div>';
                }

                // Days of the month
                for ($day = 1; $day <= $days_in_month; $day++) {
                    $current_date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $day);
                    $is_today = ($current_date === date('Y-m-d'));
                    
                    echo '<div class="calendar-day border border-gray-200 p-2' . ($is_today ? ' bg-blue-50' : '') . '">';
                    echo '<div class="font-semibold text-gray-900 mb-1">' . $day . '</div>';
                    
                    // Show rentals for this day
                    foreach ($rentals as $rental) {
                        $rental_start = date('Y-m-d', strtotime($rental['start_date']));
                        $rental_end = date('Y-m-d', strtotime($rental['end_date']));
                        
                        if ($current_date >= $rental_start && $current_date <= $rental_end) {
                            $status_class = 'rental-' . $rental['status'];
                            echo '<div class="rental-item ' . $status_class . '" onclick="showRentalDetails(' . $rental['id'] . ')">';
                            echo htmlspecialchars($rental['make'] . ' ' . $rental['model']);
                            echo '<br><small>' . htmlspecialchars($rental['user_name']) . '</small>';
                            echo '</div>';
                        }
                    }
                    
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <!-- Car Availability Summary -->
        <div class="mt-6 bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Car Availability Summary</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($cars as $car): ?>
                <div class="border rounded-lg p-4">
                    <h4 class="font-semibold"><?php echo htmlspecialchars($car['make'] . ' ' . $car['model'] . ' (' . $car['year'] . ')'); ?></h4>
                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($car['color']); ?></p>
                    
                    <?php
                    // Count bookings for this car in current month
                    $car_bookings = array_filter($rentals, function($rental) use ($car) {
                        return $rental['car_id'] == $car['id'];
                    });
                    
                    if (count($car_bookings) > 0) {
                        echo '<p class="text-sm text-blue-600 mt-2">' . count($car_bookings) . ' booking(s) this month</p>';
                    } else {
                        echo '<p class="text-sm text-green-600 mt-2">Available all month</p>';
                    }
                    ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Rental Details Modal -->
    <div id="rentalModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">Rental Details</h3>
                </div>
                <div id="rentalDetails" class="p-6">
                    <!-- Rental details will be loaded here -->
                </div>
                <div class="px-6 py-4 border-t">
                    <button onclick="closeRentalModal()" class="w-full bg-primary text-white py-2 px-4 rounded-md hover:bg-purple-700">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showRentalDetails(rentalId) {
            // Fetch rental details via AJAX
            fetch(`get_rental_details.php?id=${rentalId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('rentalDetails').innerHTML = `
                        <div class="space-y-3">
                            <div><strong>Customer:</strong> ${data.user_name}</div>
                            <div><strong>Car:</strong> ${data.make} ${data.model} (${data.year})</div>
                            <div><strong>Dates:</strong> ${data.start_date} to ${data.end_date}</div>
                            <div><strong>Total Cost:</strong> $${parseFloat(data.total_cost).toFixed(2)}</div>
                            <div><strong>Status:</strong> <span class="px-2 py-1 rounded text-xs ${getStatusClass(data.status)}">${data.status}</span></div>
                            ${data.pickup_location ? `<div><strong>Pickup:</strong> ${data.pickup_location}</div>` : ''}
                            ${data.return_location ? `<div><strong>Return:</strong> ${data.return_location}</div>` : ''}
                            ${data.notes ? `<div><strong>Notes:</strong> ${data.notes}</div>` : ''}
                        </div>
                    `;
                    document.getElementById('rentalModal').classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        function closeRentalModal() {
            document.getElementById('rentalModal').classList.add('hidden');
        }

        function getStatusClass(status) {
            switch(status) {
                case 'pending': return 'bg-yellow-100 text-yellow-800';
                case 'confirmed': return 'bg-blue-100 text-blue-800';
                case 'active': return 'bg-green-100 text-green-800';
                case 'completed': return 'bg-gray-100 text-gray-800';
                case 'cancelled': return 'bg-red-100 text-red-800';
                default: return 'bg-gray-100 text-gray-800';
            }
        }

        // Close modal when clicking outside
        document.getElementById('rentalModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRentalModal();
            }
        });
    </script>
</body>
</html>
