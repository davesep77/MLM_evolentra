<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evolentra - Setup Verification</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #f1f5f9;
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 1.5rem;
        }

        .status {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        .status-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .status-icon {
            font-size: 1.5rem;
        }

        .status-text {
            flex: 1;
        }

        .status-text h3 {
            font-size: 1.125rem;
            margin-bottom: 0.25rem;
        }

        .status-text p {
            font-size: 0.875rem;
            color: #94a3b8;
        }

        .credentials {
            background: rgba(16, 185, 129, 0.05);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }

        .credentials h3 {
            margin-bottom: 1rem;
            color: #10b981;
        }

        .credential-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 0.375rem;
            margin-bottom: 0.5rem;
        }

        .credential-label {
            font-weight: 600;
            color: #94a3b8;
        }

        .credential-value {
            font-family: monospace;
            color: #10b981;
        }

        .btn {
            display: inline-block;
            padding: 0.875rem 2rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: transform 0.2s;
            text-align: center;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .info-item {
            background: rgba(0, 0, 0, 0.2);
            padding: 1rem;
            border-radius: 0.5rem;
        }

        .info-item h4 {
            color: #10b981;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .info-item p {
            font-size: 1.25rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Setup Verification</h1>
            <p>Evolentra MLM Platform Status</p>
        </div>

        <?php
        require_once 'config_db.php';

        $checks = [];
        $overallStatus = true;

        // Check database connection
        try {
            if (isset($conn) && $conn instanceof PDO) {
                $stmt = $conn->query("SELECT COUNT(*) as count FROM mlm_users");
                $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                $checks[] = [
                    'title' => 'Database Connection',
                    'status' => 'success',
                    'message' => "Successfully connected to Supabase PostgreSQL database. Found $userCount users."
                ];
            } else {
                throw new Exception("Invalid database connection object");
            }
        } catch (Exception $e) {
            $checks[] = [
                'title' => 'Database Connection',
                'status' => 'error',
                'message' => 'Failed to connect: ' . $e->getMessage()
            ];
            $overallStatus = false;
        }

        // Check tables
        try {
            $tables = ['mlm_users', 'mlm_wallets', 'mlm_referral_links', 'mlm_withdrawal_requests'];
            $foundTables = [];

            foreach ($tables as $table) {
                $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                $foundTables[] = "$table ($count rows)";
            }

            $checks[] = [
                'title' => 'Database Tables',
                'status' => 'success',
                'message' => 'All required tables exist: ' . implode(', ', $foundTables)
            ];
        } catch (Exception $e) {
            $checks[] = [
                'title' => 'Database Tables',
                'status' => 'error',
                'message' => 'Error checking tables: ' . $e->getMessage()
            ];
            $overallStatus = false;
        }

        // Check admin user
        try {
            $stmt = $conn->query("SELECT username, email, role FROM mlm_users WHERE role = 'admin' LIMIT 1");
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin) {
                $checks[] = [
                    'title' => 'Admin Account',
                    'status' => 'success',
                    'message' => "Admin user '{$admin['username']}' ({$admin['email']}) is ready"
                ];
            } else {
                throw new Exception("No admin user found");
            }
        } catch (Exception $e) {
            $checks[] = [
                'title' => 'Admin Account',
                'status' => 'error',
                'message' => 'Admin user not found: ' . $e->getMessage()
            ];
            $overallStatus = false;
        }

        // Display results
        foreach ($checks as $check) {
            $statusClass = $check['status'] === 'success' ? 'status-success' : 'status-error';
            $icon = $check['status'] === 'success' ? '✓' : '✗';

            echo '<div class="card">';
            echo '<div class="status ' . $statusClass . '">';
            echo '<div class="status-icon">' . $icon . '</div>';
            echo '<div class="status-text">';
            echo '<h3>' . $check['title'] . '</h3>';
            echo '<p>' . $check['message'] . '</p>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }

        // Get statistics
        try {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM mlm_users");
            $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            $stmt = $conn->query("SELECT SUM(roi_wallet + referral_wallet + binary_wallet) as total FROM mlm_wallets");
            $totalBalance = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            $stmt = $conn->query("SELECT COUNT(*) as count FROM mlm_withdrawal_requests WHERE status = 'pending'");
            $pendingWithdrawals = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            echo '<div class="card">';
            echo '<h3>Platform Statistics</h3>';
            echo '<div class="info-grid">';
            echo '<div class="info-item"><h4>Total Users</h4><p>' . $totalUsers . '</p></div>';
            echo '<div class="info-item"><h4>Total Balance</h4><p>$' . number_format($totalBalance, 2) . '</p></div>';
            echo '<div class="info-item"><h4>Pending Withdrawals</h4><p>' . $pendingWithdrawals . '</p></div>';
            echo '</div>';
            echo '</div>';
        } catch (Exception $e) {
            // Silently fail
        }
        ?>

        <div class="card">
            <div class="credentials">
                <h3>Default Login Credentials</h3>
                <div class="credential-item">
                    <span class="credential-label">Username:</span>
                    <span class="credential-value">admin</span>
                </div>
                <div class="credential-item">
                    <span class="credential-label">Password:</span>
                    <span class="credential-value">password</span>
                </div>
                <div class="credential-item">
                    <span class="credential-label">Demo User:</span>
                    <span class="credential-value">demo_user / password</span>
                </div>
                <p style="margin-top: 1rem; color: #94a3b8; font-size: 0.875rem;">
                    ⚠️ Please change the default password after first login
                </p>
            </div>

            <div class="actions">
                <a href="index.php" class="btn">View Landing Page</a>
                <a href="login.php" class="btn">Admin Login</a>
                <a href="register.php" class="btn">Register New User</a>
            </div>
        </div>

        <?php if ($overallStatus): ?>
        <div class="card" style="border-color: rgba(16, 185, 129, 0.3); background: rgba(16, 185, 129, 0.05);">
            <div class="status status-success">
                <div class="status-icon">🎉</div>
                <div class="status-text">
                    <h3>Setup Complete!</h3>
                    <p>Your Evolentra MLM platform is ready to use. All systems are operational.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
