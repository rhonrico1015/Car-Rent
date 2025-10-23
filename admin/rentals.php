<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!is_admin()) {
    redirect('../login.php');
}

$page_title = "Rentals Management - CarRent Pro";
$current_page = 'admin_rentals';

// Handle rental status updates
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_status':
            $rental_id = (int)$_POST['rental_id'];
            $status = sanitize_input($_POST['status']);
            $admin_notes = sanitize_input($_POST['admin_notes'] ?? '');
            
            try {
                $stmt = $pdo->prepare("UPDATE rentals SET status = ?, admin_notes = ? WHERE id = ?");
                $stmt->execute([$status, $admin_notes, $rental_id]);
                
                // Update car status based on rental status
                if ($status === 'active') {
                    $stmt = $pdo->prepare("UPDATE cars SET status = 'rented' WHERE id = (SELECT car_id FROM rentals WHERE id = ?)");
                    $stmt->execute([$rental_id]);
                } elseif (in_array($status, ['completed', 'cancelled'])) {
                    $stmt = $pdo->prepare("UPDATE cars SET status = 'available' WHERE id = (SELECT car_id FROM rentals WHERE id = ?)");
                    $stmt->execute([$rental_id]);
                }
                
                $success_message = "Rental status updated successfully!";
                log_activity($pdo, 'rental_updated', "Updated rental ID: $rental_id to status: $status");
            } catch (PDOException $e) {
                $error_message = "Error updating rental: " . $e->getMessage();
            }
            break;
    }
}

// Get all rentals with user and car information
$rentals = [];
try {
    $stmt = $pdo->query("
        SELECT r.*, u.name as user_name, u.email as user_email, 
               c.make, c.model, c.year, c.license_plate, c.daily_rate
        FROM rentals r 
        LEFT JOIN users u ON r.user_id = u.id 
        LEFT JOIN cars c ON r.car_id = c.id 
        ORDER BY r.created_at DESC
    ");
    $rentals = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error fetching rentals: " . $e->getMessage();
}

// Get statistics
$stats = [];
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM rentals");
    $stats['total'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as pending FROM rentals WHERE status = 'pending'");
    $stats['pending'] = $stmt->fetch()['pending'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as active FROM rentals WHERE status = 'active'");
    $stats['active'] = $stmt->fetch()['active'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as completed FROM rentals WHERE status = 'completed'");
    $stats['completed'] = $stmt->fetch()['completed'];
    
    $stmt = $pdo->query("SELECT SUM(total_cost) as revenue FROM rentals WHERE status = 'completed'");
    $stats['revenue'] = $stmt->fetch()['revenue'] ?? 0;
} catch (PDOException $e) {
    // Handle error
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
                    <a href="../dashboard.php" class="text-xl font-bold text-primary">CarRent Pro</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="../dashboard.php" class="text-gray-700 hover:text-primary">Dashboard</a>
                    <a href="cars.php" class="text-gray-700 hover:text-primary">Cars</a>
                    <a href="rentals.php" class="text-primary font-semibold">Rentals</a>
                    <a href="users.php" class="text-gray-700 hover:text-primary">Users</a>
                    <a href="../logout.php" class="text-gray-700 hover:text-primary">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Rentals Management</h1>
            <a href="calendar.php" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                <i class="fas fa-calendar mr-2"></i>Calendar View
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i class="fas fa-list text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Rentals</p>
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
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <i class="fas fa-dollar-sign text-purple-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Revenue</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo format_currency($stats['revenue']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Rentals Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rental ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Car</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Cost</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($rentals as $rental): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            #<?php echo $rental['id']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($rental['user_name']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($rental['user_email']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($rental['make'] . ' ' . $rental['model']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($rental['year'] . ' - ' . $rental['license_plate']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div><?php echo format_date($rental['start_date']); ?></div>
                            <div class="text-gray-500">to <?php echo format_date($rental['end_date']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo format_currency($rental['total_cost']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php 
                                switch($rental['status']) {
                                    case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                    case 'confirmed': echo 'bg-blue-100 text-blue-800'; break;
                                    case 'active': echo 'bg-green-100 text-green-800'; break;
                                    case 'completed': echo 'bg-gray-100 text-gray-800'; break;
                                    case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                    default: echo 'bg-gray-100 text-gray-800';
                                }
                                ?>">
                                <?php echo ucfirst($rental['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="openStatusModal(<?php echo $rental['id']; ?>, '<?php echo $rental['status']; ?>', '<?php echo htmlspecialchars($rental['admin_notes'] ?? ''); ?>')" 
                                    class="text-primary hover:text-purple-700 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">Update Rental Status</h3>
                </div>
                <form method="POST" class="p-6">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="rental_id" id="rentalId">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="status" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Admin Notes</label>
                        <textarea name="admin_notes" id="adminNotes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeStatusModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-md hover:bg-purple-700">
                            Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openStatusModal(rentalId, currentStatus, adminNotes) {
            document.getElementById('rentalId').value = rentalId;
            document.getElementById('status').value = currentStatus;
            document.getElementById('adminNotes').value = adminNotes;
            document.getElementById('statusModal').classList.remove('hidden');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeStatusModal();
            }
        });
    </script>
</body>
</html>
