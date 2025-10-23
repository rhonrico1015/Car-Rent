<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!is_admin()) {
    redirect('../login.php');
}

$page_title = "Cars Management - CarRent Pro";
$current_page = 'admin_cars';

// Handle CRUD operations
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $make = sanitize_input($_POST['make']);
            $model = sanitize_input($_POST['model']);
            $year = (int)$_POST['year'];
            $color = sanitize_input($_POST['color']);
            $license_plate = sanitize_input($_POST['license_plate']);
            $daily_rate = (float)$_POST['daily_rate'];
            $description = sanitize_input($_POST['description']);
            $features = sanitize_input($_POST['features']);
            $fuel_type = sanitize_input($_POST['fuel_type']);
            $transmission = sanitize_input($_POST['transmission']);
            $seats = (int)$_POST['seats'];
            $location = sanitize_input($_POST['location']);
            $mileage = (int)$_POST['mileage'];
            $insurance_expiry = $_POST['insurance_expiry'];
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO cars (make, model, year, color, license_plate, daily_rate, description, features, 
                                    fuel_type, transmission, seats, location, mileage, insurance_expiry, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')
                ");
                $stmt->execute([$make, $model, $year, $color, $license_plate, $daily_rate, $description, $features, 
                              $fuel_type, $transmission, $seats, $location, $mileage, $insurance_expiry]);
                
                $success_message = "Car added successfully!";
                log_activity($pdo, 'car_added', "Added car: $make $model");
            } catch (PDOException $e) {
                $error_message = "Error adding car: " . $e->getMessage();
            }
            break;
            
        case 'edit':
            $id = (int)$_POST['id'];
            $make = sanitize_input($_POST['make']);
            $model = sanitize_input($_POST['model']);
            $year = (int)$_POST['year'];
            $color = sanitize_input($_POST['color']);
            $license_plate = sanitize_input($_POST['license_plate']);
            $daily_rate = (float)$_POST['daily_rate'];
            $description = sanitize_input($_POST['description']);
            $features = sanitize_input($_POST['features']);
            $fuel_type = sanitize_input($_POST['fuel_type']);
            $transmission = sanitize_input($_POST['transmission']);
            $seats = (int)$_POST['seats'];
            $location = sanitize_input($_POST['location']);
            $mileage = (int)$_POST['mileage'];
            $insurance_expiry = $_POST['insurance_expiry'];
            $status = sanitize_input($_POST['status']);
            
            try {
                $stmt = $pdo->prepare("
                    UPDATE cars SET make = ?, model = ?, year = ?, color = ?, license_plate = ?, daily_rate = ?, 
                                   description = ?, features = ?, fuel_type = ?, transmission = ?, seats = ?, 
                                   location = ?, mileage = ?, insurance_expiry = ?, status = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$make, $model, $year, $color, $license_plate, $daily_rate, $description, $features, 
                              $fuel_type, $transmission, $seats, $location, $mileage, $insurance_expiry, $status, $id]);
                
                $success_message = "Car updated successfully!";
                log_activity($pdo, 'car_updated', "Updated car: $make $model");
            } catch (PDOException $e) {
                $error_message = "Error updating car: " . $e->getMessage();
            }
            break;
            
        case 'delete':
            $id = (int)$_POST['id'];
            try {
                $stmt = $pdo->prepare("SELECT make, model FROM cars WHERE id = ?");
                $stmt->execute([$id]);
                $car = $stmt->fetch();
                
                $stmt = $pdo->prepare("DELETE FROM cars WHERE id = ?");
                $stmt->execute([$id]);
                
                $success_message = "Car deleted successfully!";
                log_activity($pdo, 'car_deleted', "Deleted car: {$car['make']} {$car['model']}");
            } catch (PDOException $e) {
                $error_message = "Error deleting car: " . $e->getMessage();
            }
            break;
    }
}

// Get all cars
$cars = [];
try {
    $stmt = $pdo->query("SELECT * FROM cars ORDER BY created_at DESC");
    $cars = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error fetching cars: " . $e->getMessage();
}

