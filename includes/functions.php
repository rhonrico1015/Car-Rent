<?php
// Advanced utility functions for CarRent Pro

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function get_user_role() {
    return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'guest';
}

function is_super_admin() {
    return get_user_role() === 'super_admin';
}

function is_admin() {
    $role = get_user_role();
    return $role === 'admin' || $role === 'super_admin';
}

function is_user() {
    return get_user_role() === 'user';
}

function has_permission($permission) {
    global $pdo;
    
    if (!is_logged_in()) {
        return false;
    }
    
    $role = get_user_role();
    
    if ($role === 'super_admin') {
        return true; // Super admin has all permissions
    }
    
    try {
        $stmt = $pdo->prepare("SELECT allowed FROM permissions WHERE role = ? AND permission = ?");
        $stmt->execute([$role, $permission]);
        $result = $stmt->fetch();
        
        return $result ? (bool)$result['allowed'] : false;
    } catch (PDOException $e) {
        return false;
    }
}

function redirect($location) {
    header("Location: " . $location);
    exit();
}

function format_currency($amount) {
    return '$' . number_format($amount, 2);
}

function format_date($date) {
    return date('M d, Y', strtotime($date));
}

function format_datetime($datetime) {
    return date('M d, Y H:i', strtotime($datetime));
}

function get_user_by_id($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function get_car_by_id($pdo, $car_id) {
    $stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
    $stmt->execute([$car_id]);
    return $stmt->fetch();
}

function get_all_cars($pdo, $status = 'available') {
    $stmt = $pdo->prepare("SELECT * FROM cars WHERE status = ? ORDER BY created_at DESC");
    $stmt->execute([$status]);
    return $stmt->fetchAll();
}

function get_all_users($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_all_rentals($pdo) {
    $stmt = $pdo->prepare("
        SELECT r.*, u.name as user_name, u.email as user_email, c.make, c.model, c.year 
        FROM rentals r 
        LEFT JOIN users u ON r.user_id = u.id 
        LEFT JOIN cars c ON r.car_id = c.id 
        ORDER BY r.created_at DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_user_rentals($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT r.*, c.make, c.model, c.year, c.image 
        FROM rentals r 
        LEFT JOIN cars c ON r.car_id = c.id 
        WHERE r.user_id = ? 
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function renderCarCard($car) {
    $features = !empty($car['features']) ? explode(',', $car['features']) : [];
    ?>
    <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition duration-300">
        <div class="h-48 bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center relative">
            <i class="fas fa-car text-white text-6xl"></i>
            <div class="absolute top-4 right-4">
                <span class="bg-white text-gray-800 px-2 py-1 rounded-full text-xs font-semibold">
                    <?php echo strtoupper($car['status']); ?>
                </span>
            </div>
        </div>
        <div class="p-6">
            <h3 class="text-xl font-semibold text-gray-900 mb-2">
                <?php echo htmlspecialchars($car['year'] . ' ' . $car['make'] . ' ' . $car['model']); ?>
            </h3>
            <p class="text-gray-600 mb-4">
                <?php echo htmlspecialchars($car['description']); ?>
            </p>
            
            <?php if (!empty($features)): ?>
                <div class="mb-4">
                    <div class="flex flex-wrap gap-2">
                        <?php foreach (array_slice($features, 0, 3) as $feature): ?>
                            <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs">
                                <?php echo htmlspecialchars(trim($feature)); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="flex justify-between items-center mb-4">
                <span class="text-2xl font-bold text-purple-600">
                    <?php echo format_currency($car['daily_rate']); ?>/day
                </span>
                <div class="flex items-center text-yellow-400">
                    <?php for ($i = 0; $i < 5; $i++): ?>
                        <i class="fas fa-star"></i>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-4 text-sm text-gray-600">
                <div class="flex items-center">
                    <i class="fas fa-gas-pump text-purple-600 mr-2"></i>
                    <?php echo ucfirst($car['fuel_type']); ?>
                </div>
                <div class="flex items-center">
                    <i class="fas fa-cogs text-purple-600 mr-2"></i>
                    <?php echo ucfirst($car['transmission']); ?>
                </div>
                <div class="flex items-center">
                    <i class="fas fa-users text-purple-600 mr-2"></i>
                    <?php echo $car['seats']; ?> seats
                </div>
                <div class="flex items-center">
                    <i class="fas fa-map-marker-alt text-purple-600 mr-2"></i>
                    <?php echo htmlspecialchars($car['location']); ?>
                </div>
            </div>
            
            <?php if ($car['status'] === 'available'): ?>
                <button onclick="bookCar(<?php echo $car['id']; ?>)" 
                        class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700 transition duration-300">
                    Book Now
                </button>
            <?php else: ?>
                <button disabled 
                        class="w-full bg-gray-300 text-gray-500 py-2 rounded-lg cursor-not-allowed">
                    <?php echo ucfirst($car['status']); ?>
                </button>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function get_rental_status_badge($status) {
    $badges = [
        'pending' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>',
        'confirmed' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Confirmed</span>',
        'active' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>',
        'completed' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Completed</span>',
        'cancelled' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Cancelled</span>'
    ];
    
    return isset($badges[$status]) ? $badges[$status] : '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Unknown</span>';
}

function get_user_role_badge($role) {
    $badges = [
        'super_admin' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Super Admin</span>',
        'admin' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">Admin</span>',
        'user' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">User</span>'
    ];
    
    return isset($badges[$role]) ? $badges[$role] : '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Unknown</span>';
}

function log_activity($pdo, $action, $description = '') {
    if (!is_logged_in()) {
        return;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (PDOException $e) {
        // Log error silently
    }
}

function get_setting($pdo, $key, $default = '') {
    try {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        return $result ? $result['value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

function update_setting($pdo, $key, $value) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, value, updated_at) 
            VALUES (?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()
        ");
        $stmt->execute([$key, $value]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function upload_file($file, $directory = 'uploads/') {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return false;
    }
    
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
    $max_size = MAX_FILE_SIZE;
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_types)) {
        return false;
    }
    
    if ($file['size'] > $max_size) {
        return false;
    }
    
    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $upload_path = $directory . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return [
            'filename' => $filename,
            'original_name' => $file['name'],
            'size' => $file['size'],
            'path' => $upload_path
        ];
    }
    
    return false;
}

function upload_profile_picture($file) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return false;
    }
    
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    $max_size = 2 * 1024 * 1024; // 2MB for profile pictures
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_types)) {
        return false;
    }
    
    if ($file['size'] > $max_size) {
        return false;
    }
    
    // Create profiles directory if it doesn't exist
    $directory = 'uploads/profiles/';
    if (!file_exists($directory)) {
        mkdir($directory, 0777, true);
    }
    
    $filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_extension;
    $upload_path = $directory . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return $filename;
    }
    
    return false;
}

function upload_car_image($file) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return false;
    }
    
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    $max_size = 5 * 1024 * 1024; // 5MB for car images
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_types)) {
        return false;
    }
    
    if ($file['size'] > $max_size) {
        return false;
    }
    
    // Create cars directory if it doesn't exist
    $directory = 'uploads/cars/';
    if (!file_exists($directory)) {
        mkdir($directory, 0777, true);
    }
    
    $filename = 'car_' . uniqid() . '_' . time() . '.' . $file_extension;
    $upload_path = $directory . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return [
            'filename' => $filename,
            'original_name' => $file['name'],
            'size' => $file['size'],
            'path' => $upload_path
        ];
    }
    
    return false;
}

