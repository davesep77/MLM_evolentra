<?php
// Function to create a notification
function createNotification($conn, $user_id, $title, $message, $type = 'info') {
    // Types: info, success, warning, error
    $title = $conn->real_escape_string($title);
    $message = $conn->real_escape_string($message);
    $type = $conn->real_escape_string($type);
    
    $sql = "INSERT INTO mlm_notifications (user_id, title, message, type) VALUES ($user_id, '$title', '$message', '$type')";
    return $conn->query($sql);
}

// Function to get unread count
function getUnreadNotificationCount($conn, $user_id) {
    if (!$user_id) return 0;
    $res = $conn->query("SELECT COUNT(*) FROM mlm_notifications WHERE user_id=$user_id AND is_read=0");
    return $res ? $res->fetch_row()[0] : 0;
}
?>
