<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!is_admin()) {
    redirect('../login.php');
}

$page_title = "Products Management - CarRent Pro";
$current_page = 'admin_products';

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
            $category = sanitize_input($_POST['category']);
            $brand = sanitize_input($_POST['brand']);
            
            // Handle image upload
            $image_path = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_result = upload_car_image($_FILES['image']);
                if ($upload_result) {
                    $image_path = $upload_result['filename'];
                }
            }
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO cars (make, model, year, color, license_plate, daily_rate, description, features, 
                                    fuel_type, transmission, seats, location, mileage, insurance_expiry, image, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')
                ");
                $stmt->execute([$make, $model, $year, $color, $license_plate, $daily_rate, $description, $features, 
                              $fuel_type, $transmission, $seats, $location, $mileage, $insurance_expiry, $image_path]);
                
                $success_message = "Product added successfully!";
                log_activity($pdo, 'product_added', "Added product: $make $model");
            } catch (PDOException $e) {
                $error_message = "Error adding product: " . $e->getMessage();
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
            
            // Handle image update
            $current_image = $_POST['current_image'] ?? '';
            $image_path = $current_image;
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_result = upload_car_image($_FILES['image']);
                if ($upload_result) {
                    // Delete old image if exists
                    if ($current_image && file_exists('uploads/cars/' . $current_image)) {
                        unlink('uploads/cars/' . $current_image);
                    }
                    $image_path = $upload_result['filename'];
                }
            }
            
            try {
                $stmt = $pdo->prepare("
                    UPDATE cars SET make = ?, model = ?, year = ?, color = ?, license_plate = ?, daily_rate = ?, 
                                   description = ?, features = ?, fuel_type = ?, transmission = ?, seats = ?, 
                                   location = ?, mileage = ?, insurance_expiry = ?, image = ?, status = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$make, $model, $year, $color, $license_plate, $daily_rate, $description, $features, 
                              $fuel_type, $transmission, $seats, $location, $mileage, $insurance_expiry, $image_path, $status, $id]);
                
                $success_message = "Product updated successfully!";
                log_activity($pdo, 'product_updated', "Updated product: $make $model");
            } catch (PDOException $e) {
                $error_message = "Error updating product: " . $e->getMessage();
            }
            break;
            
        case 'delete':
            $id = (int)$_POST['id'];
            try {
                // Get product details for logging
                $stmt = $pdo->prepare("SELECT make, model, image FROM cars WHERE id = ?");
                $stmt->execute([$id]);
                $product = $stmt->fetch();
                
                // Delete image file if exists
                if ($product['image'] && file_exists('uploads/cars/' . $product['image'])) {
                    unlink('uploads/cars/' . $product['image']);
                }
                
                $stmt = $pdo->prepare("DELETE FROM cars WHERE id = ?");
                $stmt->execute([$id]);
                
                $success_message = "Product deleted successfully!";
                log_activity($pdo, 'product_deleted', "Deleted product: {$product['make']} {$product['model']}");
            } catch (PDOException $e) {
                $error_message = "Error deleting product: " . $e->getMessage();
            }
            break;
    }
}

// Get all products
$products = [];
try {
    $stmt = $pdo->query("SELECT * FROM cars ORDER BY created_at DESC");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error fetching products: " . $e->getMessage();
}