function upload_homepage_image($file, $section = 'general') {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return false;
    }
    
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    $max_size = 5 * 1024 * 1024; // 5MB for homepage images
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_types)) {
        return false;
    }
    
    if ($file['size'] > $max_size) {
        return false;
    }
    
    // Create homepage directory if it doesn't exist
    $directory = 'uploads/homepage/';
    if (!file_exists($directory)) {
        mkdir($directory, 0777, true);
    }
    
    $filename = $section . '_' . uniqid() . '_' . time() . '.' . $file_extension;
    $upload_path = $directory . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return $filename;
    }
    
    return false;
}


function get_chat_messages($pdo, $limit = 50) {
    $limit = (int)$limit; // Cast to integer to ensure proper SQL syntax
    
    // Debug: Log the limit value and type
    error_log("get_chat_messages called with limit: " . $limit . " (type: " . gettype($limit) . ")");
    
    try {
        $stmt = $pdo->prepare("
            SELECT cm.*, u.name as user_name, u.role as user_role 
            FROM chat_messages cm 
            LEFT JOIN users u ON cm.user_id = u.id 
            ORDER BY cm.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return array_reverse($stmt->fetchAll()); // Reverse to show oldest first
    } catch (PDOException $e) {
        error_log("SQL Error in get_chat_messages: " . $e->getMessage());
        error_log("SQL Query: SELECT cm.*, u.name as user_name, u.role as user_role FROM chat_messages cm LEFT JOIN users u ON cm.user_id = u.id ORDER BY cm.created_at DESC LIMIT " . $limit);
        return [];
    }
}

function send_chat_message($pdo, $message, $message_type = 'text', $file_info = null) {
    if (!is_logged_in()) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO chat_messages (user_id, message, message_type, file_path, file_name, file_size, is_admin) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $is_admin = is_admin();
        
        return $stmt->execute([
            $_SESSION['user_id'],
            $message,
            $message_type,
            $file_info['path'] ?? null,
            $file_info['original_name'] ?? null,
            $file_info['size'] ?? null,
            $is_admin
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

function check_auto_response($pdo, $message) {
    $message_lower = strtolower($message);
    
    $stmt = $pdo->prepare("SELECT response_text FROM auto_responses WHERE trigger_keyword = ? AND is_active = 1");
    
    $keywords = ['hello', 'hi', 'booking', 'book', 'price', 'pricing', 'cost', 'contact', 'hours', 'insurance', 'payment'];
    
    foreach ($keywords as $keyword) {
        if (strpos($message_lower, $keyword) !== false) {
            $stmt->execute([$keyword]);
            $result = $stmt->fetch();
            if ($result) {
                return $result['response_text'];
            }
        }
    }
    
    return null;
}

function send_auto_response($pdo, $user_id, $response) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO chat_messages (user_id, message, message_type, is_admin, is_auto_response) 
            VALUES (?, ?, 'text', 1, 1)
        ");
        
        return $stmt->execute([$user_id, $response]);
    } catch (PDOException $e) {
        return false;
    }
}

function get_unread_message_count($pdo) {
    if (!is_logged_in()) {
        return 0;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM chat_messages 
            WHERE (user_id = ? AND is_admin = 1 AND read_status = 0) 
            OR (is_admin = 0 AND read_status = 0)
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch();
        
        return $result['count'];
    } catch (PDOException $e) {
        return 0;
    }
}
?>
