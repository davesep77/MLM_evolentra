<?php
require 'config_db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get date range from request or default to current month
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Fetch user info
$user_query = $conn->query("SELECT username, email, investment FROM mlm_users WHERE id = $user_id");
$user = $user_query->fetch_assoc();

// Fetch wallet balances
$wallet_query = $conn->query("SELECT * FROM mlm_wallets WHERE user_id = $user_id");
$wallet = $wallet_query->fetch_assoc();

// Get all transactions in date range
$transactions_query = $conn->query("
    SELECT * FROM mlm_transactions 
    WHERE user_id = $user_id 
    AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'
    ORDER BY created_at DESC
");

// Calculate income by type
$roi_income = 0;
$referral_income = 0;
$binary_income = 0;
$total_deposits = 0;
$total_withdrawals = 0;

$income_by_type_query = $conn->query("
    SELECT 
        type,
        SUM(amount) as total
    FROM mlm_transactions
    WHERE user_id = $user_id 
    AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'
    GROUP BY type
");

while ($row = $income_by_type_query->fetch_assoc()) {
    switch ($row['type']) {
        case 'ROI':
            $roi_income = $row['total'];
            break;
        case 'REFERRAL':
            $referral_income = $row['total'];
            break;
        case 'BINARY':
            $binary_income = $row['total'];
            break;
        case 'DEPOSIT':
            $total_deposits = $row['total'];
            break;
        case 'WITHDRAWAL':
            $total_withdrawals = $row['total'];
            break;
    }
}

$total_income = $roi_income + $referral_income + $binary_income;

// Get daily income data for chart
$daily_income_query = $conn->query("
    SELECT 
        DATE(created_at) as date,
        SUM(CASE WHEN type = 'ROI' THEN amount ELSE 0 END) as roi,
        SUM(CASE WHEN type = 'REFERRAL' THEN amount ELSE 0 END) as referral,
        SUM(CASE WHEN type = 'BINARY' THEN amount ELSE 0 END) as `binary`
    FROM mlm_transactions
    WHERE user_id = $user_id 
    AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'
    AND type IN ('ROI', 'REFERRAL', 'BINARY')
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");

$chart_data = [];
while ($row = $daily_income_query->fetch_assoc()) {
    $chart_data[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Income Report - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
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

        .btn-export {
            padding: 0.875rem 1.5rem;
            background: linear-gradient(135deg, #a78bfa 0%, #ec4899 100%);
            border: none;
            border-radius: 0.75rem;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(167, 139, 250, 0.5);
        }

        .date-filter {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(20px);
        }

        .date-filter form {
            display: flex;
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            flex: 1;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 0.5rem;
            color: white;
            font-size: 0.95rem;
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
            font-size: 1.75rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .chart-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
            padding: 2rem;
            backdrop-filter: blur(20px);
            margin-bottom: 2rem;
        }

        .chart-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .transactions-table {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
            padding: 2rem;
            backdrop-filter: blur(20px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            padding: 1rem;
            text-align: left;
            font-size: 0.875rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.7);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .badge {
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-roi { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }
        .badge-referral { background: rgba(16, 185, 129, 0.2); color: #86efac; }
        .badge-binary { background: rgba(236, 72, 153, 0.2); color: #f9a8d4; }
        .badge-deposit { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
        .badge-withdrawal { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }

        @media (max-width: 1024px) {
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
        }

        @media print {
            body {
                background: white !important;
                color: black !important;
            }
            .main-wrapper {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            .btn-export, .date-filter, #sidebar {
                display: none !important;
            }
            .report-printable {
                background: white !important;
                color: black !important;
                padding: 2rem !important;
                border-radius: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }
            .stat-card, .chart-card, .transactions-table {
                background: #f8fafc !important;
                border: 1px solid #e2e8f0 !important;
                color: #1e293b !important;
                box-shadow: none !important;
            }
            .stat-value, .chart-title, th, td {
                color: #1e293b !important;
            }
            .badge {
                border: 1px solid #cbd5e1 !important;
                color: #1e293b !important;
                background: transparent !important;
            }
        }

        /* PDF Specific Styles */
        .pdf-header {
            display: none;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #a78bfa;
            padding-bottom: 2rem;
            margin-bottom: 3rem;
        }
        .pdf-mode .pdf-header {
            display: flex;
        }
        .pdf-mode .report-printable {
            background: white !important;
            color: #1e293b !important;
            padding: 40px !important;
            width: 794px !important; /* A4 width at 96 DPI */
            margin: 0 auto !important;
        }
        .pdf-mode .stat-card, .pdf-mode .chart-card, .pdf-mode .transactions-table {
            background: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            color: #1e293b !important;
        }
        .pdf-mode .stat-value, .pdf-mode .chart-title, .pdf-mode th, .pdf-mode td {
            color: #1e293b !important;
        }
    </style>
</head>
<body>
    <?php include 'sidebar_nav.php'; ?>
    
    <div class="main-wrapper">
        <div class="page-header">
            <h1 class="page-title">Income Report</h1>
            <button class="btn-export" onclick="exportPDF()">
                <i class="fas fa-file-pdf"></i> Export PDF
            </button>
        </div>

        <div class="date-filter">
            <form method="GET" action="">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" class="form-input" value="<?= $start_date ?>" required>
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" class="form-input" value="<?= $end_date ?>" required>
                </div>
                <button type="submit" class="btn-export">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </form>
        </div>

        <div id="reportContent" class="report-printable">
            <!-- PDF Header (Hidden in Web View) -->
            <div class="pdf-header">
                <div>
                    <h2 style="font-size: 2.5rem; font-weight: 800; color: #a78bfa; margin-bottom: 0.5rem;">EVOLENTRA</h2>
                    <p style="color: #64748b; font-weight: 600;">FINANCIAL ECOSYSTEM STATEMENT</p>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: 700; color: #1e293b;"><?= htmlspecialchars($user['username']) ?></div>
                    <div style="font-size: 0.875rem; color: #64748b;"><?= htmlspecialchars($user['email']) ?></div>
                    <div style="margin-top: 1rem; font-size: 0.75rem; color: #94a3b8; text-transform: uppercase;">Period</div>
                    <div style="font-weight: 600; font-size: 0.875rem; color: #1e293b;">
                        <?= date('M d, Y', strtotime($start_date)) ?> - <?= date('M d, Y', strtotime($end_date)) ?>
                    </div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                    <div class="stat-value">$<?= number_format($total_income, 2) ?></div>
                    <div class="stat-label">Total Earnings</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);"><i class="fas fa-arrow-down"></i></div>
                    <div class="stat-value">$<?= number_format($total_deposits, 2) ?></div>
                    <div class="stat-label">Total Deposits</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #34d399 100%);"><i class="fas fa-arrow-up"></i></div>
                    <div class="stat-value">$<?= number_format($total_withdrawals, 2) ?></div>
                    <div class="stat-label">Total Withdrawals</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #ec4899 0%, #f472b6 100%);"><i class="fas fa-piggy-bank"></i></div>
                    <div class="stat-value">$<?= number_format($user['investment'], 2) ?></div>
                    <div class="stat-label">Active Capital</div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                <div class="stat-card" style="padding: 1rem;">
                    <div style="font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; margin-bottom: 0.5rem;">ROI Income</div>
                    <div style="font-size: 1.25rem; font-weight: 700;">$<?= number_format($roi_income, 2) ?></div>
                </div>
                <div class="stat-card" style="padding: 1rem;">
                    <div style="font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; margin-bottom: 0.5rem;">Referral Income</div>
                    <div style="font-size: 1.25rem; font-weight: 700;">$<?= number_format($referral_income, 2) ?></div>
                </div>
                <div class="stat-card" style="padding: 1rem;">
                    <div style="font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; margin-bottom: 0.5rem;">Binary Income</div>
                    <div style="font-size: 1.25rem; font-weight: 700;">$<?= number_format($binary_income, 2) ?></div>
                </div>
            </div>

            <div class="chart-card">
                <h3 class="chart-title">Income Trend Tracking</h3>
                <canvas id="incomeChart" height="100"></canvas>
            </div>

            <div class="transactions-table" style="margin-bottom: 2rem;">
                <h3 class="chart-title">Earnings Category Overview</h3>
                <table>
                    <thead style="background: rgba(167, 139, 250, 0.1);">
                        <tr>
                            <th>Category</th>
                            <th>Count</th>
                            <th style="text-align: right;">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>ROI (Daily Returns)</td>
                            <td>-</td>
                            <td style="text-align: right; font-weight: 700;">$<?= number_format($roi_income, 2) ?></td>
                        </tr>
                        <tr>
                            <td>Referral Commissions</td>
                            <td>-</td>
                            <td style="text-align: right; font-weight: 700;">$<?= number_format($referral_income, 2) ?></td>
                        </tr>
                        <tr>
                            <td>Binary Matching Bonuses</td>
                            <td>-</td>
                            <td style="text-align: right; font-weight: 700;">$<?= number_format($binary_income, 2) ?></td>
                        </tr>
                        <tr style="border-top: 2px solid rgba(167, 139, 250, 0.3);">
                            <td colspan="2" style="font-weight: 800; color: #a78bfa;">NET PERIOD EARNINGS</td>
                            <td style="text-align: right; font-weight: 800; color: #a78bfa;">$<?= number_format($total_income, 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="transactions-table">
                <h3 class="chart-title">Transaction History</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $transactions_query->data_seek(0);
                        while ($trx = $transactions_query->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?= date('M d, Y H:i', strtotime($trx['created_at'])) ?></td>
                            <td>
                                <span class="badge badge-<?= strtolower($trx['type']) ?>">
                                    <?= $trx['type'] ?>
                                </span>
                            </td>
                            <td style="font-weight: 600; color: #a78bfa;">
                                $<?= number_format($trx['amount'], 2) ?>
                            </td>
                            <td><?= htmlspecialchars($trx['description'] ?? '-') ?></td>
                            <td><?= $trx['status'] ?? 'completed' ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 3rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1); text-align: center; font-size: 0.75rem; color: #64748b;">
                <p>This is a system-generated statement and does not require a physical signature.</p>
                <p style="margin-top: 0.5rem;">EVOLENTRA FINANCIAL ECOSYSTEM | 2025 COMPLIANT</p>
                <p style="margin-top: 0.25rem;">Generated on <?= date('M d, Y H:i:s') ?> UTC</p>
            </div>
        </div>
    </div>

    <script>
        // Chart Data
        const chartData = <?= json_encode($chart_data) ?>;
        
        const labels = chartData.map(d => d.date);
        const roiData = chartData.map(d => parseFloat(d.roi));
        const referralData = chartData.map(d => parseFloat(d.referral));
        const binaryData = chartData.map(d => parseFloat(d.binary));

        // Create Chart
        const ctx = document.getElementById('incomeChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'ROI Income',
                        data: roiData,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Referral Income',
                        data: referralData,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Binary Income',
                        data: binaryData,
                        borderColor: '#ec4899',
                        backgroundColor: 'rgba(236, 72, 153, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        labels: {
                            color: 'white'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: 'rgba(255,255,255,0.7)',
                            callback: function(value) {
                                return '$' + value;
                            }
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: 'rgba(255,255,255,0.7)'
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.1)'
                        }
                    }
                }
            }
        });

        // PDF Export Function
        function exportPDF() {
            const element = document.getElementById('reportContent');
            
            // Apply PDF mode class for styling
            element.classList.add('pdf-mode');
            
            const opt = {
                margin: 0.5,
                filename: 'Evolentera_Statement_<?= htmlspecialchars($user['username']) ?>_<?= date("Y-m-d") ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { 
                    scale: 2,
                    useCORS: true,
                    letterRendering: true,
                    width: 794 // Match A4 pixel width
                },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
            };

            // Use a promise to ensure styles are applied then removed
            html2pdf().set(opt).from(element).save().then(() => {
                element.classList.remove('pdf-mode');
            });
        }
    </script>
</body>
</html>
