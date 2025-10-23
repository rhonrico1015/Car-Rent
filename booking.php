<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$page_title = "Book a Car - CarRent Pro";
$current_page = 'booking';

// Handle booking submission
if ($_POST) {
    $car_id = (int)$_POST['car_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $pickup_location = sanitize_input($_POST['pickup_location']);
    $return_location = sanitize_input($_POST['return_location']);
    $pickup_time = $_POST['pickup_time'];
    $return_time = $_POST['return_time'];
    $notes = sanitize_input($_POST['notes']);
    
    // Calculate total cost
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $days = $end->diff($start)->days + 1;
    
    try {
        // Get car details
        $stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ? AND status = 'available'");
        $stmt->execute([$car_id]);
        $car = $stmt->fetch();
        
        if (!$car) {
            $error_message = "Car not available for booking.";
        } else {
            $daily_rate = (float)$car['daily_rate'];
            $total_cost = $days * $daily_rate;
            
            // Create booking
            $stmt = $pdo->prepare("
                INSERT INTO rentals (user_id, car_id, start_date, end_date, daily_rate, total_cost, 
                                   pickup_location, return_location, pickup_time, return_time, notes, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$_SESSION['user_id'], $car_id, $start_date, $end_date, $daily_rate, $total_cost,
                          $pickup_location, $return_location, $pickup_time, $return_time, $notes]);
            
            $success_message = "Booking submitted successfully! Total cost: " . format_currency($total_cost);
            log_activity($pdo, 'booking_created', "Created booking for car ID: $car_id");
        }
    } catch (PDOException $e) {
        $error_message = "Error creating booking: " . $e->getMessage();
    }
}

// Get available cars
$cars = [];
try {
    $stmt = $pdo->query("SELECT * FROM cars WHERE status = 'available' ORDER BY make, model");
    $cars = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error fetching cars: " . $e->getMessage();
}

// Get selected car
$selected_car = null;
if (isset($_GET['car_id'])) {
    $car_id = (int)$_GET['car_id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
        $stmt->execute([$car_id]);
        $selected_car = $stmt->fetch();
    } catch (PDOException $e) {
        // Handle error
    }
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
                    <a href="booking.php" class="text-primary font-semibold">Book a Car</a>
                    <a href="my_bookings.php" class="text-gray-700 hover:text-primary">My Bookings</a>
                    <a href="logout.php" class="text-gray-700 hover:text-primary">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 px-4">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Book a Car</h1>

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

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Car Selection -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Select a Car</h2>
                
                <div class="space-y-4">
                    <?php foreach ($cars as $car): ?>
                    <div class="border rounded-lg p-4 hover:border-primary cursor-pointer transition-colors" 
                         onclick="selectCar(<?php echo $car['id']; ?>)">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?></h3>
                                <p class="text-gray-600"><?php echo htmlspecialchars($car['year'] . ' - ' . $car['color']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($car['fuel_type'] . ' - ' . $car['transmission']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo $car['seats']; ?> seats - <?php echo htmlspecialchars($car['location']); ?></p>
                            </div>
                            <div class="text-right">
                                <div class="text-xl font-bold text-primary"><?php echo format_currency($car['daily_rate']); ?>/day</div>
                            </div>
                        </div>
                        <?php if ($car['description']): ?>
                        <p class="text-sm text-gray-600 mt-2"><?php echo htmlspecialchars($car['description']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Booking Form -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Booking Details</h2>
                
                <?php if ($selected_car): ?>
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-semibold">Selected Car</h3>
                    <p><?php echo htmlspecialchars($selected_car['make'] . ' ' . $selected_car['model'] . ' (' . $selected_car['year'] . ')'); ?></p>
                    <p class="text-primary font-semibold"><?php echo format_currency($selected_car['daily_rate']); ?>/day</p>
                </div>
                <?php endif; ?>

                <form method="POST" id="bookingForm">
                    <input type="hidden" name="car_id" id="car_id" value="<?php echo $selected_car['id'] ?? ''; ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                            <input type="date" name="start_date" id="start_date" required 
                                   min="<?php echo date('Y-m-d'); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input type="date" name="end_date" id="end_date" required 
                                   min="<?php echo date('Y-m-d'); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pickup Time</label>
                            <input type="time" name="pickup_time" id="pickup_time" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Return Time</label>
                            <input type="time" name="return_time" id="return_time" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pickup Location</label>
                            <input type="text" name="pickup_location" id="pickup_location" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Return Location</label>
                            <input type="text" name="return_location" id="return_location" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Special Notes</label>
                        <textarea name="notes" id="notes" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                    </div>
                    
                    <div id="costEstimate" class="mb-4 p-4 bg-blue-50 rounded-lg hidden">
                        <h4 class="font-semibold text-blue-900">Cost Estimate</h4>
                        <p id="daysCount" class="text-blue-700"></p>
                        <p id="totalCost" class="text-blue-900 font-bold text-lg"></p>
                    </div>
                    
                    <button type="submit" id="submitBtn" 
                            class="w-full bg-primary text-white py-2 px-4 rounded-md hover:bg-purple-700 disabled:bg-gray-400 disabled:cursor-not-allowed"
                            <?php echo !$selected_car ? 'disabled' : ''; ?>>
                        Submit Booking
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        let selectedCar = null;
        
        function selectCar(carId) {
            // Remove previous selection
            document.querySelectorAll('.border').forEach(el => {
                el.classList.remove('border-primary', 'bg-blue-50');
                el.classList.add('border-gray-300');
            });
            
            // Add selection to clicked car
            event.currentTarget.classList.add('border-primary', 'bg-blue-50');
            event.currentTarget.classList.remove('border-gray-300');
            
            // Update form
            document.getElementById('car_id').value = carId;
            document.getElementById('submitBtn').disabled = false;
            
            selectedCar = carId;
        }
        
        // Calculate cost when dates change
        document.getElementById('start_date').addEventListener('change', calculateCost);
        document.getElementById('end_date').addEventListener('change', calculateCost);
        
        function calculateCost() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            
            if (startDate && endDate && selectedCar) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
                
                if (days > 0) {
                    // Get daily rate from selected car (you'd need to fetch this via AJAX)
                    // For now, using a placeholder
                    const dailyRate = 100; // This should be fetched from the selected car
                    const totalCost = days * dailyRate;
                    
                    document.getElementById('daysCount').textContent = `${days} days`;
                    document.getElementById('totalCost').textContent = `Total: $${totalCost.toFixed(2)}`;
                    document.getElementById('costEstimate').classList.remove('hidden');
                }
            }
        }
        
        // Set minimum end date when start date changes
        document.getElementById('start_date').addEventListener('change', function() {
            document.getElementById('end_date').min = this.value;
            if (document.getElementById('end_date').value < this.value) {
                document.getElementById('end_date').value = this.value;
            }
        });
        
        // Form validation
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            if (!selectedCar) {
                e.preventDefault();
                alert('Please select a car first.');
                return false;
            }
        });
    </script>
</body>
</html>
