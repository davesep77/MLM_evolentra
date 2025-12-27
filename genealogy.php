<?php
require 'config_db.php';
// Security check
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$current_user_id = $_SESSION['user_id'];
$root_id = isset($_GET['root_id']) ? intval($_GET['root_id']) : $current_user_id;

// Fetch User Data Function with complete info
function getUserNode($conn, $id) {
    if (!$id) return null;
    $sql = "SELECT u.id, u.username, u.investment, u.created_at, u.current_rank,
                   w.left_vol, w.right_vol
            FROM mlm_users u 
            LEFT JOIN mlm_wallets w ON u.id = w.user_id 
            WHERE u.id=$id";
    $q = $conn->query($sql);
    $user = $q->fetch_assoc();
    
    // Set rank (use current_rank if exists, otherwise calculate)
    if ($user) {
        if (!empty($user['current_rank'])) {
            $user['rank'] = $user['current_rank'];
        } elseif ($user['investment'] >= 25001) {
            $user['rank'] = 'TERRA';
        } elseif ($user['investment'] >= 5001) {
            $user['rank'] = 'RISE';
        } elseif ($user['investment'] >= 500) {
            $user['rank'] = 'ROOT';
        } else {
            $user['rank'] = 'Member';
        }
    }
    
    return $user;
}

// Count direct referrals
function countDirectReferrals($conn, $user_id) {
    $result = $conn->query("SELECT COUNT(*) as count FROM mlm_users WHERE sponsor_id=$user_id");
    return $result->fetch_assoc()['count'];
}

// Recursive Tree Builder (5 levels deep)
function buildTree($conn, $rootId, $depth = 0, $maxDepth = 4) {
    $node = getUserNode($conn, $rootId);
    if (!$node) return null;

    $tree = [
        'user' => $node,
        'left' => null,
        'right' => null,
        'direct_count' => countDirectReferrals($conn, $rootId)
    ];

    if ($depth < $maxDepth) {
        // Find Left Child
        $qL = $conn->query("SELECT id FROM mlm_users WHERE sponsor_id=$rootId AND binary_position='L' LIMIT 1");
        if ($qL->num_rows > 0) {
            $tree['left'] = buildTree($conn, $qL->fetch_assoc()['id'], $depth + 1, $maxDepth);
        }

        // Find Right Child
        $qR = $conn->query("SELECT id FROM mlm_users WHERE sponsor_id=$rootId AND binary_position='R' LIMIT 1");
        if ($qR->num_rows > 0) {
            $tree['right'] = buildTree($conn, $qR->fetch_assoc()['id'], $depth + 1, $maxDepth);
        }
    }
    return $tree;
}

$treeData = buildTree($conn, $root_id);
$currentUser = getUserNode($conn, $current_user_id);

