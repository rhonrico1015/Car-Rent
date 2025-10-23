<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    exit('Unauthorized');
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_messages':
        $messages = get_chat_messages($pdo, 100); // Use hardcoded limit instead of constant
        $last_message_time = isset($_GET['last_time']) ? $_GET['last_time'] : null;
        
        if ($last_message_time) {
            // Check if there are new messages since last check
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM chat_messages WHERE created_at > ?");
            $stmt->execute([$last_message_time]);
            $new_count = $stmt->fetch()['count'];
            
            echo json_encode(['new_messages' => $new_count > 0]);
        } else {
            echo json_encode(['messages' => $messages]);
        }
        break;
        
    case 'mark_read':
        if (isset($_POST['message_id'])) {
            $message_id = (int)$_POST['message_id'];
            try {
                $stmt = $pdo->prepare("UPDATE chat_messages SET read_status = 1 WHERE id = ? AND user_id = ?");
                $stmt->execute([$message_id, $_SESSION['user_id']]);
                echo json_encode(['success' => true]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
        break;
        
    case 'send_message':
        if ($_POST && isset($_POST['message'])) {
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
                    
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to send message']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Message is required']);
            }
        }
        break;
        
    case 'get_unread_count':
        $count = get_unread_message_count($pdo);
        echo json_encode(['count' => $count]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>
