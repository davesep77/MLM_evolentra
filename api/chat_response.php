<?php
require '../config_db.php';
header('Content-Type: application/json');

// Security Check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['reply' => "Please log in to chat with me."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$message = strtolower(trim($input['message'] ?? ''));

if (empty($message)) {
    echo json_encode(['reply' => "How can I help you?"]);
    exit;
}

$reply = "";

// 1. Ticket Creation Shortcut
if (strpos($message, 'ticket:') === 0) {
    $ticket_msg = trim(substr($message, 7));
    if (strlen($ticket_msg) < 5) {
        $reply = "Your ticket message is too short. Please describe your issue.";
    } else {
        $subject = "Chatbot Escalation";
        $sql = "INSERT INTO mlm_support_tickets (user_id, subject, priority, message, status) VALUES ($user_id, '$subject', 'medium', '$ticket_msg', 'open')";
        if ($conn->query($sql)) {
            $reply = "✅ System: Support ticket created successfully! An admin will review it shortly.";
        } else {
            $reply = "❌ Error creating ticket. Please try the Support page.";
        }
    }
} 
// 2. Keyword Matching
else if (strpos($message, 'withdraw') !== false || strpos($message, 'cashout') !== false) {
    $reply = "To withdraw funds, go to the Withdrawals section. Ensure your wallet address is set in your Profile. Admin approval is required.";
} 
else if (strpos($message, 'invest') !== false || strpos($message, 'deposit') !== false) {
    $reply = "You can invest in our plans (ROOT, RISE, TERRA) via the Dashboard. We accept BTC, ETH, and USDT.";
} 
else if (strpos($message, 'transfer') !== false) {
    $reply = "User-to-User transfers are available in the Transfer section. All transfers require admin approval for security.";
} 
else if (strpos($message, 'hello') !== false || strpos($message, 'hi') !== false) {
    $reply = "Hello! I am the Evolentra Support Bot. Ask me about withdrawals, investments, or type 'ticket: your issue' to contact a human.";
} 
else if (strpos($message, 'human') !== false || strpos($message, 'support') !== false || strpos($message, 'help') !== false) {
    $reply = "To contact a human agent, you can type 'ticket: [your message]' right here to open a ticket immediately, or visit the Support page.";
} 
// 3. Default Fallback
else {
    $reply = "I'm not sure about that. Try asking about 'investments', 'withdrawals', or type 'ticket: [your issue]' to get help from an admin.";
}

echo json_encode(['reply' => $reply]);
?>