// Get product for editing
$edit_product = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
        $stmt->execute([$id]);
        $edit_product = $stmt->fetch();
    } catch (PDOException $e) {
        $error_message = "Error fetching product: " . $e->getMessage();
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
                    <a href="cars.php" class="text-gray-700 hover:text-primary">Cars</a>
                    <a href="products.php" class="text-primary font-semibold">Products</a>
                    <a href="rentals.php" class="text-gray-700 hover:text-primary">Rentals</a>
                    <a href="calendar.php" class="text-gray-700 hover:text-primary">Calendar</a>
                    <a href="users.php" class="text-gray-700 hover:text-primary">Users</a>
                    <a href="homepage.php" class="text-gray-700 hover:text-primary">Homepage</a>
                    <a href="../logout.php" class="text-gray-700 hover:text-primary">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Products Management</h1>
            <button onclick="openAddModal()" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                <i class="fas fa-plus mr-2"></i>Add New Product
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

        <!-- Products Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($products as $product): ?>
            <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
                <!-- Product Image -->
                <div class="h-48 bg-gray-200 rounded-t-lg flex items-center justify-center overflow-hidden">
                    <?php if ($product['image']): ?>
                        <img src="uploads/cars/<?php echo htmlspecialchars($product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['make'] . ' ' . $product['model']); ?>" 
                             class="w-full h-full object-cover">
                    <?php else: ?>
                        <i class="fas fa-car text-4xl text-gray-400"></i>
                    <?php endif; ?>
                </div>

                <div class="p-6">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <?php echo htmlspecialchars($product['make'] . ' ' . $product['model']); ?>
                        </h3>
                        <span class="text-lg font-bold text-primary">
                            <?php echo format_currency($product['daily_rate']); ?>/day
                        </span>
                    </div>

                    <p class="text-gray-600 mb-2"><?php echo htmlspecialchars($product['year'] . ' - ' . $product['color']); ?></p>
                    
                    <div class="flex items-center text-sm text-gray-500 mb-4">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                            <?php echo $product['status'] === 'available' ? 'bg-green-100 text-green-800' : 
                                      ($product['status'] === 'rented' ? 'bg-red-100 text-red-800' : 
                                      'bg-yellow-100 text-yellow-800'); ?>">
                            <?php echo ucfirst($product['status']); ?>
                        </span>
                    </div>

                    <?php if ($product['description']): ?>
                    <p class="text-sm text-gray-600 mb-4"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?><?php echo strlen($product['description']) > 100 ? '...' : ''; ?></p>
                    <?php endif; ?>

                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500">
                            License: <?php echo htmlspecialchars($product['license_plate']); ?>
                        </span>
                        
                        <div class="flex space-x-2">
                            <button onclick="openEditModal(<?php echo $product['id']; ?>)" 
                                    class="text-primary hover:text-purple-700">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteProduct(<?php echo $product['id']; ?>)" 
                                    class="text-red-600 hover:text-red-900">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Add/Edit Product Modal -->
    <div id="productModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-screen overflow-y-auto">
                <div class="px-6 py-4 border-b">
                    <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">Add New Product</h3>
                </div>
                <form id="productForm" method="POST" enctype="multipart/form-data" class="p-6">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="productId">
                    <input type="hidden" name="current_image" id="currentImage">
                    
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
                    
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Image</label>
                        <input type="file" name="image" id="image" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        <p class="text-xs text-gray-500 mt-1">Accepted formats: JPG, PNG, GIF. Max size: 5MB</p>
                        <div id="currentImagePreview" class="mt-2 hidden">
                            <p class="text-sm text-gray-600">Current image:</p>
                            <img id="currentImageDisplay" class="w-20 h-20 object-cover rounded mt-1" alt="Current image">
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-md hover:bg-purple-700">
                            Save Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Product';
            document.getElementById('formAction').value = 'add';
            document.getElementById('productForm').reset();
            document.getElementById('statusField').classList.add('hidden');
            document.getElementById('currentImagePreview').classList.add('hidden');
            document.getElementById('productModal').classList.remove('hidden');
        }

        function openEditModal(productId) {
            // Fetch product data via AJAX and populate form
            fetch(`get_product.php?id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('modalTitle').textContent = 'Edit Product';
                    document.getElementById('formAction').value = 'edit';
                    document.getElementById('productId').value = data.id;
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
                    document.getElementById('currentImage').value = data.image || '';
                    
                    if (data.image) {
                        document.getElementById('currentImageDisplay').src = 'uploads/cars/' + data.image;
                        document.getElementById('currentImagePreview').classList.remove('hidden');
                    } else {
                        document.getElementById('currentImagePreview').classList.add('hidden');
                    }
                    
                    document.getElementById('statusField').classList.remove('hidden');
                    document.getElementById('productModal').classList.remove('hidden');
                });
        }

        function closeModal() {
            document.getElementById('productModal').classList.add('hidden');
        }

        function deleteProduct(productId) {
            if (confirm('Are you sure you want to delete this product?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${productId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        document.getElementById('productModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Image preview
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // You can add image preview here if needed
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