// Get car for editing
$edit_car = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
        $stmt->execute([$id]);
        $edit_car = $stmt->fetch();
    } catch (PDOException $e) {
        $error_message = "Error fetching car: " . $e->getMessage();
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
                    <a href="../dashboard.php" class="text-xl font-bold text-primary">CarRent Pro</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="../dashboard.php" class="text-gray-700 hover:text-primary">Dashboard</a>
                    <a href="cars.php" class="text-primary font-semibold">Cars</a>
                    <a href="rentals.php" class="text-gray-700 hover:text-primary">Rentals</a>
                    <a href="users.php" class="text-gray-700 hover:text-primary">Users</a>
                    <a href="../logout.php" class="text-gray-700 hover:text-primary">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Cars Management</h1>
            <button onclick="openAddModal()" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                <i class="fas fa-plus mr-2"></i>Add New Car
            </button>
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

        <!-- Cars Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Car</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">License Plate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Daily Rate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($cars as $car): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($car['year'] . ' - ' . $car['color']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo htmlspecialchars($car['license_plate']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo format_currency($car['daily_rate']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php echo $car['status'] === 'available' ? 'bg-green-100 text-green-800' : 
                                          ($car['status'] === 'rented' ? 'bg-red-100 text-red-800' : 
                                          'bg-yellow-100 text-yellow-800'); ?>">
                                <?php echo ucfirst($car['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo htmlspecialchars($car['location']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="openEditModal(<?php echo $car['id']; ?>)" class="text-primary hover:text-purple-700 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteCar(<?php echo $car['id']; ?>)" class="text-red-600 hover:text-red-900">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Car Modal -->
    <div id="carModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-screen overflow-y-auto">
                <div class="px-6 py-4 border-b">
                    <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">Add New Car</h3>
                </div>
                <form id="carForm" method="POST" class="p-6">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="carId">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Make</label>
                            <input type="text" name="make" id="make" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                            <input type="text" name="model" id="model" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                            <input type="number" name="year" id="year" min="1900" max="2030" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                            <input type="text" name="color" id="color" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">License Plate</label>
                            <input type="text" name="license_plate" id="license_plate" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Daily Rate ($)</label>
                            <input type="number" name="daily_rate" id="daily_rate" step="0.01" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fuel Type</label>
                            <select name="fuel_type" id="fuel_type" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="gasoline">Gasoline</option>
                                <option value="diesel">Diesel</option>
                                <option value="electric">Electric</option>
                                <option value="hybrid">Hybrid</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Transmission</label>
                            <select name="transmission" id="transmission" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="automatic">Automatic</option>
                                <option value="manual">Manual</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Seats</label>
                            <input type="number" name="seats" id="seats" min="1" max="20" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mileage</label>
                            <input type="number" name="mileage" id="mileage" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                            <input type="text" name="location" id="location" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Insurance Expiry</label>
                            <input type="date" name="insurance_expiry" id="insurance_expiry" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div id="statusField" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="available">Available</option>
                                <option value="rented">Rented</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" id="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                    </div>
                    
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Features</label>
                        <textarea name="features" id="features" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-md hover:bg-purple-700">
                            Save Car
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Car';
            document.getElementById('formAction').value = 'add';
            document.getElementById('carForm').reset();
            document.getElementById('statusField').classList.add('hidden');
            document.getElementById('carModal').classList.remove('hidden');
        }

        function openEditModal(carId) {
            // Fetch car data via AJAX and populate form
            fetch(`get_car.php?id=${carId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('modalTitle').textContent = 'Edit Car';
                    document.getElementById('formAction').value = 'edit';
                    document.getElementById('carId').value = data.id;
                    document.getElementById('make').value = data.make;
                    document.getElementById('model').value = data.model;
                    document.getElementById('year').value = data.year;
                    document.getElementById('color').value = data.color;
                    document.getElementById('license_plate').value = data.license_plate;
                    document.getElementById('daily_rate').value = data.daily_rate;
                    document.getElementById('description').value = data.description || '';
                    document.getElementById('features').value = data.features || '';
                    document.getElementById('fuel_type').value = data.fuel_type;
                    document.getElementById('transmission').value = data.transmission;
                    document.getElementById('seats').value = data.seats;
                    document.getElementById('location').value = data.location;
                    document.getElementById('mileage').value = data.mileage || 0;
                    document.getElementById('insurance_expiry').value = data.insurance_expiry || '';
                    document.getElementById('status').value = data.status;
                    document.getElementById('statusField').classList.remove('hidden');
                    document.getElementById('carModal').classList.remove('hidden');
                });
        }

        function closeModal() {
            document.getElementById('carModal').classList.add('hidden');
        }

        function deleteCar(carId) {
            if (confirm('Are you sure you want to delete this car?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${carId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        document.getElementById('carModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
