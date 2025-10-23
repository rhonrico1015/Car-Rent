<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is admin
if (!is_admin()) {
    redirect('../login.php');
}

$page_title = "Homepage Management - CarRent Pro";
$current_page = 'admin_homepage';

// Handle content updates
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_hero':
            $hero_title = sanitize_input($_POST['hero_title']);
            $hero_subtitle = sanitize_input($_POST['hero_subtitle']);
            $hero_button_text = sanitize_input($_POST['hero_button_text']);
            
            // Handle hero background image upload
            $hero_image = get_setting($pdo, 'hero_background_image', '');
            if (isset($_FILES['hero_background']) && $_FILES['hero_background']['error'] === UPLOAD_ERR_OK) {
                $upload_result = upload_homepage_image($_FILES['hero_background'], 'hero');
                if ($upload_result) {
                    // Delete old hero image if exists
                    $old_image = get_setting($pdo, 'hero_background_image', '');
                    if ($old_image && file_exists('uploads/homepage/' . $old_image)) {
                        unlink('uploads/homepage/' . $old_image);
                    }
                    $hero_image = $upload_result;
                }
            }
            
            update_setting($pdo, 'hero_title', $hero_title);
            update_setting($pdo, 'hero_subtitle', $hero_subtitle);
            update_setting($pdo, 'hero_button_text', $hero_button_text);
            update_setting($pdo, 'hero_background_image', $hero_image);
            
            $success_message = "Hero section updated successfully!";
            log_activity($pdo, 'homepage_updated', 'Updated hero section');
            break;
            
        case 'update_features':
            $feature1_title = sanitize_input($_POST['feature1_title']);
            $feature1_description = sanitize_input($_POST['feature1_description']);
            $feature1_icon = sanitize_input($_POST['feature1_icon']);
            
            $feature2_title = sanitize_input($_POST['feature2_title']);
            $feature2_description = sanitize_input($_POST['feature2_description']);
            $feature2_icon = sanitize_input($_POST['feature2_icon']);
            
            $feature3_title = sanitize_input($_POST['feature3_title']);
            $feature3_description = sanitize_input($_POST['feature3_description']);
            $feature3_icon = sanitize_input($_POST['feature3_icon']);
            
            update_setting($pdo, 'feature1_title', $feature1_title);
            update_setting($pdo, 'feature1_description', $feature1_description);
            update_setting($pdo, 'feature1_icon', $feature1_icon);
            
            update_setting($pdo, 'feature2_title', $feature2_title);
            update_setting($pdo, 'feature2_description', $feature2_description);
            update_setting($pdo, 'feature2_icon', $feature2_icon);
            
            update_setting($pdo, 'feature3_title', $feature3_title);
            update_setting($pdo, 'feature3_description', $feature3_description);
            update_setting($pdo, 'feature3_icon', $feature3_icon);
            
            $success_message = "Features section updated successfully!";
            log_activity($pdo, 'homepage_updated', 'Updated features section');
            break;
            
        case 'update_about':
            $about_title = sanitize_input($_POST['about_title']);
            $about_content = sanitize_input($_POST['about_content']);
            
            // Handle about image upload
            $about_image = get_setting($pdo, 'about_image', '');
            if (isset($_FILES['about_image']) && $_FILES['about_image']['error'] === UPLOAD_ERR_OK) {
                $upload_result = upload_homepage_image($_FILES['about_image'], 'about');
                if ($upload_result) {
                    // Delete old about image if exists
                    $old_image = get_setting($pdo, 'about_image', '');
                    if ($old_image && file_exists('uploads/homepage/' . $old_image)) {
                        unlink('uploads/homepage/' . $old_image);
                    }
                    $about_image = $upload_result;
                }
            }
            
            update_setting($pdo, 'about_title', $about_title);
            update_setting($pdo, 'about_content', $about_content);
            update_setting($pdo, 'about_image', $about_image);
            
            $success_message = "About section updated successfully!";
            log_activity($pdo, 'homepage_updated', 'Updated about section');
            break;
            
        case 'update_contact':
            $contact_title = sanitize_input($_POST['contact_title']);
            $contact_address = sanitize_input($_POST['contact_address']);
            $contact_phone = sanitize_input($_POST['contact_phone']);
            $contact_email = sanitize_input($_POST['contact_email']);
            $contact_hours = sanitize_input($_POST['contact_hours']);
            
            update_setting($pdo, 'contact_title', $contact_title);
            update_setting($pdo, 'contact_address', $contact_address);
            update_setting($pdo, 'contact_phone', $contact_phone);
            update_setting($pdo, 'contact_email', $contact_email);
            update_setting($pdo, 'contact_hours', $contact_hours);
            
            $success_message = "Contact section updated successfully!";
            log_activity($pdo, 'homepage_updated', 'Updated contact section');
            break;
    }
}

