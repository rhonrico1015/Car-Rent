<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$page_title = "My Profile - CarRent Pro";
$current_page = 'profile';

// Get current user data
$user = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $error_message = "Error fetching user data: " . $e->getMessage();
}

// Handle profile update
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_profile':
            $name = sanitize_input($_POST['name']);
            $email = sanitize_input($_POST['email']);
            $phone = sanitize_input($_POST['phone']);
            $address = sanitize_input($_POST['address']);
            
            // Handle profile picture upload
            $profile_picture = $user['profile_picture'] ?? null;
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $upload_result = upload_profile_picture($_FILES['profile_picture']);
                if ($upload_result) {
                    // Delete old profile picture if exists
                    if ($profile_picture && file_exists('uploads/profiles/' . $profile_picture)) {
                        unlink('uploads/profiles/' . $profile_picture);
                    }
                    $profile_picture = $upload_result;
                }
            }
            
            try {
                $stmt = $pdo->prepare("
                    UPDATE users SET 
                        name = ?, 
                        email = ?, 
                        phone = ?, 
                        address = ?, 
                        profile_picture = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$name, $email, $phone, $address, $profile_picture, $_SESSION['user_id']]);
                
                // Update session data
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                
                $success_message = "Profile updated successfully!";
                log_activity($pdo, 'profile_updated', 'Updated profile information');
                
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
            } catch (PDOException $e) {
                $error_message = "Error updating profile: " . $e->getMessage();
            }
            break;
            
        case 'change_password':
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error_message = "Please fill in all password fields.";
            } elseif ($new_password !== $confirm_password) {
                $error_message = "New passwords do not match.";
            } elseif (!password_verify($current_password, $user['password'])) {
                $error_message = "Current password is incorrect.";
            } else {
                try {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                    
                    $success_message = "Password changed successfully!";
                    log_activity($pdo, 'password_changed', 'Changed account password');
                } catch (PDOException $e) {
                    $error_message = "Error changing password: " . $e->getMessage();
                }
            }
            break;
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
                    <a href="profile.php" class="text-primary font-semibold">Profile</a>
                    <a href="logout.php" class="text-gray-700 hover:text-primary">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto py-6 px-4">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">My Profile</h1>

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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Profile Picture & Basic Info -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-center">
                        <div class="relative inline-block">
                            <?php if ($user['profile_picture']): ?>
                                <img src="uploads/profiles/<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                     alt="Profile Picture" class="w-32 h-32 rounded-full object-cover mx-auto">
                            <?php else: ?>
                                <div class="w-32 h-32 rounded-full bg-primary flex items-center justify-center mx-auto">
                                    <span class="text-white text-4xl font-bold">
                                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <h2 class="text-xl font-semibold text-gray-900 mt-4"><?php echo htmlspecialchars($user['name']); ?></h2>
                        <p class="text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                        
                        <div class="mt-4">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold 
                                <?php echo $user['role'] === 'super_admin' ? 'bg-red-100 text-red-800' : 
                                          ($user['role'] === 'admin' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                            </span>
                        </div>
                        
                        <div class="mt-6 text-sm text-gray-600">
                            <p><i class="fas fa-calendar mr-2"></i>Member since <?php echo format_date($user['created_at']); ?></p>
                            <?php if ($user['last_login']): ?>
                                <p><i class="fas fa-sign-in-alt mr-2"></i>Last login: <?php echo format_datetime($user['last_login']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Forms -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Update Profile -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Update Profile</h3>
                    
                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                <input type="text" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Profile Picture</label>
                            <input type="file" name="profile_picture" accept="image/*" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                            <p class="text-xs text-gray-500 mt-1">Accepted formats: JPG, PNG, GIF. Max size: 2MB</p>
                        </div>
                        
                        <button type="submit" class="bg-primary text-white px-6 py-2 rounded-md hover:bg-purple-700">
                            <i class="fas fa-save mr-2"></i>Update Profile
                        </button>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Change Password</h3>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                            <input type="password" name="current_password" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                <input type="password" name="new_password" required 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                                <input type="password" name="confirm_password" required 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                        </div>
                        
                        <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-md hover:bg-red-700">
                            <i class="fas fa-key mr-2"></i>Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Image preview
        document.querySelector('input[name="profile_picture"]').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.querySelector('img[alt="Profile Picture"]');
                    if (img) {
                        img.src = e.target.result;
                    } else {
                        // Replace the default avatar with image
                        const avatar = document.querySelector('.w-32.h-32.rounded-full.bg-primary');
                        if (avatar) {
                            avatar.outerHTML = `<img src="${e.target.result}" alt="Profile Picture" class="w-32 h-32 rounded-full object-cover mx-auto">`;
                        }
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
