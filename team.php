<?php
require 'config_db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get user's direct referrals
$direct_query = $conn->query("
    SELECT id, username, email, investment, binary_position, created_at 
    FROM mlm_users 
    WHERE sponsor_id = $user_id 
    ORDER BY created_at DESC
");

// Count total team members (all levels)
function countTotalTeam($conn, $user_id, &$visited = []) {
    if (in_array($user_id, $visited)) return 0;
    $visited[] = $user_id;
    
    $count = 0;
    $children = $conn->query("SELECT id FROM mlm_users WHERE sponsor_id = $user_id");
    
    while ($child = $children->fetch_assoc()) {
        $count++;
        $count += countTotalTeam($conn, $child['id'], $visited);
    }
    
    return $count;
}

$total_team = countTotalTeam($conn, $user_id);
$direct_count = $direct_query->num_rows;

// Calculate team statistics
$team_investment_query = $conn->query("
    SELECT SUM(u.investment) as total_investment
    FROM mlm_users u
    WHERE u.sponsor_id = $user_id
");
$team_investment = $team_investment_query->fetch_assoc()['total_investment'] ?? 0;

// Get left and right leg counts
$left_leg_query = $conn->query("SELECT COUNT(*) as count FROM mlm_users WHERE sponsor_id = $user_id AND binary_position = 'L'");
$left_count = $left_leg_query->fetch_assoc()['count'];

$right_leg_query = $conn->query("SELECT COUNT(*) as count FROM mlm_users WHERE sponsor_id = $user_id AND binary_position = 'R'");
$right_count = $right_leg_query->fetch_assoc()['count'];

// Get wallet data for team volume
$wallet_query = $conn->query("SELECT left_vol, right_vol FROM mlm_wallets WHERE user_id = $user_id");
$wallet = $wallet_query->fetch_assoc();

// Get active members (those with investment)
$active_query = $conn->query("SELECT COUNT(*) as count FROM mlm_users WHERE sponsor_id = $user_id AND investment > 0");
$active_count = $active_query->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Team - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4c1d95 100%);
            min-height: 100vh;
            color: white;
            margin: 0;
            padding: 0;
        }

        .main-wrapper {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #a78bfa 0%, #ec4899 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.25rem;
            padding: 1.5rem;
            backdrop-filter: blur(20px);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 50%, rgba(167, 139, 250, 0.15) 0%, transparent 50%);
            pointer-events: none;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 1rem;
            background: linear-gradient(135deg, #a78bfa 0%, #ec4899 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .team-table-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
            padding: 2rem;
            backdrop-filter: blur(20px);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .table-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 0.75rem;
            color: white;
            font-size: 0.9rem;
            width: 300px;
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.5);
        }

        .team-table {
            width: 100%;
            border-collapse: collapse;
        }

        .team-table thead tr {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .team-table th {
            padding: 1rem;
            text-align: left;
            font-size: 0.875rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.7);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .team-table td {
            padding: 1.25rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .team-table tbody tr {
            transition: all 0.3s ease;
        }

        .team-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .member-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .member-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #a78bfa 0%, #ec4899 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
        }

        .member-details {
            flex: 1;
        }

        .member-name {
            font-weight: 600;
            color: white;
            margin-bottom: 0.25rem;
        }

        .member-email {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.5);
        }

        .badge {
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-left {
            background: rgba(59, 130, 246, 0.2);
            color: #93c5fd;
        }

        .badge-right {
            background: rgba(236, 72, 153, 0.2);
            color: #f9a8d4;
        }

        .badge-active {
            background: rgba(16, 185, 129, 0.2);
            color: #86efac;
        }

        .badge-inactive {
            background: rgba(107, 114, 128, 0.2);
            color: #9ca3af;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: rgba(255, 255, 255, 0.5);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .main-wrapper {
                margin-left: 0;
                padding: 1rem;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .search-box input {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar_nav.php'; ?>
    
    <div class="main-wrapper">
        <div class="page-header">
            <h1 class="page-title">My Team</h1>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?= $total_team ?></div>
                <div class="stat-label">Total Team</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="stat-value"><?= $direct_count ?></div>
                <div class="stat-label">Direct Referrals</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?= $active_count ?></div>
                <div class="stat-label">Active Members</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-value">$<?= number_format($team_investment, 0) ?></div>
                <div class="stat-label">Team Investment</div>
            </div>
        </div>

        <!-- Binary Leg Stats -->
        <div class="stats-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 2rem;">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);">
                    <i class="fas fa-arrow-left"></i>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                    <div>
                        <div class="stat-value"><?= $left_count ?></div>
                        <div class="stat-label">Left Leg Members</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 1.25rem; font-weight: 700; color: #93c5fd;">
                            $<?= number_format($wallet['left_vol'], 0) ?>
                        </div>
                        <div style="font-size: 0.75rem; color: rgba(255,255,255,0.5);">Volume</div>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #ec4899 0%, #f472b6 100%);">
                    <i class="fas fa-arrow-right"></i>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                    <div>
                        <div class="stat-value"><?= $right_count ?></div>
                        <div class="stat-label">Right Leg Members</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 1.25rem; font-weight: 700; color: #f9a8d4;">
                            $<?= number_format($wallet['right_vol'], 0) ?>
                        </div>
                        <div style="font-size: 0.75rem; color: rgba(255,255,255,0.5);">Volume</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Members Table -->
        <div class="team-table-card">
            <div class="table-header">
                <h3 class="table-title">Direct Referrals (<?= $direct_count ?>)</h3>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search members..." onkeyup="searchTable()">
                </div>
            </div>

            <?php if ($direct_count > 0): ?>
            <table class="team-table" id="teamTable">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Position</th>
                        <th>Investment</th>
                        <th>Status</th>
                        <th>Joined Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($member = $direct_query->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="member-info">
                                <div class="member-avatar">
                                    <?= strtoupper(substr($member['username'], 0, 1)) ?>
                                </div>
                                <div class="member-details">
                                    <div class="member-name"><?= htmlspecialchars($member['username']) ?></div>
                                    <div class="member-email"><?= htmlspecialchars($member['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($member['binary_position'] == 'L'): ?>
                                <span class="badge badge-left"><i class="fas fa-arrow-left"></i> Left</span>
                            <?php elseif ($member['binary_position'] == 'R'): ?>
                                <span class="badge badge-right"><i class="fas fa-arrow-right"></i> Right</span>
                            <?php else: ?>
                                <span class="badge" style="background: rgba(107,114,128,0.2); color: #9ca3af;">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: 600; color: #a78bfa;">
                            $<?= number_format($member['investment'], 2) ?>
                        </td>
                        <td>
                            <?php if ($member['investment'] > 0): ?>
                                <span class="badge badge-active"><i class="fas fa-check"></i> Active</span>
                            <?php else: ?>
                                <span class="badge badge-inactive"><i class="fas fa-times"></i> Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td style="color: rgba(255,255,255,0.7);">
                            <?= date('M d, Y', strtotime($member['created_at'])) ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-users-slash"></i>
                <h3 style="margin-bottom: 0.5rem;">No Team Members Yet</h3>
                <p>Start building your team by sharing your referral link!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function searchTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('teamTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td')[0];
                if (td) {
                    const txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }
    </script>
</body>
</html>
