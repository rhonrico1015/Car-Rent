<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$page_title = "Payment - CarRent Pro";
$current_page = 'payment';

// Get rental ID from URL
$rental_id = (int)($_GET['rental_id'] ?? 0);

if ($rental_id <= 0) {
    redirect('my_bookings.php');
}

// Get rental details
$rental = null;
try {
    $stmt = $pdo->prepare("
        SELECT r.*, c.make, c.model, c.year, c.color, u.name as user_name, u.email as user_email
        FROM rentals r 
        LEFT JOIN cars c ON r.car_id = c.id 
        LEFT JOIN users u ON r.user_id = u.id 
        WHERE r.id = ? AND r.user_id = ?
    ");
    $stmt->execute([$rental_id, $_SESSION['user_id']]);
    $rental = $stmt->fetch();
    
    if (!$rental) {
        redirect('my_bookings.php');
    }
} catch (PDOException $e) {
    redirect('my_bookings.php');
}

// Handle payment submission
if ($_POST) {
    $payment_method = sanitize_input($_POST['payment_method']);
    $card_number = sanitize_input($_POST['card_number']);
    $expiry_date = sanitize_input($_POST['expiry_date']);
    $cvv = sanitize_input($_POST['cvv']);
    $cardholder_name = sanitize_input($_POST['cardholder_name']);
    
    // Basic validation (in real application, use proper payment gateway)
    if (empty($payment_method) || empty($card_number) || empty($expiry_date) || empty($cvv) || empty($cardholder_name)) {
        $error_message = "Please fill in all payment details.";
    } else {
        try {
            // Update rental with payment information
            $stmt = $pdo->prepare("
                UPDATE rentals SET 
                    payment_status = 'paid',
                    payment_method = ?,
                    payment_date = NOW(),
                    status = 'confirmed'
                WHERE id = ?
            ");
            $stmt->execute([$payment_method, $rental_id]);
            
            // Log payment
            log_activity($pdo, 'payment_made', "Payment made for rental ID: $rental_id - Amount: " . format_currency($rental['total_cost']));
            
            $success_message = "Payment processed successfully! Your booking has been confirmed.";
            
            // Redirect after 3 seconds
            header("refresh:3;url=my_bookings.php");
        } catch (PDOException $e) {
            $error_message = "Error processing payment: " . $e->getMessage();
        }
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
                    <a href="booking.php" class="text-gray-700 hover:text-primary">Book a Car</a>
                    <a href="my_bookings.php" class="text-gray-700 hover:text-primary">My Bookings</a>
                    <a href="logout.php" class="text-gray-700 hover:text-primary">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto py-6 px-4">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Payment</h1>

        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo $success_message; ?>
                <p class="text-sm mt-1">Redirecting to your bookings...</p>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Booking Summary -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Booking Summary</h2>
                
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Rental ID:</span>
                        <span class="font-semibold">#<?php echo $rental['id']; ?></span>
                    </div>
                    
                    <div class="border-t pt-4">
                        <h3 class="font-semibold mb-2"><?php echo htmlspecialchars($rental['make'] . ' ' . $rental['model'] . ' (' . $rental['year'] . ')'); ?></h3>
                        <p class="text-gray-600"><?php echo htmlspecialchars($rental['color']); ?></p>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Pickup Date:</span>
                            <span><?php echo format_date($rental['start_date']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Return Date:</span>
                            <span><?php echo format_date($rental['end_date']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Daily Rate:</span>
                            <span><?php echo format_currency($rental['daily_rate']); ?></span>
                        </div>
                        
                        <?php
                        $start = new DateTime($rental['start_date']);
                        $end = new DateTime($rental['end_date']);
                        $days = $end->diff($start)->days + 1;
                        ?>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Days:</span>
                            <span><?php echo $days; ?> day(s)</span>
                        </div>
                    </div>
                    
                    <div class="border-t pt-4">
                        <div class="flex justify-between text-lg font-bold">
                            <span>Total Amount:</span>
                            <span class="text-primary"><?php echo format_currency($rental['total_cost']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Payment Details</h2>
                
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                        <select name="payment_method" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">Select Payment Method</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="debit_card">Debit Card</option>
                            <option value="paypal">PayPal</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Card Number</label>
                        <input type="text" name="card_number" placeholder="1234 5678 9012 3456" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                               maxlength="19">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
                            <input type="text" name="expiry_date" placeholder="MM/YY" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                                   maxlength="5">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CVV</label>
                            <input type="text" name="cvv" placeholder="123" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                                   maxlength="4">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cardholder Name</label>
                        <input type="text" name="cardholder_name" placeholder="John Doe" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                        <div class="flex">
                            <i class="fas fa-shield-alt text-yellow-600 mr-2 mt-1"></i>
                            <div class="text-sm text-yellow-800">
                                <p class="font-semibold">Secure Payment</p>
                                <p>Your payment information is encrypted and secure. We use industry-standard security measures to protect your data.</p>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full bg-primary text-white py-3 px-4 rounded-md hover:bg-purple-700 font-semibold">
                        <i class="fas fa-credit-card mr-2"></i>
                        Pay <?php echo format_currency($rental['total_cost']); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Format card number
        document.querySelector('input[name="card_number"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            if (formattedValue !== e.target.value) {
                e.target.value = formattedValue;
            }
        });

        // Format expiry date
        document.querySelector('input[name="expiry_date"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });

        // Only allow numbers for CVV
        document.querySelector('input[name="cvv"]').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>
