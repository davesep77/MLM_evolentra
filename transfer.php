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
$receipt = null;

// AJAX Handler for User Search
if (isset($_GET['check_user'])) {
    header('Content-Type: application/json');
    $username = $conn->real_escape_string($_GET['check_user']);
    // Verify recipient (and ensure it's not self)
    $q = $conn->query("SELECT id, username, email FROM mlm_users WHERE username='$username' LIMIT 1");
    if ($q->num_rows > 0) {
        $u = $q->fetch_assoc();
        if ($u['id'] == $user_id) {
            echo json_encode(['status' => 'error', 'message' => 'Cannot transfer to self']);
        } else {
            // Mask email
            $masked_email = substr($u['email'], 0, 3) . '***' . substr($u['email'], strpos($u['email'], '@'));
            echo json_encode(['status' => 'found', 'username' => $u['username'], 'email' => $masked_email, 'id' => $u['id']]);
        }
    } else {
        echo json_encode(['status' => 'not_found']);
    }
    exit;
}

// Handle Transfer Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_username = $conn->real_escape_string($_POST['recipient']);
    $amount = floatval($_POST['amount']);
    $wallet_type = $_POST['wallet_type']; 
    $password = $_POST['password'];

    // Validations...
    if ($amount <= 0) {
        $error = "Invalid amount.";
    } else {
        $u = $conn->query("SELECT password FROM mlm_users WHERE id=$user_id")->fetch_assoc();
        if (!password_verify($password, $u['password'])) {
            $error = "Incorrect password.";
        } else {
            $w = $conn->query("SELECT $wallet_type FROM mlm_wallets WHERE user_id=$user_id")->fetch_assoc();
            if ($w[$wallet_type] < $amount) {
                $error = "Insufficient balance.";
            } else {
                $r = $conn->query("SELECT id FROM mlm_users WHERE username='$recipient_username'");
                if ($r->num_rows === 0) {
                    $error = "Recipient not found.";
                } else {
                    $recipient_id = $r->fetch_assoc()['id'];
                    if ($recipient_id == $user_id) {
                        $error = "Cannot transfer to self.";
                    } else {
                        // EXECUTE TRANSFER
                        $conn->begin_transaction();
                        try {
                            // Deduct
                            $conn->query("UPDATE mlm_wallets SET $wallet_type = $wallet_type - $amount WHERE user_id=$user_id");
                            
                            // Insert Request
                            $sql = "INSERT INTO mlm_transfer_requests (sender_id, receiver_id, amount, wallet_type, status) 
                                    VALUES ($user_id, $recipient_id, $amount, '$wallet_type', 'pending')";
                            $conn->query($sql);
                            $tx_id = $conn->insert_id;

                            // Log
                            $conn->query("INSERT INTO mlm_transactions (user_id, type, amount, description) VALUES ($user_id, 'transfer_request', $amount, 'Transfer Request #$tx_id to $recipient_username')");

                            $conn->commit();
                            
                            // Generate Receipt Data
                            $receipt = [
                                'tx_id' => 'TRF-' . str_pad($tx_id, 8, '0', STR_PAD_LEFT),
                                'amount' => $amount,
                                'recipient' => $recipient_username,
                                'date' => date('M d, Y H:i'),
                                'status' => 'Pending Approval'
                            ];
                        } catch (Exception $e) {
                            $conn->rollback();
                            $error = "System Error: " . $e->getMessage();
                        }
                    }
                }
            }
        }
    }
}

