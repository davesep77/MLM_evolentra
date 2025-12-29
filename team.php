<?php
session_start();
require 'config_db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

try {
    $stmt = $conn->prepare("
        SELECT u.id, u.username, u.email, u.investment, u.created_at, u.status, u.binary_position,
               w.roi_wallet, w.referral_wallet, w.binary_wallet
        FROM mlm_users u
        LEFT JOIN mlm_wallets w ON u.id = w.user_id
        WHERE u.sponsor_id = :user_id
        ORDER BY u.created_at DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $team_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_directs = count($team_members);
    $active_count = count(array_filter($team_members, fn($m) => $m['investment'] > 0));
    $team_investment = array_sum(array_column($team_members, 'investment'));

} catch (PDOException $e) {
    error_log("Team page error: " . $e->getMessage());
    $team_members = [];
    $total_directs = 0;
    $active_count = 0;
    $team_investment = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Team - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #f1f5f9; min-height: 100vh; }
        .dashboard-container { display: flex; }
        .sidebar { width: 260px; background: rgba(30, 41, 59, 0.8); backdrop-filter: blur(10px); border-right: 1px solid rgba(255, 255, 255, 0.1); padding: 2rem 1rem; height: 100vh; position: sticky; top: 0; }
        .logo { font-size: 1.75rem; font-weight: 800; background: linear-gradient(135deg, #10b981 0%, #34d399 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 2rem; text-align: center; }
        .nav-menu { list-style: none; }
        .nav-menu li { margin-bottom: 0.5rem; }
        .nav-menu a { display: flex; align-items: center; gap: 0.75rem; padding: 0.875rem 1rem; color: #94a3b8; text-decoration: none; border-radius: 0.5rem; transition: all 0.2s; }
        .nav-menu a:hover, .nav-menu a.active { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .main-content { flex: 1; padding: 2rem; }
        .page-header { background: rgba(30, 41, 59, 0.6); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1rem; padding: 2rem; margin-bottom: 2rem; }
        .page-header h1 { font-size: 2rem; margin-bottom: 0.5rem; }
        .page-header p { color: #94a3b8; }
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-box { background: rgba(30, 41, 59, 0.6); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1rem; padding: 1.5rem; }
        .stat-label { font-size: 0.875rem; color: #64748b; text-transform: uppercase; font-weight: 600; margin-bottom: 0.5rem; }
        .stat-value { font-size: 2rem; font-weight: 800; color: #10b981; }
        .table-container { background: rgba(30, 41, 59, 0.6); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1rem; padding: 2rem; }
        table { width: 100%; border-collapse: separate; border-spacing: 0 0.5rem; }
        th { text-align: left; padding: 1rem; color: #64748b; font-size: 0.875rem; font-weight: 600; text-transform: uppercase; }
        td { padding: 1rem; background: rgba(0, 0, 0, 0.2); }
        tr:hover td { background: rgba(16, 185, 129, 0.1); }
        tr td:first-child { border-radius: 0.5rem 0 0 0.5rem; }
        tr td:last-child { border-radius: 0 0.5rem 0.5rem 0; }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600; }
        .status-active { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .status-inactive { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .position-badge { padding: 0.25rem 0.75rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600; }
        .position-left { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .position-right { background: rgba(236, 72, 153, 0.2); color: #ec4899; }
        .logout-btn { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 0.75rem 1.5rem; border-radius: 0.5rem; text-decoration: none; display: inline-block; font-weight: 600; transition: all 0.2s; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo">Evolentra</div>
            <ul class="nav-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="team.php" class="active"><i class="fas fa-users"></i> My Team</a></li>
                <li><a href="genealogy.php"><i class="fas fa-sitemap"></i> Binary Tree</a></li>
                <li><a href="income_report.php"><i class="fas fa-chart-line"></i> Income Report</a></li>
                <li><a href="withdraw.php"><i class="fas fa-money-bill-wave"></i> Withdraw</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            </ul>
            <div style="position: absolute; bottom: 2rem; left: 1rem; right: 1rem;">
                <a href="logout.php" class="logout-btn" style="width: 100%; text-align: center; display: block;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>My Team</h1>
                <p>View and manage your direct referrals</p>
            </div>

            <div class="stats-row">
                <div class="stat-box">
                    <div class="stat-label">Total Direct Referrals</div>
                    <div class="stat-value"><?= $total_directs ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Active Members</div>
                    <div class="stat-value"><?= $active_count ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Total Team Investment</div>
                    <div class="stat-value">$<?= number_format($team_investment, 2) ?></div>
                </div>
            </div>

            <div class="table-container">
                <h3 style="margin-bottom: 1.5rem; font-size: 1.25rem;">Direct Referrals</h3>
                <?php if (count($team_members) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Position</th>
                            <th>Investment</th>
                            <th>Total Earnings</th>
                            <th>Join Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($team_members as $member): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($member['username']) ?></strong></td>
                            <td><?= htmlspecialchars($member['email']) ?></td>
                            <td>
                                <?php if ($member['binary_position'] === 'left'): ?>
                                    <span class="position-badge position-left">Left</span>
                                <?php elseif ($member['binary_position'] === 'right'): ?>
                                    <span class="position-badge position-right">Right</span>
                                <?php else: ?>
                                    <span style="color: #64748b;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>$<?= number_format($member['investment'], 2) ?></td>
                            <td>$<?= number_format(($member['roi_wallet'] ?? 0) + ($member['referral_wallet'] ?? 0) + ($member['binary_wallet'] ?? 0), 2) ?></td>
                            <td><?= date('M d, Y', strtotime($member['created_at'])) ?></td>
                            <td>
                                <span class="status-badge status-<?= $member['status'] ?>">
                                    <?= ucfirst($member['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="text-align: center; padding: 3rem; color: #64748b;">
                    <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.3;"></i>
                    No team members yet. Share your referral link to start building your team!
                </p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
