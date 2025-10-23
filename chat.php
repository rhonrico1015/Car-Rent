<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$page_title = "Live Chat - CarRent Pro";

// Handle message sending
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $message = sanitize_input($_POST['message']);
    $message_type = 'text';
    $file_info = null;
    
    // Handle file upload
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file_info = upload_file($_FILES['file']);
        if ($file_info) {
            $message_type = 'file';
            $message = $message ?: "Shared a file: " . $file_info['original_name'];
        }
    }
    
    if (!empty($message) || $file_info) {
        if (send_chat_message($pdo, $message, $message_type, $file_info)) {
            // Check for auto response
            if (AUTO_RESPONSE_ENABLED && !is_admin()) {
                $auto_response = check_auto_response($pdo, $message);
                if ($auto_response) {
                    send_auto_response($pdo, $_SESSION['user_id'], $auto_response);
                }
            }
            
            // Log activity
            log_activity($pdo, 'chat_message', 'Sent chat message');
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: chat.php');
    exit();
}

// Mark messages as read
if (isset($_POST['mark_read'])) {
    try {
        $stmt = $pdo->prepare("UPDATE chat_messages SET read_status = 1 WHERE user_id = ? AND is_admin = 1");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (PDOException $e) {
        // Handle error silently
    }
}

// Get chat messages
$messages = get_chat_messages($pdo, 100); // Use hardcoded limit instead of constant

// Log chat access
log_activity($pdo, 'chat_access', 'Accessed chat system');
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
                    <a href="dashboard.php" class="text-2xl font-bold text-primary mr-8">CarRent Pro</a>
                    <div class="hidden md:flex md:space-x-8">
                        <a href="dashboard.php" class="text-gray-500 hover:text-primary px-3 py-2 text-sm font-medium">Dashboard</a>
                        <a href="cars.php" class="text-gray-500 hover:text-primary px-3 py-2 text-sm font-medium">Cars</a>
                        <a href="chat.php" class="text-gray-900 hover:text-primary px-3 py-2 text-sm font-medium">Chat</a>
                        <?php if (is_admin()): ?>
                            <a href="admin/" class="text-gray-500 hover:text-primary px-3 py-2 text-sm font-medium">Admin Panel</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <?php echo get_user_role_badge($_SESSION['user_role']); ?>
                        <span class="text-sm text-gray-600"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    </div>
                    <a href="logout.php" class="text-gray-500 hover:text-primary px-3 py-2 text-sm font-medium">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Chat Interface -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-lg">
            <!-- Chat Header -->
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-t-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-semibold">Live Chat Support</h1>
                        <p class="text-purple-100 text-sm">
                            <?php if (is_admin()): ?>
                                Customer Support - Respond to user messages
                            <?php else: ?>
                                24/7 Support - Get instant help with auto-responses
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
                        <span class="text-sm">Online</span>
                    </div>
                </div>
            </div>

            <!-- Chat Messages -->
            <div id="chat-messages" class="h-96 overflow-y-auto p-6 space-y-4">
                <?php foreach ($messages as $message): ?>
                    <div class="flex <?php echo $message['is_admin'] ? 'justify-start' : 'justify-end'; ?>">
                        <div class="max-w-xs lg:max-w-md">
                            <div class="flex items-start space-x-2">
                                <?php if ($message['is_admin']): ?>
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-purple-600 rounded-full flex items-center justify-center">
                                            <i class="fas fa-user-tie text-white text-sm"></i>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="<?php echo $message['is_admin'] ? 'bg-gray-200' : 'bg-primary text-white'; ?> rounded-lg px-4 py-2">
                                    <?php if ($message['is_auto_response']): ?>
                                        <div class="flex items-center text-xs <?php echo $message['is_admin'] ? 'text-purple-600' : 'text-purple-200'; ?> mb-1">
                                            <i class="fas fa-robot mr-1"></i>
                                            Auto Response
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($message['message_type'] === 'file'): ?>
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-file text-lg"></i>
                                            <div>
                                                <p class="font-medium"><?php echo htmlspecialchars($message['file_name']); ?></p>
                                                <p class="text-xs opacity-75"><?php echo number_format($message['file_size'] / 1024, 1); ?> KB</p>
                                            </div>
                                            <a href="<?php echo htmlspecialchars($message['file_path']); ?>" target="_blank" 
                                               class="ml-2 px-2 py-1 bg-white bg-opacity-20 rounded text-xs hover:bg-opacity-30 transition">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-sm"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!$message['is_admin']): ?>
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                                            <i class="fas fa-user text-white text-sm"></i>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-gray-500 mt-1 <?php echo $message['is_admin'] ? 'text-left' : 'text-right'; ?>">
                                <?php echo format_datetime($message['created_at']); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($messages)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-comments text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">No messages yet. Start the conversation!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Chat Input -->
            <div class="px-6 py-4 border-t border-gray-200">
                <form method="POST" enctype="multipart/form-data" class="flex space-x-4">
                    <input type="hidden" name="action" value="send_message">
                    
                    <!-- File Upload -->
                    <label class="flex-shrink-0 bg-gray-200 hover:bg-gray-300 text-gray-600 px-3 py-2 rounded-lg cursor-pointer transition duration-200">
                        <i class="fas fa-paperclip"></i>
                        <input type="file" name="file" class="hidden" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx" onchange="showFileName(this)">
                    </label>
                    
                    <!-- Message Input -->
                    <div class="flex-1">
                        <input type="text" name="message" placeholder="Type your message..." 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                               required>
                    </div>
                    
                    <!-- Send Button -->
                    <button type="submit" 
                            class="flex-shrink-0 bg-primary text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition duration-200">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
                
                <!-- File Name Display -->
                <div id="file-name" class="mt-2 text-sm text-gray-500 hidden"></div>
                
                <!-- Help Text -->
                <div class="mt-3 text-xs text-gray-400">
                    <p>
                        <i class="fas fa-info-circle mr-1"></i>
                        You can upload images and documents (JPG, PNG, PDF, DOC). 
                        <?php if (AUTO_RESPONSE_ENABLED && !is_admin()): ?>
                            Auto-responses are enabled for common questions.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-scroll to bottom of chat
        function scrollToBottom() {
            const chatMessages = document.getElementById('chat-messages');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        // Show selected file name
        function showFileName(input) {
            const fileNameDiv = document.getElementById('file-name');
            if (input.files && input.files[0]) {
                fileNameDiv.textContent = 'Selected: ' + input.files[0].name;
                fileNameDiv.classList.remove('hidden');
            } else {
                fileNameDiv.classList.add('hidden');
            }
        }
        
        // Auto-refresh messages every 5 seconds
        setInterval(function() {
            fetch('chat_ajax.php?action=get_messages')
                .then(response => response.json())
                .then(data => {
                    if (data.new_messages) {
                        location.reload(); // Simple refresh for new messages
                    }
                })
                .catch(error => console.log('Chat refresh error:', error));
        }, 5000);
        
        // Scroll to bottom on page load
        document.addEventListener('DOMContentLoaded', scrollToBottom);
    </script>
</body>
</html>
