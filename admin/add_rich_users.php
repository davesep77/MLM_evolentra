<?php
require '../config_db.php';

// Security: Only admin can run this
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied: Admin only.");
}

$message = "";
$error = "";

// Helper for names
$first_names = ['Maximilian', 'Alexander', 'Sophia', 'Isabella', 'Leonardo', 'Sebastian', 'Victoria', 'Charlotte', 'Julian', 'Dominic', 'Adrian', 'Elena', 'Gabriel', 'Valentina', 'Matteo'];
$last_names = ['Rothschild', 'Belfort', 'Morgan', 'Vanderbilt', 'Carnegie', 'Rockefeller', 'Walton', 'Buffett', 'Gates', 'Kovacs', 'Sokolov', 'Chen', 'Matsumoto', 'Garcia', 'Muller'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $num_users = intval($_POST['num_users']);
    $min_inv = intval($_POST['min_inv']);
    $max_inv = intval($_POST['max_inv']);
    $history_days = intval($_POST['history_days']);
    $pass = password_hash('rich_pass_123', PASSWORD_DEFAULT);

    // Get potential sponsors
    $sponsors = [];
    $res = $conn->query("SELECT id FROM mlm_users ORDER BY investment DESC LIMIT 10");
    while($row = $res->fetch_assoc()) $sponsors[] = $row['id'];

    $created_count = 0;

    for ($i = 0; $i < $num_users; $i++) {
        $fname = $first_names[array_rand($first_names)];
        $lname = $last_names[array_rand($last_names)];
        $username = strtolower($fname . "_" . rand(10, 99) . "_" . substr(md5(uniqid()), 0, 4));
        $email = $username . "@evolentra-rich.com";
        $investment = rand($min_inv, $max_inv);
        $sponsor_id = !empty($sponsors) ? $sponsors[array_rand($sponsors)] : 1;
        $position = (rand(0, 1) == 0) ? 'L' : 'R';
        
        // Random join date
        $created_at = date('Y-m-d H:i:s', strtotime("-" . rand(30, 60) . " days"));

        $sql = "INSERT INTO mlm_users (username, email, password, role, sponsor_id, binary_position, investment, created_at, status) 
                VALUES ('$username', '$email', '$pass', 'member', $sponsor_id, '$position', $investment, '$created_at', 'active')";
        
        if ($conn->query($sql)) {
            $user_id = $conn->insert_id;
            
            // 1. Wallets
            $conn->query("INSERT INTO mlm_wallets (user_id) VALUES ($user_id)");
            
            // 2. Settings (Verified for rich users)
            $conn->query("INSERT INTO mlm_user_settings (user_id, binance_link_status, usdt_address_bep20) 
                          VALUES ($user_id, 'verified', '0x" . substr(md5($username), 0, 40) . "')");

            // 3. Transactions History
            for ($d = $history_days; $d >= 0; $d--) {
                $tx_date = date('Y-m-d H:i:s', strtotime("-$d days"));
                
                // ROI (1.25% - 1.5%)
                $roi_rate = 0.0125 + (rand(0, 25) / 10000);
                $roi_amt = $investment * $roi_rate;
                $conn->query("INSERT INTO mlm_transactions (user_id, amount, type, description, created_at, status) 
                              VALUES ($user_id, $roi_amt, 'ROI', 'Algorithm Performance Payout', '$tx_date', 'completed')");

                // Occasional Binary/Referral
                if (rand(0, 4) == 1) {
                    $ref_amt = rand(100, 500);
                    $conn->query("INSERT INTO mlm_transactions (user_id, amount, type, description, created_at, status) 
                                  VALUES ($user_id, $ref_amt, 'REFERRAL', 'High-Tier Referral Commission', '$tx_date', 'completed')");
                }
            }

            // Add to sponsor pool for next iterations
            if (rand(0, 2) == 1) $sponsors[] = $user_id;
            $created_count++;
        }
    }
    $message = "Successfully generated $created_count Elite High-Value Users!";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elite User Generator - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-deep: #0f172a;
            --accent: #a78bfa;
            --glass: rgba(30, 41, 59, 0.7);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-deep);
            color: #f8fafc;
            margin: 0;
            display: flex;
        }

        .main-content {
            margin-left: 280px;
            padding: 3rem;
            width: 100%;
            max-width: 1200px;
        }

        .header {
            margin-bottom: 3rem;
        }

        .header h1 {
            font-size: 2.5rem;
            background: linear-gradient(135deg, #fff 0%, #a78bfa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
        }

        .header p {
            color: #94a3b8;
            font-size: 1.1rem;
        }

        .generator-card {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 2rem;
            padding: 3rem;
            max-width: 600px;
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 500;
            color: #cbd5e1;
        }

        .form-group input, .form-group select {
            width: 100%;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 1rem;
            color: white;
            font-family: inherit;
            box-sizing: border-box;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .btn-generate {
            background: linear-gradient(135deg, #7c3aed 0%, #db2777 100%);
            color: white;
            border: none;
            padding: 1.25rem;
            border-radius: 1rem;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
            transition: all 0.3s;
            box-shadow: 0 10px 30px rgba(124, 58, 237, 0.3);
        }

        .btn-generate:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(124, 58, 237, 0.5);
        }

        .alert {
            padding: 1rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            font-weight: 600;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid #10b981;
            color: #34d399;
        }

        .rich-badge {
            background: gold;
            color: black;
            padding: 0.2rem 0.6rem;
            border-radius: 0.5rem;
            font-size: 0.7rem;
            font-weight: 900;
            vertical-align: middle;
            margin-left: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>Elite Asset Seeding <span class="rich-badge">ELITE</span></h1>
            <p>Generate high-value biological nodes for ecosystem demonstration.</p>
        </div>

        <?php if($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="generator-card">
            <form method="POST">
                <div class="form-group">
                    <label>Quantity of Nodes</label>
                    <select name="num_users">
                        <option value="5">5 High-Value Nodes</option>
                        <option value="10" selected>10 Elite Nodes</option>
                        <option value="25">25 Network Anchors</option>
                        <option value="50">50 Ecosystem Influx</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Min Investment ($)</label>
                        <input type="number" name="min_inv" value="5000">
                    </div>
                    <div class="form-group">
                        <label>Max Investment ($)</label>
                        <input type="number" name="max_inv" value="25000">
                    </div>
                </div>

                <div class="form-group">
                    <label>Simulation Depth (Days of History)</label>
                    <input type="number" name="history_days" value="30">
                    <p style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;">Generates ROI and Referral logs for each day to populate charts.</p>
                </div>

                <button type="submit" name="generate" class="btn-generate">
                    <i class="fas fa-microchip"></i> EXECUTE ECOSYSTEM SEEDING
                </button>
            </form>
        </div>

        <div style="margin-top: 3rem; color: #64748b; font-size: 0.875rem;">
            <p><i class="fas fa-info-circle"></i> Seeded users are automatically assigned passwords: <b>rich_pass_123</b></p>
            <p><i class="fas fa-shield-alt"></i> All seeded users are pre-verified for Binance payouts.</p>
        </div>
    </div>
</body>
</html>