$wallet = $conn->query("SELECT * FROM mlm_wallets WHERE user_id=$user_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Transfer - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #0f172a; color: #f8fafc; }
        .glass-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; padding: 2rem; }
        .step-active { border-color: #4f46e5; color: white; }
        .step-inactive { border-color: #334155; color: #64748b; }
        /* Animation for wizard */
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="flex">

    <?php include 'sidebar_nav.php'; ?>

    <div class="flex-1 ml-[280px] p-8">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-3xl font-bold text-white tracking-tight">Global Transfer</h1>
                <p class="text-slate-400">Secure Peer-to-Peer Transaction Network</p>
            </div>
            <!-- Balance Pill -->
            <div class="bg-indigo-900/40 border border-indigo-500/30 px-6 py-2 rounded-full flex items-center gap-3">
                <span class="text-indigo-300 text-sm font-semibold uppercase">Wallet Balance</span>
                <span class="text-white font-bold text-lg">$<?= number_format($wallet['roi_wallet'] + $wallet['referral_wallet'], 2) ?></span>
            </div>
        </header>

        <div class="max-w-4xl mx-auto">
            
            <?php if ($receipt): ?>
                <!-- RECEIPT VIEW -->
                <div class="glass-card max-w-lg mx-auto text-center fade-in border-emerald-500/30">
                    <div class="w-16 h-16 bg-emerald-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-3xl">✅</span>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-2">Transfer Submitted</h2>
                    <p class="text-slate-400 mb-8">Your request is awaiting admin approval.</p>
                    
                    <div class="bg-slate-900/50 rounded-lg p-6 text-left mb-8 border border-white/5">
                        <div class="flex justify-between mb-3 text-sm">
                            <span class="text-slate-500">Transaction ID</span>
                            <span class="text-white font-mono"><?= $receipt['tx_id'] ?></span>
                        </div>
                        <div class="flex justify-between mb-3 text-sm">
                            <span class="text-slate-500">Amount</span>
                            <span class="text-emerald-400 font-bold text-lg">$<?= number_format($receipt['amount'], 2) ?></span>
                        </div>
                        <div class="flex justify-between mb-3 text-sm">
                            <span class="text-slate-500">Recipient</span>
                            <span class="text-white"><?= htmlspecialchars($receipt['recipient']) ?></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Date</span>
                            <span class="text-slate-300"><?= $receipt['date'] ?></span>
                        </div>
                    </div>

                    <a href="transfer.php" class="inline-block bg-slate-700 hover:bg-slate-600 text-white px-8 py-3 rounded-lg font-semibold transition-all">Make Another Transfer</a>
                </div>
            <?php else: ?>

                <!-- WIZARD UI -->
                <div class="glass-card fade-in">
                    
                    <!-- Steps Indicator -->
                    <div class="flex items-center justify-center mb-8 gap-4">
                        <div id="step-ind-1" class="flex items-center gap-2 text-sm font-bold step-active transition-colors">
                            <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center">1</div>
                            <span>Recipient</span>
                        </div>
                        <div class="h-[2px] w-12 bg-slate-700"></div>
                        <div id="step-ind-2" class="flex items-center gap-2 text-sm font-bold step-inactive transition-colors">
                            <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center">2</div>
                            <span>Details</span>
                        </div>
                        <div class="h-[2px] w-12 bg-slate-700"></div>
                        <div id="step-ind-3" class="flex items-center gap-2 text-sm font-bold step-inactive transition-colors">
                            <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center">3</div>
                            <span>Confirm</span>
                        </div>
                    </div>

                    <?php if($error): ?>
                        <div class="bg-red-500/20 text-red-300 p-4 rounded mb-6 border border-red-500/50 text-center"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST" id="transferForm">
                        
                        <!-- STEP 1: FIND USER -->
                        <div id="step-1">
                            <h3 class="text-xl font-semibold mb-6 text-center">Find Recipient</h3>
                            <div class="max-w-md mx-auto">
                                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Recipient Username</label>
                                <div class="flex gap-2">
                                    <input type="text" id="check_username" class="flex-1 bg-slate-900 border border-slate-700 rounded-lg p-3 text-white focus:border-indigo-500 outline-none placeholder-slate-600" placeholder="e.g. johndoe">
                                    <button type="button" onclick="checkUser()" class="bg-indigo-600 hover:bg-indigo-500 text-white px-6 rounded-lg font-bold transition-colors">Verify</button>
                                </div>
                                <div id="user-result" class="mt-4 hidden p-4 bg-slate-800/50 rounded-lg border border-slate-700 flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-500 flex items-center justify-center text-xl">👤</div>
                                    <div>
                                        <div id="res-name" class="font-bold text-white text-lg"></div>
                                        <div id="res-email" class="text-xs text-slate-400"></div>
                                    </div>
                                    <div class="ml-auto text-emerald-400 text-sm flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span> Verified
                                    </div>
                                </div>
                                <div id="user-error" class="mt-4 hidden text-red-400 text-sm text-center"></div>

                                <div class="mt-8 flex justify-end">
                                    <button type="button" id="btn-next-1" disabled onclick="nextStep(2)" class="px-8 py-3 bg-slate-700 text-slate-400 rounded-lg font-bold cursor-not-allowed transition-all">Next Step &rarr;</button>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 2: DETAILS -->
                        <div id="step-2" class="hidden">
                            <h3 class="text-xl font-semibold mb-6 text-center">Transfer Details</h3>
                            <div class="max-w-md mx-auto space-y-6">
                                
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Source Wallet</label>
                                    <div class="grid grid-cols-2 gap-4">
                                        <label class="cursor-pointer">
                                            <input type="radio" name="wallet_type" value="roi_wallet" checked class="peer sr-only">
                                            <div class="p-4 bg-slate-800 border-2 border-transparent peer-checked:border-indigo-500 rounded-lg hover:bg-slate-700 transition-all">
                                                <div class="text-xs text-slate-400 mb-1">ROI Balance</div>
                                                <div class="font-bold text-white text-lg">$<?= number_format($wallet['roi_wallet'], 2) ?></div>
                                            </div>
                                        </label>
                                        <label class="cursor-pointer">
                                            <input type="radio" name="wallet_type" value="referral_wallet" class="peer sr-only">
                                            <div class="p-4 bg-slate-800 border-2 border-transparent peer-checked:border-indigo-500 rounded-lg hover:bg-slate-700 transition-all">
                                                <div class="text-xs text-slate-400 mb-1">Ref Balance</div>
                                                <div class="font-bold text-white text-lg">$<?= number_format($wallet['referral_wallet'], 2) ?></div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Amount to Send</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-3 text-slate-500">$</span>
                                        <input type="number" name="amount" id="amount-input" step="0.01" min="1" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-3 pl-8 text-white focus:border-indigo-500 outline-none font-mono text-lg" placeholder="0.00">
                                    </div>
                                </div>

                                <div class="flex justify-between pt-4">
                                    <button type="button" onclick="prevStep(1)" class="text-slate-400 hover:text-white font-semibold">Back</button>
                                    <button type="button" onclick="nextStep(3)" class="bg-indigo-600 hover:bg-indigo-500 text-white px-8 py-3 rounded-lg font-bold shadow-lg shadow-indigo-500/20 transition-all">Review &rarr;</button>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 3: CONFIRM -->
                        <div id="step-3" class="hidden">
                            <h3 class="text-xl font-semibold mb-6 text-center">Confirm Transaction</h3>
                            <div class="max-w-sm mx-auto">
                                
                                <div class="bg-indigo-900/20 border border-indigo-500/20 rounded-lg p-6 mb-6">
                                    <div class="text-center mb-6">
                                        <div class="text-sm text-slate-400 mb-1">Sending Amount</div>
                                        <div class="text-3xl font-bold text-white" id="confirm-amount">$0.00</div>
                                    </div>
                                    <div class="space-y-3 text-sm border-t border-white/10 pt-4">
                                        <div class="flex justify-between">
                                            <span class="text-slate-400">To Recipient:</span>
                                            <span class="text-white font-semibold" id="confirm-user">...</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-400">Wallet:</span>
                                            <span class="text-white capitalize" id="confirm-wallet">...</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-400">Fee:</span>
                                            <span class="text-slate-200">Free</span>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Security Password</label>
                                    <input type="password" name="password" required class="w-full bg-slate-900 border border-slate-700 rounded-lg p-3 text-white focus:border-indigo-500 outline-none" placeholder="Enter password to confirm">
                                </div>

                                <!-- Hidden Inputs -->
                                <input type="hidden" name="recipient" id="final-recipient">

                                <div class="mt-8">
                                    <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-400 text-white font-bold py-3 rounded-lg shadow-lg shadow-emerald-500/20 transition-all flex justify-center items-center gap-2">
                                        <span>🔒</span> Confirm Transfer
                                    </button>
                                    <button type="button" onclick="prevStep(2)" class="w-full mt-3 text-slate-500 hover:text-slate-300 text-sm">Cancel</button>
                                </div>

                            </div>
                        </div>

                    </form>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <script>
        function checkUser() {
            const username = document.getElementById('check_username').value;
            const resDiv = document.getElementById('user-result');
            const errDiv = document.getElementById('user-error');
            const btn = document.getElementById('btn-next-1');

            if (!username) return;

            // Reset
            resDiv.classList.add('hidden');
            errDiv.classList.add('hidden');
            btn.disabled = true;
            btn.classList.add('cursor-not-allowed', 'bg-slate-700', 'text-slate-400');
            btn.classList.remove('bg-indigo-600', 'text-white', 'hover:bg-indigo-500');

            fetch(`transfer.php?check_user=${username}`)
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'found') {
                        document.getElementById('res-name').innerText = data.username;
                        document.getElementById('res-email').innerText = data.email;
                        document.getElementById('final-recipient').value = data.username;
                        document.getElementById('confirm-user').innerText = data.username;
                        
                        resDiv.classList.remove('hidden');
                        
                        // Enable Next
                        btn.disabled = false;
                        btn.classList.remove('cursor-not-allowed', 'bg-slate-700', 'text-slate-400');
                        btn.classList.add('bg-indigo-600', 'text-white', 'hover:bg-indigo-500');
                    } else {
                        errDiv.innerText = data.message || "User not found.";
                        errDiv.classList.remove('hidden');
                    }
                });
        }

        function nextStep(step) {
            // Validate step 2
            if (step === 3) {
                const amt = document.getElementById('amount-input').value;
                if (!amt || amt <= 0) {
                    alert("Please enter a valid amount");
                    return;
                }
                // Update Confirm Screen
                document.getElementById('confirm-amount').innerText = '$' + parseFloat(amt).toFixed(2);
                const walletType = document.querySelector('input[name="wallet_type"]:checked').value;
                document.getElementById('confirm-wallet').innerText = walletType.replace('_', ' ');
            }

            document.getElementById('step-1').classList.add('hidden');
            document.getElementById('step-2').classList.add('hidden');
            document.getElementById('step-3').classList.add('hidden');

            document.getElementById(`step-${step}`).classList.remove('hidden');
            document.getElementById(`step-${step}`).classList.add('fade-in');

            // Update Indicators
            for(let i=1; i<=3; i++) {
                const el = document.getElementById(`step-ind-${i}`);
                if (i === step) {
                    el.classList.add('step-active');
                    el.classList.remove('step-inactive');
                } else {
                    el.classList.remove('step-active');
                    el.classList.add('step-inactive');
                }
            }
        }

        function prevStep(step) {
            nextStep(step);
        }
    </script>
</body>
</html>