function renderNode($node, $type = 'member', $isCurrentUser = false) {
    global $root_id;
    
    if (!$node) {
        echo '
        <div class="tree-node empty">
            <div class="avatar-wrapper">
                <div class="empty-slot">
                    <i class="fas fa-user-plus"></i>
                </div>
            </div>
            <div class="node-info glass-panel">
                <span class="status">OPEN SLOT</span>
                <span class="action">Waiting for referral</span>
            </div>
        </div>';
        return;
    }

    $img = 'fas fa-user'; 
    $bgColor = '#3b82f6';
    
    if ($type == 'root') {
        $img = 'fas fa-crown';
        $bgColor = '#f59e0b';
    }
    
    if ($isCurrentUser) {
        $bgColor = '#10b981';
    }

    $link = "?root_id=" . $node['user']['id'];
    $joinDate = date('M Y', strtotime($node['user']['created_at']));
    $rank = $node['user']['rank'] ?: 'Member';
    
    echo '
    <div class="tree-node occupied ' . ($isCurrentUser ? 'current-user' : '') . '" data-user-id="'.$node['user']['id'].'">
        <a href="'.$link.'" class="avatar-wrapper" style="background: '.$bgColor.'">
            <i class="'.$img.' avatar-icon"></i>
            <div class="glow-effect"></div>
            ' . ($type == 'root' ? '<div class="chief-badge">CHIEF</div>' : '') . '
            ' . ($isCurrentUser ? '<div class="you-badge">YOU</div>' : '') . '
        </a>
        <div class="node-info glass-panel">
            <div class="node-header">
                <span class="username">' . htmlspecialchars($node['user']['username']) . '</span>
                <span class="user-id">#' . $node['user']['id'] . '</span>
            </div>
            <div class="rank-badge">' . $rank . '</div>
            <div class="node-stats">
                <div class="stat-row">
                    <span class="stat-label">Investment:</span>
                    <span class="stat-value">$' . number_format($node['user']['investment'], 2) . '</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Team:</span>
                    <span class="stat-value">' . $node['direct_count'] . ' direct</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">L:</span>
                    <span class="stat-value">$' . number_format($node['user']['left_vol'] ?? 0) . '</span>
                    <span class="stat-label">R:</span>
                    <span class="stat-value">$' . number_format($node['user']['right_vol'] ?? 0) . '</span>
                </div>
                <div class="join-date">Joined ' . $joinDate . '</div>
            </div>
        </div>
    </div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Genealogy Tree - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --font-main: 'Outfit', sans-serif;
            --chief-color: #f59e0b;
            --you-color: #10b981;
            --member-color: #3b82f6;
        }
        
        body {
            overflow-x: hidden;
        }
        
        .main-content {
            width: 100%;
            overflow-x: hidden;
        }
        
        /* Tree Container */
        .tree-scroll-container {
            overflow-x: auto;
            overflow-y: visible;
            padding: 2rem;
            text-align: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 1rem;
            min-height: 800px;
            width: 100%;
        }

        .tree-container {
            display: inline-flex;
            justify-content: center;
            padding: 50px 20px;
            min-width: max-content;
        }
        
        .node-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            padding: 0 10px; /* Reduced padding */
            min-width: 130px;
        }

        .children-container {
            display: flex;
            justify-content: center;
            position: relative;
            margin-top: 80px;
            padding-top: 40px;
            gap: 15px; /* Reduced gap default */
        }
        
        /* Connecting Lines - Animated */
        .node-wrapper::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            width: 3px;
            height: 30px;
            background: linear-gradient(to bottom, var(--primary-accent), var(--secondary-accent));
            box-shadow: 0 0 10px var(--primary-accent);
            animation: pulse 2s infinite;
        }

        .children-container::after {
            content: '';
            position: absolute;
            top: 0;
            left: 20%;
            width: 60%;
            height: 3px;
            background: linear-gradient(to right, var(--primary-accent), var(--secondary-accent));
            box-shadow: 0 0 8px var(--secondary-accent);
            animation: glow 2s infinite;
        }

        .children-container .node-wrapper::before {
            content: '';
            position: absolute;
            top: -30px;
            left: 50%;
            width: 3px;
            height: 30px;
            background: var(--secondary-accent);
            box-shadow: 0 0 5px var(--secondary-accent);
        }

        /* Tree Node */
        .tree-node {
            text-align: center;
            position: relative;
            z-index: 5;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            width: 125px; /* Compact width */
            flex-shrink: 0;
        }

        .tree-node.occupied {
            cursor: pointer;
            animation: fadeIn 0.5s ease-in;
        }

        .tree-node:hover {
            transform: scale(1.15);
            z-index: 20;
        }

        .tree-node.current-user {
            animation: highlight 2s infinite;
        }

        /* Avatar */
        .avatar-wrapper {
            width: 70px;
            height: 70px;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            position: relative;
            border: 3px solid rgba(255,255,255,0.2);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            transition: all 0.3s;
        }

        .avatar-icon {
            font-size: 1.8rem;
            color: white;
        }

        .tree-node:hover .avatar-wrapper {
            transform: rotate(360deg);
            box-shadow: 0 15px 40px rgba(0,0,0,0.5);
        }

        .glow-effect {
            position: absolute;
            inset: -10px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.3), transparent);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .tree-node:hover .glow-effect {
            opacity: 1;
        }

        /* Badges */
        .chief-badge, .you-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--chief-color);
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.6rem;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            animation: bounce 2s infinite;
        }

        .you-badge {
            background: var(--you-color);
        }

        /* Empty Slot */
        .empty-slot {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 2px dashed rgba(255,255,255,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.05);
        }

        .empty-slot i {
            font-size: 1.2rem;
            color: rgba(255,255,255,0.3);
        }

        /* Node Info Panel */
        .glass-panel {
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 8px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            transition: all 0.3s;
            font-size: 0.75rem;
            width: 100%;
        }

        .tree-node:hover .glass-panel {
            background: rgba(15, 23, 42, 0.95);
            border-color: var(--primary-accent);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.5);
            transform: translateY(-5px);
            z-index: 50;
            position: relative;
        }

        .node-header {
            margin-bottom: 5px;
        }

        .username {
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-id {
            display: block;
            font-size: 0.7rem;
            color: var(--primary-accent);
            margin-top: 1px;
        }

        .rank-badge {
            background: linear-gradient(135deg, var(--primary-accent), var(--secondary-accent));
            color: white;
            padding: 2px 8px;
            border-radius: 15px;
            font-size: 0.6rem;
            font-weight: 600;
            margin: 5px 0;
            display: inline-block;
        }

        .node-stats {
            display: flex;
            flex-direction: column;
            gap: 2px;
            font-size: 0.7rem;
            color: #94a3b8;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            gap: 5px;
        }

        .stat-label {
            color: #64748b;
        }

        .stat-value {
            color: #e2e8f0;
            font-weight: 600;
        }

        .join-date {
            font-size: 0.6rem;
            color: #64748b;
            margin-top: 4px;
            font-style: italic;
        }

        .empty .status {
            color: #64748b;
            font-weight: 600;
            font-size: 0.75rem;
            display: block;
            margin: 5px 0;
        }
        
        .empty .action {
            color: var(--secondary-accent);
            font-size: 0.65rem;
            display: block;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        @keyframes glow {
            0%, 100% { box-shadow: 0 0 8px var(--secondary-accent); }
            50% { box-shadow: 0 0 20px var(--primary-accent); }
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        @keyframes highlight {
            0%, 100% { box-shadow: 0 0 20px var(--you-color); }
            50% { box-shadow: 0 0 40px var(--you-color); }
        }

        /* Stats Panel */
        .tree-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
        }

        .stat-card-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-accent);
            margin-bottom: 0.5rem;
        }

        .stat-card-label {
            font-size: 0.9rem;
            color: #94a3b8;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .tree-node {
                width: 90px;
            }
            .avatar-wrapper {
                width: 50px;
                height: 50px;
            }
            .avatar-icon {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar_nav.php'; ?>

        <div class="main-content">
            <div class="dashboard-section-full">
                <!-- Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <div>
                        <h2 class="gradient-text">
                            <i class="fas fa-sitemap"></i> Genealogy Tree
                        </h2>
                        <p style="color: var(--text-muted);">Your Binary Network Structure (5 Levels)</p>
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        <?php if ($root_id != $current_user_id): ?>
                            <a href="genealogy.php" class="btn btn-outline">
                                <i class="fas fa-arrow-up"></i> Back to Top
                            </a>
                        <?php endif; ?>
                        <button onclick="location.reload()" class="btn btn-primary">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>

                <!-- Stats -->
                <div class="tree-stats">
                    <div class="stat-card">
                        <div class="stat-card-value"><?= $currentUser['rank'] ?: 'Member' ?></div>
                        <div class="stat-card-label">Your Rank</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-value"><?= countDirectReferrals($conn, $current_user_id) ?></div>
                        <div class="stat-card-label">Direct Referrals</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-value">$<?= number_format($currentUser['left_vol'] ?? 0) ?></div>
                        <div class="stat-card-label">Left Team Volume</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-value">$<?= number_format($currentUser['right_vol'] ?? 0) ?></div>
                        <div class="stat-card-label">Right Team Volume</div>
                    </div>
                </div>

                <!-- Tree -->
                <div class="glass-card">
                    <div class="tree-scroll-container">
                        <div class="tree-container">
                            <?php
                            function renderTreeLevel($node, $level = 0, $current_user_id = null) {
                                if ($level > 4) return;

                                echo '<div class="node-wrapper">';
                                
                                $isCurrentUser = ($node && $node['user']['id'] == $current_user_id);
                                
                                if ($level == 0) renderNode($node, 'root', $isCurrentUser);
                                else renderNode($node, 'member', $isCurrentUser);

                                if ($level < 4) {
                                    echo '<div class="children-container">';
                                    
                                    if (isset($node['left'])) renderTreeLevel($node['left'], $level + 1, $current_user_id);
                                    else renderTreeLevel(null, $level + 1, $current_user_id);

                                    if (isset($node['right'])) renderTreeLevel($node['right'], $level + 1, $current_user_id);
                                    else renderTreeLevel(null, $level + 1, $current_user_id);

                                    echo '</div>';
                                }
                                
                                echo '</div>';
                            }

                            renderTreeLevel($treeData, 0, $current_user_id);
                            ?>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 2rem; color: var(--text-muted); font-size: 0.9rem;">
                    <p><i class="fas fa-info-circle"></i> Click on any member to view their downline tree</p>
                    <p><i class="fas fa-crown" style="color: var(--chief-color);"></i> Chief = Top of current view | 
                       <i class="fas fa-user" style="color: var(--you-color);"></i> Green = You</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-scroll to center
        const container = document.querySelector('.tree-scroll-container');
        const tree = document.querySelector('.tree-container');
        if (container && tree) {
            container.scrollLeft = (tree.offsetWidth - container.offsetWidth) / 2;
        }

        // Auto-refresh every 30 seconds to show new referrals
        setInterval(() => {
            location.reload();
        }, 30000);

        // Highlight new nodes (if added in last 24 hours)
        document.querySelectorAll('.tree-node.occupied').forEach(node => {
            // You can add logic here to highlight recently added members
        });
    </script>
</body>
</html>
