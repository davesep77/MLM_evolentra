<?php
require 'config_db.php';
require_once 'lib/ReferralEngine.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get referral engine
$refEngine = new ReferralEngine($conn);

// Get comprehensive stats
$stats = $refEngine->getReferralStats($user_id);

// Get referral links
$user_query = $conn->query("SELECT referral_code FROM mlm_users WHERE id=$user_id");
$refCode = $user_query->fetch_assoc()['referral_code'] ?? '';

$path = dirname($_SERVER['PHP_SELF']);
if ($path == '/' || $path == '\\') { $path = ''; }
$base_url = "http://" . $_SERVER['HTTP_HOST'] . str_replace('\\', '/', $path) . "/register.php";
$link_general = $base_url . "?ref=" . $refCode;
$link_left = $base_url . "?ref=" . $refCode . "&position=left";
$link_right = $base_url . "?ref=" . $refCode . "&position=right";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Analytics - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar_nav.php'; ?>
        
        <div class="main-content">
            <div class="container">
                <h1 class="page-title"><i class="fas fa-chart-line"></i> Referral Analytics</h1>
                
                <!-- Stats Overview -->
                <div class="grid-layout-4">
                    <div class="glass-card stat-card">
                        <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Total Referrals</div>
                            <div class="stat-value"><?= $stats['total_referrals'] ?></div>
                        </div>
                    </div>
                    
                    <div class="glass-card stat-card">
                        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Active Referrals</div>
                            <div class="stat-value"><?= $stats['active_referrals'] ?></div>
                        </div>
                    </div>
                    
                    <div class="glass-card stat-card">
                        <div class="stat-icon" style="background: rgba(167, 139, 250, 0.1); color: #a78bfa;">
                            <i class="fas fa-mouse-pointer"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Total Clicks</div>
                            <div class="stat-value"><?= $stats['total_clicks'] ?></div>
                        </div>
                    </div>
                    
                    <div class="glass-card stat-card">
                        <div class="stat-icon" style="background: rgba(251, 191, 36, 0.1); color: #fbbf24;">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Conversion Rate</div>
                            <div class="stat-value"><?= $stats['conversion_rate'] ?>%</div>
                        </div>
                    </div>
                </div>
                
                <!-- Earnings Overview -->
                <div class="grid-layout-2 mt-6">
                    <div class="glass-card">
                        <h3 class="card-title">Referral Earnings</h3>
                        <div class="earnings-display">
                            <div class="earnings-item">
                                <span class="earnings-label">Total Earned</span>
                                <span class="earnings-amount">$<?= number_format($stats['total_earned'], 2) ?></span>
                            </div>
                            <div class="earnings-item">
                                <span class="earnings-label">This Month</span>
                                <span class="earnings-amount text-green">$<?= number_format($stats['this_month_earned'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="glass-card">
                        <h3 class="card-title">Link Performance</h3>
                        <div class="link-stats">
                            <?php foreach ($stats['links'] as $type => $link): ?>
                                <div class="link-stat-item">
                                    <div class="link-type"><?= ucfirst($type) ?> Link</div>
                                    <div class="link-metrics">
                                        <span><?= $link['clicks'] ?> clicks</span>
                                        <span><?= $link['conversions'] ?> conversions</span>
                                        <span>$<?= number_format($link['total_earned'], 2) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Referral Links -->
                <div class="glass-card mt-6">
                    <h3 class="card-title"><i class="fas fa-link"></i> Your Referral Links</h3>
                    <div class="referral-links-grid">
                        <div class="ref-link-box">
                            <label>General Link</label>
                            <div class="link-input-group">
                                <input type="text" id="link-general" value="<?= $link_general ?>" readonly>
                                <button onclick="copyLink('link-general')" class="btn-copy"><i class="fas fa-copy"></i></button>
                            </div>
                        </div>
                        <div class="ref-link-box">
                            <label>Left Team Link</label>
                            <div class="link-input-group">
                                <input type="text" id="link-left" value="<?= $link_left ?>" readonly>
                                <button onclick="copyLink('link-left')" class="btn-copy"><i class="fas fa-copy"></i></button>
                            </div>
                        </div>
                        <div class="ref-link-box">
                            <label>Right Team Link</label>
                            <div class="link-input-group">
                                <input type="text" id="link-right" value="<?= $link_right ?>" readonly>
                                <button onclick="copyLink('link-right')" class="btn-copy"><i class="fas fa-copy"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="ref-code-display">
                        <span>Your Referral Code:</span>
                        <code><?= $refCode ?></code>
                    </div>
                </div>
                
                <!-- Recent Conversions -->
                <?php if (!empty($stats['recent_conversions'])): ?>
                <div class="glass-card mt-6">
                    <h3 class="card-title"><i class="fas fa-history"></i> Recent Conversions</h3>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Investment</th>
                                    <th>Commission</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['recent_conversions'] as $conversion): ?>
                                <tr>
                                    <td><?= htmlspecialchars($conversion['username']) ?></td>
                                    <td>$<?= number_format($conversion['investment'], 2) ?></td>
                                    <td class="text-green">$<?= number_format($conversion['commission_amount'], 2) ?></td>
                                    <td><?= date('M d, Y', strtotime($conversion['earned_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Top Performers -->
                <?php if (!empty($stats['top_performers'])): ?>
                <div class="glass-card mt-6">
                    <h3 class="card-title"><i class="fas fa-trophy"></i> Top Performing Referrals</h3>
                    <div class="top-performers-list">
                        <?php foreach ($stats['top_performers'] as $index => $performer): ?>
                        <div class="performer-item">
                            <div class="performer-rank">#<?= $index + 1 ?></div>
                            <div class="performer-name"><?= htmlspecialchars($performer['username']) ?></div>
                            <div class="performer-earnings">$<?= number_format($performer['total_commission'], 2) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <style>
        .grid-layout-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; }
        .grid-layout-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
        
        .stat-card { display: flex; align-items: center; gap: 1rem; padding: 1.5rem; }
        .stat-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .stat-label { font-size: 0.875rem; color: #94a3b8; margin-bottom: 0.25rem; }
        .stat-value { font-size: 2rem; font-weight: 700; color: #fff; }
        
        .earnings-display { display: flex; flex-direction: column; gap: 1.5rem; margin-top: 1rem; }
        .earnings-item { display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: rgba(0,0,0,0.2); border-radius: 0.75rem; }
        .earnings-label { color: #94a3b8; }
        .earnings-amount { font-size: 1.5rem; font-weight: 700; color: #fff; }
        .text-green { color: #10b981 !important; }
        
        .link-stats { display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem; }
        .link-stat-item { padding: 1rem; background: rgba(0,0,0,0.2); border-radius: 0.75rem; }
        .link-type { font-weight: 600; color: #fff; margin-bottom: 0.5rem; }
        .link-metrics { display: flex; gap: 1.5rem; font-size: 0.875rem; color: #94a3b8; }
        
        .referral-links-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-top: 1rem; }
        .ref-link-box label { display: block; font-size: 0.875rem; color: #94a3b8; margin-bottom: 0.5rem; }
        .link-input-group { display: flex; gap: 0.5rem; }
        .link-input-group input { flex: 1; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 0.75rem; border-radius: 0.5rem; font-size: 0.875rem; }
        .btn-copy { background: rgba(99, 102, 241, 0.2); border: 1px solid rgba(99, 102, 241, 0.5); color: #6366f1; padding: 0.75rem 1rem; border-radius: 0.5rem; cursor: pointer; transition: all 0.3s; }
        .btn-copy:hover { background: rgba(99, 102, 241, 0.3); }
        
        .ref-code-display { margin-top: 1.5rem; padding: 1rem; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 0.75rem; text-align: center; }
        .ref-code-display code { font-size: 1.25rem; font-weight: 700; color: #10b981; padding: 0.5rem 1rem; background: rgba(0,0,0,0.2); border-radius: 0.5rem; }
        
        .top-performers-list { display: flex; flex-direction: column; gap: 0.75rem; margin-top: 1rem; }
        .performer-item { display: flex; align-items: center; gap: 1rem; padding: 1rem; background: rgba(0,0,0,0.2); border-radius: 0.75rem; }
        .performer-rank { width: 40px; height: 40px; background: rgba(251, 191, 36, 0.2); color: #fbbf24; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; }
        .performer-name { flex: 1; color: #fff; font-weight: 600; }
        .performer-earnings { color: #10b981; font-weight: 700; font-size: 1.125rem; }
        
        @media (max-width: 1024px) {
            .grid-layout-4, .grid-layout-2, .referral-links-grid { grid-template-columns: 1fr; }
        }
    </style>
    
    <script>
        function copyLink(elementId) {
            const input = document.getElementById(elementId);
            input.select();
            input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value).then(() => {
                showToast('Link copied to clipboard!');
            });
        }
        
        function showToast(message) {
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
            toast.style.cssText = 'position: fixed; bottom: 2rem; right: 2rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 1rem 1.5rem; border-radius: 0.75rem; box-shadow: 0 10px 40px rgba(16, 185, 129, 0.4); display: flex; align-items: center; gap: 0.75rem; z-index: 9999; font-weight: 600;';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
    </script>
</body>
</html>