// Get current settings
$settings = [
    'hero_title' => get_setting($pdo, 'hero_title', 'Welcome to CarRent Pro'),
    'hero_subtitle' => get_setting($pdo, 'hero_subtitle', 'Your premium car rental experience starts here'),
    'hero_button_text' => get_setting($pdo, 'hero_button_text', 'Browse Cars'),
    'hero_background_image' => get_setting($pdo, 'hero_background_image', ''),
    
    'feature1_title' => get_setting($pdo, 'feature1_title', 'Wide Selection'),
    'feature1_description' => get_setting($pdo, 'feature1_description', 'Choose from our extensive fleet of premium vehicles'),
    'feature1_icon' => get_setting($pdo, 'feature1_icon', 'fas fa-car'),
    
    'feature2_title' => get_setting($pdo, 'feature2_title', '24/7 Support'),
    'feature2_description' => get_setting($pdo, 'feature2_description', 'Round-the-clock customer support for all your needs'),
    'feature2_icon' => get_setting($pdo, 'feature2_icon', 'fas fa-headset'),
    
    'feature3_title' => get_setting($pdo, 'feature3_title', 'Best Prices'),
    'feature3_description' => get_setting($pdo, 'feature3_description', 'Competitive rates with no hidden fees'),
    'feature3_icon' => get_setting($pdo, 'feature3_icon', 'fas fa-dollar-sign'),
    
    'about_title' => get_setting($pdo, 'about_title', 'About CarRent Pro'),
    'about_content' => get_setting($pdo, 'about_content', 'We are a leading car rental company committed to providing exceptional service and quality vehicles to our customers.'),
    'about_image' => get_setting($pdo, 'about_image', ''),
    
    'contact_title' => get_setting($pdo, 'contact_title', 'Contact Us'),
    'contact_address' => get_setting($pdo, 'contact_address', '123 Main Street, City, State 12345'),
    'contact_phone' => get_setting($pdo, 'contact_phone', '+1 (555) 123-4567'),
    'contact_email' => get_setting($pdo, 'contact_email', 'info@carrentpro.com'),
    'contact_hours' => get_setting($pdo, 'contact_hours', 'Mon-Fri: 8AM-8PM, Sat-Sun: 9AM-6PM'),
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
                    <a href="../dashboard.php" class="text-xl font-bold text-primary">CarRent Pro</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="../dashboard.php" class="text-gray-700 hover:text-primary">Dashboard</a>
                    <a href="cars.php" class="text-gray-700 hover:text-primary">Cars</a>
                    <a href="products.php" class="text-gray-700 hover:text-primary">Products</a>
                    <a href="rentals.php" class="text-gray-700 hover:text-primary">Rentals</a>
                    <a href="calendar.php" class="text-gray-700 hover:text-primary">Calendar</a>
                    <a href="users.php" class="text-gray-700 hover:text-primary">Users</a>
                    <a href="homepage.php" class="text-primary font-semibold">Homepage</a>
                    <a href="../logout.php" class="text-gray-700 hover:text-primary">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Homepage Content Management</h1>
            <a href="../index.php" target="_blank" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                <i class="fas fa-external-link-alt mr-2"></i>Preview Homepage
            </a>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <div class="space-y-8">
            <!-- Hero Section -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Hero Section</h2>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="update_hero">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hero Title</label>
                        <input type="text" name="hero_title" value="<?php echo htmlspecialchars($settings['hero_title']); ?>" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hero Subtitle</label>
                        <input type="text" name="hero_subtitle" value="<?php echo htmlspecialchars($settings['hero_subtitle']); ?>" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Button Text</label>
                        <input type="text" name="hero_button_text" value="<?php echo htmlspecialchars($settings['hero_button_text']); ?>" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Background Image</label>
                        <input type="file" name="hero_background" accept="image/*" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        <p class="text-xs text-gray-500 mt-1">Accepted formats: JPG, PNG, GIF. Max size: 5MB</p>
                        <?php if ($settings['hero_background_image']): ?>
                            <div class="mt-2">
                                <p class="text-sm text-gray-600">Current image:</p>
                                <img src="uploads/homepage/<?php echo htmlspecialchars($settings['hero_background_image']); ?>" 
                                     class="w-32 h-20 object-cover rounded mt-1" alt="Current hero background">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="bg-primary text-white px-6 py-2 rounded-md hover:bg-purple-700">
                        Update Hero Section
                    </button>
                </form>
            </div>

            <!-- Features Section -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Features Section</h2>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="update_features">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Feature 1 -->
                        <div class="border rounded-lg p-4">
                            <h3 class="font-semibold mb-3">Feature 1</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Icon (FontAwesome class)</label>
                                    <input type="text" name="feature1_icon" value="<?php echo htmlspecialchars($settings['feature1_icon']); ?>" 
                                           placeholder="fas fa-car" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                                    <input type="text" name="feature1_title" value="<?php echo htmlspecialchars($settings['feature1_title']); ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea name="feature1_description" rows="3" 
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"><?php echo htmlspecialchars($settings['feature1_description']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Feature 2 -->
                        <div class="border rounded-lg p-4">
                            <h3 class="font-semibold mb-3">Feature 2</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Icon (FontAwesome class)</label>
                                    <input type="text" name="feature2_icon" value="<?php echo htmlspecialchars($settings['feature2_icon']); ?>" 
                                           placeholder="fas fa-headset" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                                    <input type="text" name="feature2_title" value="<?php echo htmlspecialchars($settings['feature2_title']); ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea name="feature2_description" rows="3" 
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"><?php echo htmlspecialchars($settings['feature2_description']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Feature 3 -->
                        <div class="border rounded-lg p-4">
                            <h3 class="font-semibold mb-3">Feature 3</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Icon (FontAwesome class)</label>
                                    <input type="text" name="feature3_icon" value="<?php echo htmlspecialchars($settings['feature3_icon']); ?>" 
                                           placeholder="fas fa-dollar-sign" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                                    <input type="text" name="feature3_title" value="<?php echo htmlspecialchars($settings['feature3_title']); ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea name="feature3_description" rows="3" 
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"><?php echo htmlspecialchars($settings['feature3_description']); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="bg-primary text-white px-6 py-2 rounded-md hover:bg-purple-700">
                        Update Features Section
                    </button>
                </form>
            </div>

            <!-- About Section -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">About Section</h2>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="update_about">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">About Title</label>
                        <input type="text" name="about_title" value="<?php echo htmlspecialchars($settings['about_title']); ?>" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">About Content</label>
                        <textarea name="about_content" rows="5" required 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"><?php echo htmlspecialchars($settings['about_content']); ?></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">About Image</label>
                        <input type="file" name="about_image" accept="image/*" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        <p class="text-xs text-gray-500 mt-1">Accepted formats: JPG, PNG, GIF. Max size: 5MB</p>
                        <?php if ($settings['about_image']): ?>
                            <div class="mt-2">
                                <p class="text-sm text-gray-600">Current image:</p>
                                <img src="uploads/homepage/<?php echo htmlspecialchars($settings['about_image']); ?>" 
                                     class="w-32 h-20 object-cover rounded mt-1" alt="Current about image">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="bg-primary text-white px-6 py-2 rounded-md hover:bg-purple-700">
                        Update About Section
                    </button>
                </form>
            </div>

            <!-- Contact Section -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Contact Section</h2>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_contact">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contact Title</label>
                        <input type="text" name="contact_title" value="<?php echo htmlspecialchars($settings['contact_title']); ?>" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                            <textarea name="contact_address" rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"><?php echo htmlspecialchars($settings['contact_address']); ?></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <input type="text" name="contact_phone" value="<?php echo htmlspecialchars($settings['contact_phone']); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email']); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Hours</label>
                            <textarea name="contact_hours" rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"><?php echo htmlspecialchars($settings['contact_hours']); ?></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" class="bg-primary text-white px-6 py-2 rounded-md hover:bg-purple-700">
                        Update Contact Section
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
