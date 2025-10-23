<?php
// Advanced CarRent Pro Setup Script
// This script will create the database and tables with role-based access control

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'carrent_db';

echo "<!DOCTYPE html>
<html>
<head>
    <title>CarRent Pro Advanced Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #27ae60; }
        .error { color: #e74c3c; }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #3498db; background: #ecf0f1; }
        .btn { background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #229954; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🚗 CarRent Pro Advanced Setup</h1>
        <p>Setting up your advanced car rental system with role-based access control...</p>";

try {
    // Step 1: Connect to MySQL server
    echo "<div class='step'>";
    echo "<h3>Step 1: Connecting to MySQL Server</h3>";
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p class='success'>✓ Connected to MySQL server successfully</p>";
    echo "</div>";
    
    // Step 2: Create database
    echo "<div class='step'>";
    echo "<h3>Step 2: Creating Database</h3>";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    echo "<p class='success'>✓ Database '$dbname' created successfully</p>";
    echo "</div>";
    
    // Step 3: Connect to the new database
    echo "<div class='step'>";
    echo "<h3>Step 3: Connecting to Database</h3>";
    $pdo->exec("USE `$dbname`");
    echo "<p class='success'>✓ Connected to database '$dbname'</p>";
    echo "</div>";
    
    // Step 4: Create tables
    echo "<div class='step'>";
    echo "<h3>Step 4: Creating Advanced Tables</h3>";
    
    // Users table with role-based access
    $pdo->exec("DROP TABLE IF EXISTS users"); // Drop existing table to ensure clean setup
    $pdo->exec("CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        role ENUM('super_admin', 'admin', 'user') DEFAULT 'user',
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        permissions JSON,
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<p class='success'>✓ Users table created with role-based access</p>";
    
    // Cars table
    $pdo->exec("DROP TABLE IF EXISTS cars"); // Drop existing table to ensure clean setup
    $pdo->exec("CREATE TABLE cars (
        id INT AUTO_INCREMENT PRIMARY KEY,
        make VARCHAR(50) NOT NULL,
        model VARCHAR(50) NOT NULL,
        year INT NOT NULL,
        color VARCHAR(30),
        license_plate VARCHAR(20) UNIQUE NOT NULL,
        daily_rate DECIMAL(10,2) NOT NULL,
        image VARCHAR(255),
        images JSON,
        description TEXT,
        features TEXT,
        fuel_type ENUM('gasoline', 'diesel', 'electric', 'hybrid') DEFAULT 'gasoline',
        transmission ENUM('manual', 'automatic') DEFAULT 'automatic',
        seats INT DEFAULT 5,
        status ENUM('available', 'rented', 'maintenance', 'inactive') DEFAULT 'available',
        location VARCHAR(255),
        mileage INT DEFAULT 0,
        insurance_expiry DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<p class='success'>✓ Cars table created with advanced features</p>";
    
    // Rentals table
    $pdo->exec("DROP TABLE IF EXISTS rentals"); // Drop existing table to ensure clean setup
    $pdo->exec("CREATE TABLE rentals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        car_id INT NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        daily_rate DECIMAL(10,2) NOT NULL,
        total_cost DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'confirmed', 'active', 'completed', 'cancelled') DEFAULT 'pending',
        pickup_location VARCHAR(255),
        return_location VARCHAR(255),
        pickup_time TIME,
        return_time TIME,
        notes TEXT,
        admin_notes TEXT,
        documents JSON,
        payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
        payment_method VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE
    )");
    echo "<p class='success'>✓ Rentals table created with payment tracking</p>";
    
    // Chat messages table
    $pdo->exec("DROP TABLE IF EXISTS chat_messages"); // Drop existing table to ensure clean setup
    $pdo->exec("CREATE TABLE chat_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        admin_id INT,
        message TEXT NOT NULL,
        message_type ENUM('text', 'image', 'file') DEFAULT 'text',
        file_path VARCHAR(255),
        file_name VARCHAR(255),
        file_size INT,
        is_admin BOOLEAN DEFAULT FALSE,
        is_auto_response BOOLEAN DEFAULT FALSE,
        read_status BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
    )");
    echo "<p class='success'>✓ Chat messages table created</p>";
    
    // Auto responses table
    $pdo->exec("DROP TABLE IF EXISTS auto_responses"); // Drop existing table to ensure clean setup
    $pdo->exec("CREATE TABLE auto_responses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        trigger_keyword VARCHAR(255) NOT NULL,
        response_text TEXT NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p class='success'>✓ Auto responses table created</p>";
    
    // Settings table
    $pdo->exec("DROP TABLE IF EXISTS settings"); // Drop existing table to ensure clean setup
    $pdo->exec("CREATE TABLE settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<p class='success'>✓ Settings table created</p>";
    
    // Permissions table
    $pdo->exec("DROP TABLE IF EXISTS permissions"); // Drop existing table to ensure clean setup
    $pdo->exec("CREATE TABLE permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role VARCHAR(50) NOT NULL,
        permission VARCHAR(100) NOT NULL,
        allowed BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_role_permission (role, permission)
    )");
    echo "<p class='success'>✓ Permissions table created</p>";
    
    // Activity logs table
    $pdo->exec("DROP TABLE IF EXISTS activity_logs"); // Drop existing table to ensure clean setup
    $pdo->exec("CREATE TABLE activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(100) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");
    echo "<p class='success'>✓ Activity logs table created</p>";
    
    echo "</div>";
    
    // Step 5: Insert sample data
    echo "<div class='step'>";
    echo "<h3>Step 5: Adding Sample Data</h3>";
    
    // Insert super admin user
    $super_admin_password = password_hash('superadmin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (name, email, password, role, permissions) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['Super Admin', 'superadmin@carrentpro.com', $super_admin_password, 'super_admin', json_encode(['all' => true])]);
    echo "<p class='success'>✓ Super Admin created (superadmin@carrentpro.com / superadmin123)</p>";
    
    // Insert admin user
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt->execute(['Admin User', 'admin@carrentpro.com', $admin_password, 'admin', json_encode(['cars' => true, 'users' => true, 'rentals' => true])]);
    echo "<p class='success'>✓ Admin created (admin@carrentpro.com / admin123)</p>";
    
    // Insert demo customer
    $customer_password = password_hash('user123', PASSWORD_DEFAULT);
    $stmt->execute(['Demo Customer', 'customer@carrentpro.com', $customer_password, 'user', json_encode([])]);
    echo "<p class='success'>✓ Demo customer created (customer@carrentpro.com / user123)</p>";
    
    // Insert sample cars
    $cars = [
        ['BMW', 'X5', 2023, 'Black', 'BMW-X5-001', 150.00, 'Luxury SUV perfect for family trips', 'Leather seats, GPS, Bluetooth, Sunroof', 'gasoline', 'automatic', 7, 'Downtown Location'],
        ['Audi', 'A4', 2023, 'Silver', 'AUD-A4-001', 120.00, 'Elegant sedan with premium features', 'Premium sound system, Heated seats, Navigation', 'gasoline', 'automatic', 5, 'Airport Location'],
        ['Toyota', 'Camry', 2023, 'White', 'TOY-CAM-001', 80.00, 'Reliable and fuel-efficient', 'Air conditioning, USB ports, Backup camera', 'gasoline', 'automatic', 5, 'City Center'],
        ['Mercedes', 'C-Class', 2023, 'Blue', 'MER-C01-001', 130.00, 'Luxury sedan with advanced technology', 'Premium interior, Advanced safety features', 'gasoline', 'automatic', 5, 'Business District'],
        ['Tesla', 'Model 3', 2023, 'White', 'TES-M3-001', 200.00, 'Electric vehicle with cutting-edge technology', 'Autopilot, Supercharging, Premium interior', 'electric', 'automatic', 5, 'Green Zone'],
        ['Honda', 'Accord', 2023, 'Red', 'HON-ACC-001', 90.00, 'Sporty sedan with excellent fuel economy', 'Sport mode, LED headlights, Smart entry', 'gasoline', 'automatic', 5, 'Suburban Location']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO cars (make, model, year, color, license_plate, daily_rate, description, features, fuel_type, transmission, seats, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($cars as $car) {
        $stmt->execute($car);
    }
    echo "<p class='success'>✓ Sample cars added to database</p>";
    
    // Insert default permissions
    $permissions = [
        ['super_admin', 'manage_users', true],
        ['super_admin', 'manage_admins', true],
        ['super_admin', 'manage_cars', true],
        ['super_admin', 'manage_rentals', true],
        ['super_admin', 'view_analytics', true],
        ['super_admin', 'manage_settings', true],
        ['admin', 'manage_cars', true],
        ['admin', 'manage_rentals', true],
        ['admin', 'view_users', true],
        ['admin', 'chat_support', true],
        ['user', 'book_cars', true],
        ['user', 'view_profile', true],
        ['user', 'chat_support', true]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO permissions (role, permission, allowed) VALUES (?, ?, ?)");
    foreach ($permissions as $permission) {
        $stmt->execute($permission);
    }
    echo "<p class='success'>✓ Default permissions set</p>";
    
    // Insert auto responses
    $auto_responses = [
        ['hello', 'Hello! Welcome to CarRent Pro. How can I help you today?'],
        ['booking', 'To book a car, please browse our available vehicles and select your preferred dates. Need help with the booking process?'],
        ['pricing', 'Our rates vary by car type. Economy cars start at $80/day, while luxury vehicles can go up to $200/day.'],
        ['contact', 'You can reach us at +1 (555) 123-4567 or email us at info@carrentpro.com. We\'re available 24/7 for support!'],
        ['hours', 'Our rental locations are open 24/7. You can pick up or return vehicles at any time.'],
        ['insurance', 'All our vehicles come with comprehensive insurance coverage. Additional coverage options are available.'],
        ['payment', 'We accept all major credit cards, PayPal, and bank transfers. Payment is required at the time of booking.']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO auto_responses (trigger_keyword, response_text) VALUES (?, ?)");
    foreach ($auto_responses as $response) {
        $stmt->execute($response);
    }
    echo "<p class='success'>✓ Auto responses configured</p>";
    
    // Insert default settings
    $settings = [
        ['site_name', 'CarRent Pro', 'text', 'Website name'],
        ['site_email', 'info@carrentpro.com', 'text', 'Contact email'],
        ['site_phone', '+1 (555) 123-4567', 'text', 'Contact phone'],
        ['site_address', '123 Car Rental Street, Downtown City, 12345', 'text', 'Business address'],
        ['max_rental_days', '30', 'number', 'Maximum rental period in days'],
        ['advance_booking_days', '90', 'number', 'Maximum advance booking in days'],
        ['auto_response_enabled', 'true', 'boolean', 'Enable automatic responses in chat'],
        ['maintenance_mode', 'false', 'boolean', 'Enable maintenance mode']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)");
    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }
    echo "<p class='success'>✓ Default settings added</p>";
    
    echo "</div>";
    
    // Success message
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 20px; border-radius: 5px; margin: 20px 0;'>
            <h2>🎉 Advanced Setup Complete!</h2>
            <p><strong>Your CarRent Pro system with role-based access is now ready!</strong></p>
            <h3>Login Credentials:</h3>
            <ul>
                <li><strong>Super Admin:</strong> superadmin@carrentpro.com / superadmin123</li>
                <li><strong>Admin:</strong> admin@carrentpro.com / admin123</li>
                <li><strong>Customer:</strong> customer@carrentpro.com / user123</li>
            </ul>
            <h3>Features Available:</h3>
            <ul>
                <li>✅ Role-based access control (Super Admin, Admin, User)</li>
                <li>✅ Real-time chat with auto-responses</li>
                <li>✅ File upload support in chat</li>
                <li>✅ Advanced car booking system</li>
                <li>✅ Permission management</li>
                <li>✅ Activity logging</li>
            </ul>
            <h3>Next Steps:</h3>
            <ul>
                <li><a href='index.php' class='btn btn-success'>Visit Homepage</a></li>
                <li><a href='login.php' class='btn'>Login to Dashboard</a></li>
                <li><a href='chat.php' class='btn'>Test Chat System</a></li>
            </ul>
          </div>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 20px; border-radius: 5px; margin: 20px 0;'>
            <h2>❌ Setup Failed</h2>
            <p><strong>Error:</strong> " . $e->getMessage() . "</p>
            <h3>Troubleshooting:</h3>
            <ul>
                <li>Make sure MySQL server is running in XAMPP</li>
                <li>Check if XAMPP/WAMP is started</li>
                <li>Verify database credentials</li>
                <li>Ensure you have permission to create databases</li>
            </ul>
          </div>";
}

echo "    </div>
</body>
</html>";
?>
