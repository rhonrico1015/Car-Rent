<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$page_title = "My Bookings - CarRent Pro";
$current_page = 'my_bookings';

// Handle booking cancellation
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $rental_id = (int)$_POST['rental_id'];
    
    try {
        // Check if user owns this booking
        $stmt = $pdo->prepare("SELECT * FROM rentals WHERE id = ? AND user_id = ?");
        $stmt->execute([$rental_id, $_SESSION['user_id']]);
        $rental = $stmt->fetch();
        
        if ($rental && in_array($rental['status'], ['pending', 'confirmed'])) {
            $stmt = $pdo->prepare("UPDATE rentals SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$rental_id]);
            
            // Make car available again
            $stmt = $pdo->prepare("UPDATE cars SET status = 'available' WHERE id = ?");
            $stmt->execute([$rental['car_id']]);
            
            $success_message = "Booking cancelled successfully!";
            log_activity($pdo, 'booking_cancelled', "Cancelled booking ID: $rental_id");
        } else {
            $error_message = "Cannot cancel this booking.";
        }
    } catch (PDOException $e) {
        $error_message = "Error cancelling booking: " . $e->getMessage();
    }
}

// Get user's bookings
$bookings = [];
try {
    $stmt = $pdo->prepare("
        SELECT r.*, c.make, c.model, c.year, c.color, c.license_plate, c.image
        FROM rentals r 
        LEFT JOIN cars c ON r.car_id = c.id 
        WHERE r.user_id = ? 
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error fetching bookings: " . $e->getMessage();
}

// Calculate statistics
$stats = [
    'total' => count($bookings),
    'pending' => count(array_filter($bookings, fn($b) => $b['status'] === 'pending')),
    'active' => count(array_filter($bookings, fn($b) => $b['status'] === 'active')),
    'completed' => count(array_filter($bookings, fn($b) => $b['status'] === 'completed'))
];
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
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-xl font-bold text-primary">CarRent Pro</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 hover:text-primary">Dashboard</a>
                    <a href="cars.php" class="text-gray-700 hover:text-primary">Browse Cars</a>
                    <a href="booking.php" class="text-gray-700 hover:text-primary">Book a Car</a>
                    <a href="my_bookings.php" class="text-primary font-semibold">My Bookings</a>
                    <a href="logout.php" class="text-gray-700 hover:text-primary">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 px-4">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">My Bookings</h1>

        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i class="fas fa-list text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Bookings</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <i class="fas fa-clock text-yellow-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Pending</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['pending']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <i class="fas fa-car text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Active</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['active']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-gray-100 rounded-lg">
                        <i class="fas fa-check text-gray-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Completed</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['completed']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bookings List -->
        <?php if (empty($bookings)): ?>
            <div class="bg-white rounded-lg shadow p-12 text-center">
                <i class="fas fa-calendar-times text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No Bookings Yet</h3>
                <p class="text-gray-600 mb-6">Start your car rental journey by booking your first car!</p>
                <a href="booking.php" class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-purple-700">
                    Book a Car
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <?php foreach ($bookings as $booking): ?>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                <?php echo htmlspecialchars($booking['make'] . ' ' . $booking['model'] . ' (' . $booking['year'] . ')'); ?>
                            </h3>
                            <p class="text-sm text-gray-600">Booking #<?php echo $booking['id']; ?></p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold 
                            <?php 
                            switch($booking['status']) {
                                case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                case 'confirmed': echo 'bg-blue-100 text-blue-800'; break;
                                case 'active': echo 'bg-green-100 text-green-800'; break;
                                case 'completed': echo 'bg-gray-100 text-gray-800'; break;
                                case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                default: echo 'bg-gray-100 text-gray-800';
                            }
                            ?>">
                            <?php echo ucfirst($booking['status']); ?>
                        </span>
                    </div>

                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Pickup Date:</span>
                            <span class="text-sm font-medium"><?php echo format_date($booking['start_date']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Return Date:</span>
                            <span class="text-sm font-medium"><?php echo format_date($booking['end_date']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Daily Rate:</span>
                            <span class="text-sm font-medium"><?php echo format_currency($booking['daily_rate']); ?></span>
                        </div>
                        <div class="flex justify-between border-t pt-2">
                            <span class="text-sm font-semibold text-gray-900">Total Cost:</span>
                            <span class="text-lg font-bold text-primary"><?php echo format_currency($booking['total_cost']); ?></span>
                        </div>
                    </div>

                    <?php if ($booking['pickup_location'] || $booking['return_location']): ?>
                    <div class="mb-4">
                        <?php if ($booking['pickup_location']): ?>
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            Pickup: <?php echo htmlspecialchars($booking['pickup_location']); ?>
                        </p>
                        <?php endif; ?>
                        <?php if ($booking['return_location']): ?>
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            Return: <?php echo htmlspecialchars($booking['return_location']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($booking['notes']): ?>
                    <div class="mb-4">
                        <p class="text-sm text-gray-600">
                            <strong>Notes:</strong> <?php echo htmlspecialchars($booking['notes']); ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500">
                            Created: <?php echo format_datetime($booking['created_at']); ?>
                        </span>
                        
                        <?php if (in_array($booking['status'], ['pending', 'confirmed'])): ?>
                        <button onclick="cancelBooking(<?php echo $booking['id']; ?>)" 
                                class="text-red-600 hover:text-red-800 text-sm font-medium">
                            <i class="fas fa-times mr-1"></i>Cancel
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Cancel Booking Form -->
    <form id="cancelForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="cancel">
        <input type="hidden" name="rental_id" id="cancelRentalId">
    </form>

    <script>
        function cancelBooking(rentalId) {
            if (confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
                document.getElementById('cancelRentalId').value = rentalId;
                document.getElementById('cancelForm').submit();
            }
        }
    </script>
</body>
</html>
