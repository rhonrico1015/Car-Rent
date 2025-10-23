<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "Browse Cars - CarRent Pro";
$current_page = 'cars';

// Handle search and filters
$search = $_GET['search'] ?? '';
$make_filter = $_GET['make'] ?? '';
$fuel_type_filter = $_GET['fuel_type'] ?? '';
$transmission_filter = $_GET['transmission'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$sort_by = $_GET['sort'] ?? 'make';

// Build query
$where_conditions = ["status = 'available'"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(make LIKE ? OR model LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($make_filter)) {
    $where_conditions[] = "make = ?";
    $params[] = $make_filter;
}

if (!empty($fuel_type_filter)) {
    $where_conditions[] = "fuel_type = ?";
    $params[] = $fuel_type_filter;
}

if (!empty($transmission_filter)) {
    $where_conditions[] = "transmission = ?";
    $params[] = $transmission_filter;
}

if (!empty($min_price)) {
    $where_conditions[] = "daily_rate >= ?";
    $params[] = (float)$min_price;
}

if (!empty($max_price)) {
    $where_conditions[] = "daily_rate <= ?";
    $params[] = (float)$max_price;
}

$where_clause = implode(' AND ', $where_conditions);

// Sort options
$sort_options = [
    'make' => 'make ASC, model ASC',
    'price_low' => 'daily_rate ASC',
    'price_high' => 'daily_rate DESC',
    'year_new' => 'year DESC',
    'year_old' => 'year ASC'
];

$order_by = $sort_options[$sort_by] ?? $sort_options['make'];

// Get cars
$cars = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM cars WHERE $where_clause ORDER BY $order_by");
    $stmt->execute($params);
    $cars = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error fetching cars: " . $e->getMessage();
}

// Get unique makes for filter
$makes = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT make FROM cars WHERE status = 'available' ORDER BY make");
    $makes = $stmt->fetchAll(PDO::FETCH_COLUMN);
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
                    <a href="dashboard.php" class="text-xl font-bold text-primary">CarRent Pro</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 hover:text-primary">Dashboard</a>
                    <a href="cars.php" class="text-primary font-semibold">Browse Cars</a>
                    <a href="booking.php" class="text-gray-700 hover:text-primary">Book a Car</a>
                    <a href="my_bookings.php" class="text-gray-700 hover:text-primary">My Bookings</a>
                    <?php if (is_logged_in()): ?>
                        <a href="logout.php" class="text-gray-700 hover:text-primary">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-700 hover:text-primary">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 px-4">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Browse Available Cars</h1>

        <!-- Search and Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Make, model, or description..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>

                    <!-- Make Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Make</label>
                        <select name="make" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">All Makes</option>
                            <?php foreach ($makes as $make): ?>
                                <option value="<?php echo htmlspecialchars($make); ?>" 
                                        <?php echo $make_filter === $make ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($make); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Fuel Type Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fuel Type</label>
                        <select name="fuel_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">All Types</option>
                            <option value="gasoline" <?php echo $fuel_type_filter === 'gasoline' ? 'selected' : ''; ?>>Gasoline</option>
                            <option value="diesel" <?php echo $fuel_type_filter === 'diesel' ? 'selected' : ''; ?>>Diesel</option>
                            <option value="electric" <?php echo $fuel_type_filter === 'electric' ? 'selected' : ''; ?>>Electric</option>
                            <option value="hybrid" <?php echo $fuel_type_filter === 'hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                        </select>
                    </div>

                    <!-- Transmission Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Transmission</label>
                        <select name="transmission" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">All</option>
                            <option value="automatic" <?php echo $transmission_filter === 'automatic' ? 'selected' : ''; ?>>Automatic</option>
                            <option value="manual" <?php echo $transmission_filter === 'manual' ? 'selected' : ''; ?>>Manual</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Price Range -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Min Price ($)</label>
                        <input type="number" name="min_price" value="<?php echo htmlspecialchars($min_price); ?>" 
                               min="0" step="0.01" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Max Price ($)</label>
                        <input type="number" name="max_price" value="<?php echo htmlspecialchars($max_price); ?>" 
                               min="0" step="0.01" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                        <select name="sort" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="make" <?php echo $sort_by === 'make' ? 'selected' : ''; ?>>Make & Model</option>
                            <option value="price_low" <?php echo $sort_by === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort_by === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="year_new" <?php echo $sort_by === 'year_new' ? 'selected' : ''; ?>>Year: Newest First</option>
                            <option value="year_old" <?php echo $sort_by === 'year_old' ? 'selected' : ''; ?>>Year: Oldest First</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-between items-center">
                    <button type="submit" class="bg-primary text-white px-6 py-2 rounded-md hover:bg-purple-700">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                    <a href="cars.php" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-times mr-1"></i>Clear Filters
                    </a>
                </div>
            </form>
        </div>

        <!-- Results Count -->
        <div class="mb-4">
            <p class="text-gray-600">
                Showing <?php echo count($cars); ?> car(s) 
                <?php if (!empty($search) || !empty($make_filter) || !empty($fuel_type_filter) || !empty($transmission_filter) || !empty($min_price) || !empty($max_price)): ?>
                    matching your criteria
                <?php endif; ?>
            </p>
        </div>

        <!-- Cars Grid -->
        <?php if (empty($cars)): ?>
            <div class="bg-white rounded-lg shadow p-12 text-center">
                <i class="fas fa-car text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No Cars Found</h3>
                <p class="text-gray-600 mb-6">Try adjusting your search criteria or filters.</p>
                <a href="cars.php" class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-purple-700">
                    View All Cars
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($cars as $car): ?>
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
                    <!-- Car Image Placeholder -->
                    <div class="h-48 bg-gray-200 rounded-t-lg flex items-center justify-center">
                        <?php if ($car['image']): ?>
                            <img src="<?php echo htmlspecialchars($car['image']); ?>" alt="<?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?>" 
                                 class="w-full h-full object-cover rounded-t-lg">
                        <?php else: ?>
                            <i class="fas fa-car text-4xl text-gray-400"></i>
                        <?php endif; ?>
                    </div>

                    <div class="p-6">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-lg font-semibold text-gray-900">
                                <?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?>
                            </h3>
                            <span class="text-2xl font-bold text-primary">
                                <?php echo format_currency($car['daily_rate']); ?>/day
                            </span>
                        </div>

                        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($car['year'] . ' - ' . $car['color']); ?></p>

                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-gas-pump w-4 mr-2"></i>
                                <?php echo ucfirst($car['fuel_type']); ?>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-cog w-4 mr-2"></i>
                                <?php echo ucfirst($car['transmission']); ?>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-users w-4 mr-2"></i>
                                <?php echo $car['seats']; ?> seats
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-map-marker-alt w-4 mr-2"></i>
                                <?php echo htmlspecialchars($car['location']); ?>
                            </div>
                        </div>

                        <?php if ($car['description']): ?>
                        <p class="text-sm text-gray-600 mb-4"><?php echo htmlspecialchars(substr($car['description'], 0, 100)); ?><?php echo strlen($car['description']) > 100 ? '...' : ''; ?></p>
                        <?php endif; ?>

                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500">
                                License: <?php echo htmlspecialchars($car['license_plate']); ?>
                            </span>
                            
                            <?php if (is_logged_in()): ?>
                                <a href="booking.php?car_id=<?php echo $car['id']; ?>" 
                                   class="bg-primary text-white px-4 py-2 rounded-md hover:bg-purple-700 text-sm">
                                    <i class="fas fa-calendar-plus mr-1"></i>Book Now
                                </a>
                            <?php else: ?>
                                <a href="login.php" 
                                   class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 text-sm">
                                    <i class="fas fa-sign-in-alt mr-1"></i>Login to Book
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-submit form when filters change
        document.querySelectorAll('select[name="make"], select[name="fuel_type"], select[name="transmission"], select[name="sort"]').forEach(select => {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });

        // Debounced search
        let searchTimeout;
        document.querySelector('input[name="search"]').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });
    </script>
</body>
</html>