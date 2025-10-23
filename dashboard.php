<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$page_title = "Dashboard - CarRent Pro";
$current_page = 'dashboard';

// Get dashboard statistics based on user role
try {
    if (is_super_admin() || is_admin()) {
        // Admin/Super Admin statistics
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM cars WHERE status != 'inactive'");
        $total_cars = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM cars WHERE status = 'available'");
        $available_cars = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM rentals");
        $total_rentals = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM rentals WHERE status = 'active'");
        $active_rentals = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
        $total_users = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM chat_messages WHERE read_status = 0");
        $unread_messages = $stmt->fetch()['total'];
        
        // Recent rentals
        $stmt = $pdo->prepare("
            SELECT r.*, u.name as user_name, c.make, c.model, c.year 
            FROM rentals r 
            LEFT JOIN users u ON r.user_id = u.id 
            LEFT JOIN cars c ON r.car_id = c.id 
            ORDER BY r.created_at DESC 
            LIMIT 5
        ");
        $stmt->execute();
        $recent_rentals = $stmt->fetchAll();
        
    } else {
        // Regular user statistics
        $user_id = $_SESSION['user_id'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM rentals WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $total_rentals = $stmt->fetch()['total'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM rentals WHERE user_id = ? AND status = 'active'");
        $stmt->execute([$user_id]);
        $active_rentals = $stmt->fetch()['total'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM rentals WHERE user_id = ? AND status = 'completed'");
        $stmt->execute([$user_id]);
        $completed_rentals = $stmt->fetch()['total'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM chat_messages WHERE user_id = ? AND is_admin = 1 AND read_status = 0");
        $stmt->execute([$user_id]);
        $unread_messages = $stmt->fetch()['total'];
        
        // User's recent rentals
        $stmt = $pdo->prepare("
            SELECT r.*, c.make, c.model, c.year, c.image 
            FROM rentals r 
            LEFT JOIN cars c ON r.car_id = c.id 
            WHERE r.user_id = ? 
            ORDER BY r.created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
        $recent_rentals = $stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Log dashboard access
log_activity($pdo, 'dashboard_access', 'Accessed dashboard');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#7c3aed',
                        secondary: '#f8fafc'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100">
    <!-- Top Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <a href="index.php" class="text-2xl font-bold text-primary mr-8">CarRent Pro</a>
                    <div class="hidden md:flex md:space-x-8">
                        <a href="dashboard.php" class="text-gray-900 hover:text-primary px-3 py-2 text-sm font-medium">Dashboard</a>
                        <a href="cars.php" class="text-gray-500 hover:text-primary px-3 py-2 text-sm font-medium">Browse Cars</a>
                        <a href="booking.php" class="text-gray-500 hover:text-primary px-3 py-2 text-sm font-medium">Book a Car</a>
                            <a href="my_bookings.php" class="text-gray-500 hover:text-primary px-3 py-2 text-sm font-medium">My Bookings</a>
                            <a href="profile.php" class="text-gray-500 hover:text-primary px-3 py-2 text-sm font-medium">Profile</a>
                            <a href="chat.php" class="text-gray-500 hover:text-primary px-3 py-2 text-sm font-medium relative">
                                Chat
                                <?php if ($unread_messages > 0): ?>
                                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                        <?php echo $unread_messages; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        <?php if (is_admin()): ?>
                            <a href="admin/cars.php" class="text-gray-500 hover:text-primary px-3 py-2 text-sm font-medium">Admin Panel</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <?php echo get_user_role_badge($_SESSION['user_role']); ?>
                        <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
                    </div>
                    <a href="logout.php" class="text-gray-500 hover:text-primary px-3 py-2 text-sm font-medium">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-gray-600">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <?php if (is_super_admin() || is_admin()): ?>
                <!-- Admin/Super Admin Stats -->
                <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Cars</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $total_cars; ?></p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-car text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-sm text-green-600">
                            <i class="fas fa-arrow-up mr-1"></i>
                            +12% from last month
                        </span>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Available Cars</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $available_cars; ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-sm text-green-600">
                            <i class="fas fa-arrow-up mr-1"></i>
                            +8% from last week
                        </span>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Users</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $total_users; ?></p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-users text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-sm text-green-600">
                            <i class="fas fa-arrow-up mr-1"></i>
                            +24% from last month
                        </span>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Unread Messages</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $unread_messages; ?></p>
                        </div>
                        <div class="bg-orange-100 p-3 rounded-full">
                            <i class="fas fa-comments text-orange-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="chat.php" class="text-sm text-blue-600 hover:underline">View Chat</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- User Stats -->
                <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Rentals</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $total_rentals; ?></p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-calendar-check text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-sm text-gray-500">All time</span>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Active Rentals</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $active_rentals; ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-car text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-sm text-green-600">Currently active</span>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Completed</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $completed_rentals; ?></p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-check text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-sm text-gray-500">All time</span>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Unread Messages</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $unread_messages; ?></p>
                        </div>
                        <div class="bg-orange-100 p-3 rounded-full">
                            <i class="fas fa-comments text-orange-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="chat.php" class="text-sm text-blue-600 hover:underline">View Chat</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions Section -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <a href="cars.php" class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="bg-blue-100 p-3 rounded-full mr-4">
                            <i class="fas fa-search text-blue-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Browse Cars</h3>
                            <p class="text-sm text-gray-600">Find your perfect car</p>
                        </div>
                    </div>
                </a>

                <a href="booking.php" class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="bg-green-100 p-3 rounded-full mr-4">
                            <i class="fas fa-calendar-plus text-green-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Book a Car</h3>
                            <p class="text-sm text-gray-600">Make a new reservation</p>
                        </div>
                    </div>
                </a>

                <a href="my_bookings.php" class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="bg-purple-100 p-3 rounded-full mr-4">
                            <i class="fas fa-list text-purple-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">My Bookings</h3>
                            <p class="text-sm text-gray-600">View your reservations</p>
                        </div>
                    </div>
                </a>

                <a href="profile.php" class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="bg-indigo-100 p-3 rounded-full mr-4">
                            <i class="fas fa-user text-indigo-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">My Profile</h3>
                            <p class="text-sm text-gray-600">Manage your account</p>
                        </div>
                    </div>
                </a>

                <a href="chat.php" class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 hover:shadow-md transition-shadow relative">
                    <div class="flex items-center">
                        <div class="bg-orange-100 p-3 rounded-full mr-4">
                            <i class="fas fa-comments text-orange-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Live Chat</h3>
                            <p class="text-sm text-gray-600">Get support</p>
                        </div>
                    </div>
                    <?php if ($unread_messages > 0): ?>
                        <span class="absolute top-2 right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                            <?php echo $unread_messages; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Recent Activity -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">
                                <?php echo is_admin() ? 'Recent Rentals' : 'My Recent Rentals'; ?>
                            </h3>
                            <a href="<?php echo is_admin() ? 'admin/rentals.php' : 'my-rentals.php'; ?>" class="text-primary hover:text-purple-700 text-sm font-medium">View All</a>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <?php if (is_admin()): ?>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <?php endif; ?>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Car</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (!empty($recent_rentals)): ?>
                                    <?php foreach ($recent_rentals as $rental): ?>
                                        <tr class="hover:bg-gray-50">
                                            <?php if (is_admin()): ?>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($rental['user_name']); ?></div>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($rental['year'] . ' ' . $rental['make'] . ' ' . $rental['model']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo format_date($rental['start_date']); ?> - <?php echo format_date($rental['end_date']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php echo get_rental_status_badge($rental['status']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo format_currency($rental['total_cost']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?php echo is_admin() ? '5' : '4'; ?>" class="px-6 py-4 text-center text-gray-500">
                                            No recent rentals found
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <a href="cars.php" class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition duration-200">
                            <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                <i class="fas fa-search text-blue-600"></i>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">Browse Cars</div>
                                <div class="text-sm text-gray-500">Find your perfect rental car</div>
                            </div>
                        </a>
                        
                        <a href="chat.php" class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition duration-200">
                            <div class="bg-green-100 p-2 rounded-lg mr-3">
                                <i class="fas fa-comments text-green-600"></i>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">Live Chat</div>
                                <div class="text-sm text-gray-500">Get instant support</div>
                            </div>
                        </a>
                        
                        <a href="profile.php" class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition duration-200">
                            <div class="bg-purple-100 p-2 rounded-lg mr-3">
                                <i class="fas fa-user text-purple-600"></i>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">Edit Profile</div>
                                <div class="text-sm text-gray-500">Update your account</div>
                            </div>
                        </a>
                        
                        <?php if (is_super_admin()): ?>
                            <a href="admin/users.php" class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition duration-200">
                                <div class="bg-red-100 p-2 rounded-lg mr-3">
                                    <i class="fas fa-crown text-red-600"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">Super Admin</div>
                                    <div class="text-sm text-gray-500">Manage system access</div>
                                </div>
                            </a>
                        <?php elseif (is_admin()): ?>
                            <a href="admin/cars.php" class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition duration-200">
                                <div class="bg-yellow-100 p-2 rounded-lg mr-3">
                                    <i class="fas fa-cog text-yellow-600"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">Admin Panel</div>
                                    <div class="text-sm text-gray-500">Manage content</div>
                                </div>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-8 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-gray-400">&copy; 2024 CarRent Pro. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>