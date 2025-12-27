<?php
require 'config_db.php';

// Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Handle New Ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_ticket'])) {
    $subject = $conn->real_escape_string($_POST['subject']);
    $priority = $conn->real_escape_string($_POST['priority']);
    $msg_content = $conn->real_escape_string($_POST['message']);

    if (empty($subject) || empty($msg_content)) {
        $error = "Please fill in all fields.";
    } else {
        $sql = "INSERT INTO mlm_support_tickets (user_id, subject, priority, message, status) VALUES ($user_id, '$subject', '$priority', '$msg_content', 'open')";
        if ($conn->query($sql)) {
            $message = "Ticket created successfully! We will reply shortly.";
        } else {
            $error = "Error creating ticket: " . $conn->error;
        }
    }
}

// Fetch Tickets
$tickets = $conn->query("SELECT * FROM mlm_support_tickets WHERE user_id=$user_id ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Support - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #f8fafc; }
        .glass-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; padding: 1.5rem; }
    </style>
</head>
<body class="flex">

    <?php include 'sidebar_nav.php'; ?>

    <div class="flex-1 ml-[280px] p-8">
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-white">Support Center</h1>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 h-[calc(100vh-140px)]">
            
            <!-- Chat / Ticket Interface (Span 2 Cols) -->
            <div class="lg:col-span-2 flex flex-col glass-card relative overflow-hidden">
                <!-- Header / Mode Switcher -->
                <div class="flex justify-between items-center mb-4 border-b border-slate-700/50 pb-4">
                    <div class="flex items-center gap-3">
                         <div class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white p-2 rounded-lg shadow-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white leading-none">Evolentra AI Support</h3>
                            <span class="text-xs text-indigo-400 font-mono">Model: EVO-1.5-Turbo</span>
                        </div>
                    </div>
                    
                    <div class="flex bg-slate-900/50 rounded-lg p-1 border border-slate-700">
                        <button onclick="setMode('chat')" id="btn-chat" class="px-3 py-1 rounded text-sm font-medium transition-all bg-indigo-600 text-white shadow">
                            AI Chat
                        </button>
                        <button onclick="setMode('ticket')" id="btn-ticket" class="px-3 py-1 rounded text-sm font-medium text-slate-400 hover:text-white transition-all">
                            Formal Ticket
                        </button>
                    </div>
                </div>

                <!-- Chat Area -->
                <div id="chat-container" class="flex-1 overflow-y-auto space-y-4 p-2 mb-4 scrollbar-hide">
                    <!-- AI Welcome Message -->
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-xs font-bold ring-2 ring-indigo-400/30">AI</div>
                        <div class="bg-indigo-900/20 border border-indigo-500/20 text-slate-200 p-3 rounded-2xl rounded-tl-none max-w-[80%] text-sm leading-relaxed">
                            Hello! I am your <strong>Evolentra Personal Assistant</strong>. 
                            <br><br>
                            I can help you with:
                            <ul class="list-disc ml-4 mt-1 space-y-1 text-xs text-slate-400">
                                <li>Withdrawal rules & status</li>
                                <li>Investment plans (ROOT, RISE, TERRA)</li>
                                <li>Commission & Binary logic</li>
                                <li>Account security</li>
                            </ul>
                            <br>
                            How can I assist you today?
                        </div>
                    </div>
                </div>

                <!-- Input Area (Chat Mode) -->
                <div id="chat-controls" class="relative">
                    <div class="absolute -top-10 left-0 w-full h-10 bg-gradient-to-t from-slate-900/80 to-transparent pointer-events-none"></div>
                    <div class="flex gap-2">
                        <input type="text" id="chatInput" placeholder="Ask anything..." class="flex-1 bg-slate-900 border border-slate-700 rounded-xl p-4 text-white focus:outline-none focus:border-indigo-500 transition-all font-medium" onkeypress="handleEnter(event)">
                        <button onclick="sendMessage()" class="bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl px-6 font-bold transition-all flex items-center gap-2">
                            <span>Send</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </button>
                    </div>
                    <div class="text-xs text-center text-slate-500 mt-2">
                        AI can make mistakes. Please verify important financial details.
                    </div>
                </div>

                <!-- Ticket Form (Hidden by default, toggled via JS) -->
                <div id="ticket-controls" class="hidden h-full flex flex-col">
                    <form method="POST" class="h-full flex flex-col">
                        <input type="hidden" name="create_ticket" value="1">
                        
                        <div class="space-y-4 flex-1 overflow-y-auto pr-2">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 mb-1 uppercase tracking-wider">Subject</label>
                                <input type="text" name="subject" id="ticketSubject" required class="w-full bg-slate-900 border border-slate-700 rounded p-3 text-white focus:outline-none focus:border-indigo-500">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-1 uppercase tracking-wider">Priority</label>
                                    <select name="priority" class="w-full bg-slate-900 border border-slate-700 rounded p-3 text-white focus:outline-none focus:border-indigo-500">
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 mb-1 uppercase tracking-wider">Department</label>
                                    <select class="w-full bg-slate-900 border border-slate-700 rounded p-3 text-white focus:outline-none focus:border-indigo-500">
                                        <option>General Support</option>
                                        <option>Technical Issue</option>
                                        <option>Financial/Billing</option>
                                    </select>
                                </div>
                            </div>

                            <div class="flex-1">
                                <label class="block text-xs font-bold text-slate-400 mb-1 uppercase tracking-wider">Detailed Message</label>
                                <textarea name="message" id="ticketMessage" rows="6" required class="w-full bg-slate-900 border border-slate-700 rounded p-3 text-white focus:outline-none focus:border-indigo-500 h-full min-h-[150px]"></textarea>
                            </div>
                        </div>

                        <button type="submit" class="w-full mt-4 bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 rounded-xl transition-all shadow-lg hover:shadow-emerald-500/30 flex justify-center items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Submit Official Ticket
                        </button>
                    </form>
                </div>
            </div>

            <!-- History Sidebar (Span 1 Col) -->
            <div class="flex flex-col space-y-4 h-full overflow-hidden">
                <!-- Telegram Bot Support Card -->
                <div class="glass-card">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-400 to-blue-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295-.002 0-.003 0-.005 0l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.64-.203-.658-.64.135-.954l11.566-4.458c.538-.196 1.006.128.832.941z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white leading-none">Telegram Support</h3>
                            <span class="text-xs text-blue-400">24/7 Instant Help</span>
                        </div>
                    </div>
                    <p class="text-sm text-slate-300 mb-3">
                        Get instant support via our Telegram bot! Check balances, view stats, and get help anytime.
                    </p>
                    <a href="https://t.me/YOUR_BOT_USERNAME" target="_blank" class="block w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-bold py-3 rounded-lg transition-all shadow-lg hover:shadow-blue-500/30 text-center flex items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295-.002 0-.003 0-.005 0l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.64-.203-.658-.64.135-.954l11.566-4.458c.538-.196 1.006.128.832.941z"/>
                        </svg>
                        Open Telegram Bot
                    </a>
                    <div class="mt-3 p-2 bg-slate-900/50 rounded border border-slate-700/50">
                        <p class="text-xs text-slate-400 text-center">
                            💡 <strong>Quick Commands:</strong> /balance, /stats, /referral, /help
                        </p>
                    </div>
                </div>

                <div class="glass-card flex-1 flex flex-col overflow-hidden">
                    <h3 class="text-lg font-bold mb-4 text-white flex items-center gap-2 sticky top-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Past Tickets
                    </h3>
                    
                    <div class="overflow-y-auto space-y-3 pr-2 scrollbar-thin">
                        <?php if ($tickets->num_rows > 0): ?>
                            <?php while ($t = $tickets->fetch_assoc()): 
                                $statusColor = 'text-slate-400 border-slate-700 bg-slate-800/50';
                                if ($t['status'] == 'open') $statusColor = 'text-emerald-400 border-emerald-500/30 bg-emerald-500/10';
                                if ($t['status'] == 'closed') $statusColor = 'text-slate-500 border-slate-700 bg-slate-900/50';
                                if ($t['status'] == 'in_progress') $statusColor = 'text-indigo-400 border-indigo-500/30 bg-indigo-500/10';
                            ?>
                            <div class="p-3 rounded-lg border border-slate-700/50 bg-slate-800/30 hover:bg-slate-800 transition cursor-pointer">
                                <div class="flex justify-between items-start mb-1">
                                    <span class="text-xs font-mono text-slate-500">#<?= $t['id'] ?></span>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase border <?= $statusColor ?>">
                                        <?= str_replace('_', ' ', $t['status']) ?>
                                    </span>
                                </div>
                                <h4 class="font-bold text-slate-200 text-sm mb-1 truncate"><?= htmlspecialchars($t['subject']) ?></h4>
                                <p class="text-xs text-slate-400 line-clamp-2"><?= htmlspecialchars($t['message']) ?></p>
                                
                                <?php if (!empty($t['admin_response'])): ?>
                                    <div class="mt-2 pt-2 border-t border-slate-700/50 flex items-center gap-1 text-xs text-indigo-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                        </svg>
                                        Admin Responded
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-8 text-slate-500 text-sm">No ticket history.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Advanced AI Context
        const context = {
            plans: "ROOT (Beginner), RISE (Standard), TERRA (Premium)",
            withdrawal_day: "Saturday",
            min_withdraw: "$15",
            fee: "7%",
            referral_rate: "8-9%",
            binary_rate: "10%"
        };

        const knowledgeBase = [
             {
                keywords: ['hello', 'hi', 'hey', 'start'],
                response: "Hello! I am ready to assist you. Ask me about investments, withdrawals, or your account."
            },
            {
                keywords: ['withdraw', 'cashout', 'payout', 'money', 'claim'],
                response: `Regarding withdrawals:
                <br>• Processed every <strong>${context.withdrawal_day}</strong>.
                <br>• Minimum amount: <strong>${context.min_withdraw}</strong>.
                <br>• Fee: <strong>${context.fee}</strong>.
                <br>Make sure your wallet address is updated in your profile before requesting.`
            },
            {
                keywords: ['deposit', 'invest', 'plan', 'package', 'root', 'rise', 'terra'],
                response: `We offer three investment tiers: <strong>${context.plans}</strong>.
                <br>To invest:
                <ol class='list-decimal ml-4 mt-1 text-slate-400'>
                    <li>Go to the Invest page.</li>
                    <li>Select a plan.</li>
                    <li>Send the exact USDT amount.</li>
                    <li>Wait for blockchain confirmation (usually 5-15 mins).</li>
                </ol>`
            },
            {
                keywords: ['referral', 'invite', 'commission', 'bonus'],
                response: `You earn <strong>${context.referral_rate}</strong> direct referral income for every user who joins and invests using your link. Commissions are credited instantly.`
            },
            {
                keywords: ['binary', 'match', 'pair', 'leg', 'left', 'right'],
                response: `Binary Income is calculated daily on the weaker leg's volume. You earn <strong>${context.binary_rate}</strong> matching bonus. Requires 1 active referral on both sides.`
            },
            {
                keywords: ['forgot', 'password', 'reset', 'login'],
                response: "For security reasons, password resets must be requested via the 'Forgot Password' link on the login page or by contacting admin directly at support@evolentra.com."
            }
        ];

        // Chat Logic
        function setMode(mode) {
            const chatContainer = document.getElementById('chat-container');
            const chatControls = document.getElementById('chat-controls');
            const ticketControls = document.getElementById('ticket-controls');
            const btnChat = document.getElementById('btn-chat');
            const btnTicket = document.getElementById('btn-ticket');

            if (mode === 'chat') {
                chatContainer.classList.remove('hidden');
                chatControls.classList.remove('hidden');
                ticketControls.classList.add('hidden');
                
                btnChat.classList.add('bg-indigo-600', 'text-white', 'shadow');
                btnChat.classList.remove('text-slate-400');
                btnTicket.classList.remove('bg-indigo-600', 'text-white', 'shadow');
                btnTicket.classList.add('text-slate-400');
            } else {
                chatContainer.classList.add('hidden');
                chatControls.classList.add('hidden');
                ticketControls.classList.remove('hidden');

                btnTicket.classList.add('bg-indigo-600', 'text-white', 'shadow');
                btnTicket.classList.remove('text-slate-400');
                btnChat.classList.remove('bg-indigo-600', 'text-white', 'shadow');
                btnChat.classList.add('text-slate-400');
            }
        }

        function handleEnter(e) {
            if (e.key === 'Enter') sendMessage();
        }

        function sendMessage() {
            const input = document.getElementById('chatInput');
            const msg = input.value.trim();
            if (!msg) return;

            // 1. User Message
            appendMessage('user', msg);
            input.value = '';

            // 2. AI Thinking (with typewriter stub)
            const thinkingId = appendThinking();

            // 3. Process Response
            setTimeout(() => {
                removeMessage(thinkingId);
                const reply = findBestResponse(msg);
                appendMessage('ai', reply);
            }, 800 + Math.random() * 1000); // Random delay 0.8s - 1.8s
        }

        function findBestResponse(msg) {
            const lowerMsg = msg.toLowerCase();
            for (let item of knowledgeBase) {
                if (item.keywords.some(k => lowerMsg.includes(k))) {
                    return item.response;
                }
            }
            return "I am not sure about that specific detail. <br>Would you like to <strong>switch to Ticket Mode</strong> and ask a human agent?";
        }

        function appendMessage(role, text) {
            const container = document.getElementById('chat-container');
            const div = document.createElement('div');
            div.className = "flex items-start gap-3 animate-fade-in-up";
            
            if (role === 'user') {
                div.classList.add('justify-end'); // Right align
                div.innerHTML = `
                    <div class="bg-indigo-600 text-white p-3 rounded-2xl rounded-tr-none max-w-[80%] text-sm shadow-lg shadow-indigo-500/10">
                        ${text.replace(/\n/g, '<br>')}
                    </div>
                    <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-xs font-bold border border-slate-600">You</div>
                `;
            } else {
                // AI
                div.innerHTML = `
                    <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-xs font-bold ring-2 ring-indigo-400/30">AI</div>
                    <div class="bg-indigo-900/20 border border-indigo-500/20 text-slate-200 p-3 rounded-2xl rounded-tl-none max-w-[80%] text-sm leading-relaxed shadow-sm">
                        ${text}
                    </div>
                `;
            }
            container.appendChild(div);
            scrollToBottom();
            return div.id;
        }

        function appendThinking() {
            const container = document.getElementById('chat-container');
            const id = 'thinking-' + Date.now();
            const div = document.createElement('div');
            div.id = id;
            div.className = "flex items-start gap-3 animate-pulse";
            div.innerHTML = `
                 <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-xs font-bold opacity-50">AI</div>
                 <div class="bg-slate-800/50 text-slate-400 p-3 rounded-2xl rounded-tl-none text-xs italic">
                    Thinking...
                 </div>
            `;
            container.appendChild(div);
            scrollToBottom();
            return id;
        }

        function removeMessage(id) {
            const el = document.getElementById(id);
            if (el) el.remove();
        }

        function scrollToBottom() {
            const container = document.getElementById('chat-container');
            container.scrollTop = container.scrollHeight;
        }
    </script>
    <style>
        @keyframes fade-in-up {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up {
            animation: fade-in-up 0.3s ease-out forwards;
        }
        /* Custom Scrollbar for chat */
        #chat-container::-webkit-scrollbar {
            width: 6px;
        }
        #chat-container::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.5);
        }
        #chat-container::-webkit-scrollbar-thumb {
            background: rgba(71, 85, 105, 0.5);
            border-radius: 3px;
        }
        #chat-container::-webkit-scrollbar-thumb:hover {
            background: rgba(99, 102, 241, 0.5);
        }
    </style>

        </div>
    </div>
</body>
</html>
