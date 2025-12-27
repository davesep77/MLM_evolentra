<?php
require 'config_db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// --- Helper Functions ---
function getKycStatusBadge($status) {
    switch ($status) {
        case 'verified': return '<span class="kyc-status-badge status-verified"><i class="fas fa-check-circle"></i> Verified</span>';
        case 'pending': return '<span class="kyc-status-badge status-pending"><i class="fas fa-clock"></i> Pending Review</span>';
        case 'rejected': return '<span class="kyc-status-badge status-rejected"><i class="fas fa-times-circle"></i> Rejected</span>';
        default: return '<span class="kyc-status-badge status-unverified"><i class="fas fa-shield-alt"></i> Unverified</span>';
    }
}

// 1. Fetch User & Wallet Data
$user = $conn->query("SELECT * FROM mlm_users WHERE id = $user_id")->fetch_assoc();
$wallet = $conn->query("SELECT * FROM mlm_wallets WHERE user_id = $user_id")->fetch_assoc();

// 2. Fetch KYC Data
$kyc_docs = $conn->query("SELECT * FROM mlm_kyc_documents WHERE user_id = $user_id ORDER BY uploaded_at DESC");
$kyc_status = $user['kyc_status'] ?? 'unverified';

// 3. Fetch User Settings (for payments)
$settings = $conn->query("SELECT * FROM mlm_user_settings WHERE user_id = $user_id")->fetch_assoc();
if (!$settings) {
    $conn->query("INSERT INTO mlm_user_settings (user_id) VALUES ($user_id)");
    $settings = $conn->query("SELECT * FROM mlm_user_settings WHERE user_id = $user_id")->fetch_assoc();
}

// --- HANDLE POST REQUESTS ---

// A. Update Profile Info
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $email = $conn->real_escape_string($_POST['email']);
    
    $check = $conn->query("SELECT id FROM mlm_users WHERE email='$email' AND id != $user_id");
    if ($check->num_rows > 0) {
        $error = "Email already in use by another account.";
    } else {
        $conn->query("UPDATE mlm_users SET email='$email' WHERE id=$user_id");
        $message = "Profile details updated successfully!";
        $user['email'] = $email;
    }
}

// A2. Update Binance Connection
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_binance'])) {
    if ($settings['binance_link_status'] === 'verified') {
        $error = "Cannot update verified address. Contact Support to change it.";
    } else {
        $bep20 = $conn->real_escape_string($_POST['usdt_address_bep20']);
        $trc20 = $conn->real_escape_string($_POST['usdt_address_trc20']);
        
        $conn->query("UPDATE mlm_user_settings SET usdt_address_bep20='$bep20', usdt_address_trc20='$trc20', binance_link_status='pending' WHERE user_id=$user_id");
        $message = "Binance addresses submitted for verification! Admin will review them shortly.";
        $settings['usdt_address_bep20'] = $bep20;
        $settings['usdt_address_trc20'] = $trc20;
        $settings['binance_link_status'] = 'pending';
    }
}

// B. Change Password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    
    if (password_verify($current_pass, $user['password'])) {
        if ($new_pass === $confirm_pass) {
            if (strlen($new_pass) >= 6) {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $conn->query("UPDATE mlm_users SET password='$hashed' WHERE id=$user_id");
                $message = "Security settings updated: Password changed successfully!";
            } else {
                $error = "New password must be at least 6 characters.";
            }
        } else {
            $error = "New passwords do not match.";
        }
    } else {
        $error = "Current password is incorrect.";
    }
}

// C. Toggle 2FA
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_2fa'])) {
    $enable = isset($_POST['two_factor_enable']) ? 1 : 0;
    // In a real app, generate secret here if enabling
    $conn->query("UPDATE mlm_users SET two_factor_enabled=$enable WHERE id=$user_id");
    $user['two_factor_enabled'] = $enable;
    $message = $enable ? "Two-Factor Authentication Enabled!" : "Two-Factor Authentication Disabled.";
}

// D. Upload KYC Documents
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_kyc'])) {
    $uploadDir = "uploads/kyc/";
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

    $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'];
    $docTypes = ['id_front', 'id_back', 'selfie'];
    $successCount = 0;

    foreach ($docTypes as $type) {
        if (isset($_FILES[$type]) && $_FILES[$type]['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES[$type]['tmp_name'];
            $fileName = $_FILES[$type]['name'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (in_array($fileExt, $allowedTypes)) {
                $newFileName = $user_id . '_' . $type . '_' . time() . '.' . $fileExt;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmp, $destPath)) {
                    $conn->query("INSERT INTO mlm_kyc_documents (user_id, document_type, file_path, status) VALUES ($user_id, '$type', '$destPath', 'pending')");
                    $successCount++;
                }
            }
        }
    }

    if ($successCount > 0) {
        $conn->query("UPDATE mlm_users SET kyc_status='pending' WHERE id=$user_id");
        $kyc_status = 'pending';
        $message = "Documents uploaded successfully. Verification is pending.";
    } else {
        $error = "No valid files uploaded. Please allowed formats (JPG, PNG, PDF).";
    }
}


// Calculate Data
$total_earnings = $wallet['roi_wallet'] + $wallet['referral_wallet'] + $wallet['binary_wallet'];
$sponsor_name = "None";
if ($user['sponsor_id']) {
    $s = $conn->query("SELECT username FROM mlm_users WHERE id={$user['sponsor_id']}");
    if ($s->num_rows > 0) $sponsor_name = $s->fetch_assoc()['username'];
}
$ref_count = $conn->query("SELECT COUNT(*) as count FROM mlm_users WHERE sponsor_id=$user_id")->fetch_assoc()['count'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Profile-specific overrides */
        .profile-container {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
        }

        .profile-sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px);
            border-radius: 1.5rem;
            padding: 1.5rem;
            position: sticky;
            top: 2rem;
        }

        .profile-user-mini {
            text-align: center;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 1rem;
        }

        .mini-avatar {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            background: linear-gradient(135deg, #a78bfa 0%, #ec4899 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            box-shadow: 0 0 20px rgba(167, 139, 250, 0.3);
        }

        .tab-btn {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: 1rem;
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.3s ease;
            background: transparent;
            border: none;
            width: 100%;
            text-align: left;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            margin-bottom: 0.5rem;
        }

        .tab-btn:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }

        .tab-btn.active {
            background: linear-gradient(90deg, rgba(167, 139, 250, 0.15) 0%, rgba(236, 72, 153, 0.15) 100%);
            color: #fff;
            border-left: 3px solid #ec4899;
        }

        .tab-btn i {
            width: 24px;
            text-align: center;
        }

        .profile-content-area {
            flex: 1;
            min-width: 0;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.4s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .profile-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
            padding: 2rem;
            backdrop-filter: blur(20px);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 0.75rem;
            color: white;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.12);
            border-color: #a78bfa;
        }

        .wallet-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .wallet-item {
            background: rgba(167, 139, 250, 0.15);
            padding: 1rem;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .wallet-icon {
            width: 45px;
            height: 45px;
            border-radius: 0.75rem;
            background: linear-gradient(135deg, #a78bfa 0%, #ec4899 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .wallet-label {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 0.25rem;
        }

        .wallet-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: white;
        }

        .total-earnings {
            background: linear-gradient(135deg, #a78bfa 0%, #ec4899 100%);
            padding: 1.5rem;
            border-radius: 1.25rem;
            text-align: center;
            box-shadow: 0 10px 40px rgba(167, 139, 250, 0.3);
        }

        .total-earnings-label {
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
            opacity: 0.9;
        }

        .total-earnings-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 28px;
        }

        .switch input { 
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255,255,255,0.1);
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #10b981;
        }

        input:checked + .slider:before {
            transform: translateX(22px);
        }

        .upload-zone {
            border: 2px dashed rgba(255, 255, 255, 0.2);
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            background: rgba(0,0,0,0.2);
            cursor: pointer;
            transition: all 0.3s;
        }

        .upload-zone:hover {
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.05);
        }

        .upload-icon {
            font-size: 2.5rem;
            color: #94a3b8;
            margin-bottom: 1rem;
        }

        .kyc-status-badge {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
        }

        .status-unverified { background: rgba(148, 163, 184, 0.2); color: #cbd5e1; }
        .status-pending { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .status-verified { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .status-rejected { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }

        @media (max-width: 992px) {
            .profile-container {
                flex-direction: column;
            }
            .profile-sidebar {
                width: 100%;
                position: static;
            }
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar_nav.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <h2 style="font-size: 1.75rem; font-weight: 700; margin-bottom: 2rem; color: #fff;">
                <i class="fas fa-user-cog" style="margin-right: 0.75rem; color: #a78bfa;"></i> Account Settings
            </h2>

            <?php if($message): ?>
                <div style="background: rgba(16, 185, 129, 0.2); color: #86efac; padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-check-circle"></i> <?= $message ?>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div style="background: rgba(239, 68, 68, 0.2); color: #fca5a5; padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <div class="profile-container">
                <!-- Sidebar Tabs -->
                <div class="profile-sidebar">
                    <div class="profile-user-mini">
                        <div class="mini-avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
                        <h3 style="margin-bottom: 0.25rem; font-size: 1.1rem;"><?= htmlspecialchars($user['username']) ?></h3>
                        <div style="font-size: 0.85rem; color: #94a3b8;"><?= htmlspecialchars($user['email']) ?></div>
                    </div>

                    <button class="tab-btn active" onclick="openTab(event, 'tab-overview')">
                        <i class="fas fa-chart-pie"></i> Overview
                    </button>
                    <button class="tab-btn" onclick="openTab(event, 'tab-binance')">
                        <i class="fa-brands fa-bitcoin"></i> Binance Connection
                    </button>
                    <button class="tab-btn" onclick="openTab(event, 'tab-security')">
                        <i class="fas fa-shield-alt"></i> Security Settings
                    </button>
                    <button class="tab-btn" onclick="openTab(event, 'tab-kyc')">
                        <i class="fas fa-id-card"></i> KYC Verification
                        <?php if($kyc_status == 'verified'): ?>
                            <i class="fas fa-check-circle" style="color: #34d399; margin-left: auto; width: auto;"></i>
                        <?php elseif($kyc_status == 'rejected'): ?>
                            <i class="fas fa-exclamation-circle" style="color: #fca5a5; margin-left: auto; width: auto;"></i>
                        <?php endif; ?>
                    </button>
                </div>

                <!-- Content Area -->
                <div class="profile-content-area">
                    
                    <!-- TAB 1: OVERVIEW -->
                    <div id="tab-overview" class="tab-content active">
                        <div class="profile-grid">
                            <!-- Account Info -->
                            <div class="profile-card">
                                <div class="card-header"><i class="fas fa-user-circle"></i> Profile Details</div>
                                <form method="POST" action="">
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e1; font-size: 0.9rem;">Username</label>
                                        <input type="text" class="form-input" value="<?= htmlspecialchars($user['username']) ?>" readonly style="opacity: 0.7; cursor: not-allowed;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e1; font-size: 0.9rem;">Email Address</label>
                                        <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($user['email']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e1; font-size: 0.9rem;">Sponsor</label>
                                        <input type="text" class="form-input" value="<?= htmlspecialchars($sponsor_name) ?>" readonly style="opacity: 0.7; cursor: not-allowed;">
                                    </div>
                                    <button type="submit" name="update_profile" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                                        <i class="fas fa-save" style="margin-right: 0.5rem;"></i> Save Changes
                                    </button>
                                </form>
                            </div>

                            <!-- Financial Snapshot -->
                            <div class="profile-card">
                                <div class="card-header"><i class="fas fa-wallet"></i> Financial Snapshot</div>
                                <div class="wallet-grid">
                                    <div class="wallet-item">
                                        <div class="wallet-icon"><i class="fas fa-dollar-sign"></i></div>
                                        <div class="wallet-info">
                                            <div class="wallet-label">Investment</div>
                                            <div class="wallet-value">$<?= number_format($user['investment'], 2) ?></div>
                                        </div>
                                    </div>
                                    <div class="wallet-item">
                                        <div class="wallet-icon"><i class="fas fa-users"></i></div>
                                        <div class="wallet-info">
                                            <div class="wallet-label">Referrals</div>
                                            <div class="wallet-value"><?= $ref_count ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="total-earnings">
                                    <div class="total-earnings-label">Total Earned</div>
                                    <div class="total-earnings-value">$<?= number_format($total_earnings, 2) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: BINANCE CONNECTION PLATFORM -->
                    <div id="tab-binance" class="tab-content">
                        <div class="profile-card">
                            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                                <span><i class="fa-brands fa-bitcoin"></i> Binance Account Connection Platform</span>
                                <?php
                                $status = $settings['binance_link_status'] ?? 'unlinked';
                                $badge_class = 'bg-slate-700';
                                if($status == 'verified') $badge_class = 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/50';
                                if($status == 'pending') $badge_class = 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/50';
                                if($status == 'rejected') $badge_class = 'bg-red-500/20 text-red-400 border border-red-500/50';
                                ?>
                                <span class="px-3 py-1 rounded text-xs uppercase font-bold <?= $badge_class ?>">
                                    <?= $status ?>
                                </span>
                            </div>

                            <?php if($status == 'rejected'): ?>
                                <div style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444; padding: 1rem; margin-bottom: 2rem; border-radius: 0.5rem; color: #fca5a5; font-size: 0.85rem;">
                                    <i class="fas fa-exclamation-circle"></i> <b>Rejection Note:</b> <?= htmlspecialchars($settings['binance_note']) ?>
                                </div>
                            <?php endif; ?>

                            <div class="wizard-steps" style="display: flex; gap: 1rem; margin-bottom: 2rem;">
                                <div style="flex: 1; padding: 1rem; background: rgba(255,255,255,0.03); border-radius: 0.75rem; border: 1px solid <?= $status != 'unlinked' ? '#f3ba2f' : 'rgba(255,255,255,0.1)' ?>;">
                                    <div style="font-weight: 800; font-size: 0.7rem; color: #f3ba2f; margin-bottom: 0.25rem;">STEP 1</div>
                                    <div style="font-size: 0.85rem; color: #fff;">Connect Address</div>
                                </div>
                                <div style="flex: 1; padding: 1rem; background: rgba(255,255,255,0.03); border-radius: 0.75rem; border: 1px solid <?= $status == 'verified' ? '#f3ba2f' : 'rgba(255,255,255,0.1)' ?>;">
                                    <div style="font-weight: 800; font-size: 0.7rem; color: #f3ba2f; margin-bottom: 0.25rem;">STEP 2</div>
                                    <div style="font-size: 0.85rem; color: #fff;">Admin Verification</div>
                                </div>
                                <div style="flex: 1; padding: 1rem; background: rgba(255,255,255,0.03); border-radius: 0.75rem; border: 1px solid <?= $status == 'verified' ? '#f3ba2f' : 'rgba(255,255,255,0.1)' ?>;">
                                    <div style="font-weight: 800; font-size: 0.7rem; color: #f3ba2f; margin-bottom: 0.25rem;">STEP 3</div>
                                    <div style="font-size: 0.85rem; color: #fff;">Withdraw Ready</div>
                                </div>
                            </div>
                            
                            <form method="POST" action="">
                                <div class="grid-layout-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                                    <div class="payment-method-box" style="background: rgba(243, 186, 47, 0.05); padding: 1.5rem; border-radius: 1rem; border: 1px solid rgba(243, 186, 47, 0.2); <?= $status == 'verified' ? 'opacity: 0.8;' : '' ?>">
                                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                                            <i class="fa-brands fa-bitcoin" style="font-size: 2rem; color: #f3ba2f;"></i>
                                            <h4 style="margin: 0; color: #f3ba2f;">USDT (BEP-20)</h4>
                                        </div>
                                        <div class="form-group">
                                            <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e1; font-size: 0.85rem;">Binance Smart Chain Address</label>
                                            <input type="text" name="usdt_address_bep20" class="form-input" placeholder="0x..." value="<?= htmlspecialchars($settings['usdt_address_bep20'] ?? '') ?>" <?= $status == 'verified' ? 'readonly' : 'required' ?>>
                                        </div>
                                    </div>

                                    <div class="payment-method-box" style="background: rgba(38, 161, 123, 0.05); padding: 1.5rem; border-radius: 1rem; border: 1px solid rgba(38, 161, 123, 0.2); <?= $status == 'verified' ? 'opacity: 0.8;' : '' ?>">
                                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                                            <i class="fa-solid fa-circle-t" style="font-size: 2rem; color: #26a17b;"></i>
                                            <h4 style="margin: 0; color: #26a17b;">USDT (TRC-20)</h4>
                                        </div>
                                        <div class="form-group">
                                            <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e1; font-size: 0.85rem;">TRON Network Address</label>
                                            <input type="text" name="usdt_address_trc20" class="form-input" placeholder="T..." value="<?= htmlspecialchars($settings['usdt_address_trc20'] ?? '') ?>" <?= $status == 'verified' ? 'readonly' : 'required' ?>>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if($status != 'verified'): ?>
                                    <div style="margin-top: 2rem; padding: 1rem; background: rgba(243, 186, 47, 0.1); border-radius: 0.75rem; border-left: 4px solid #f3ba2f; font-size: 0.85rem; color: #cbd5e1;">
                                        <i class="fas fa-info-circle"></i> <b>Action Required:</b> Submit your addresses for verification. Once verified, they will be locked for security.
                                    </div>

                                    <button type="submit" name="update_binance" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; background: #f3ba2f; color: #000; border: none; font-weight: 800;">
                                        <i class="fas fa-link" style="margin-right: 0.5rem;"></i> <?= $status == 'pending' ? 'Update & Re-Submit' : 'Link & Request Verification' ?>
                                    </button>
                                <?php else: ?>
                                    <div style="margin-top: 2rem; padding: 1rem; background: rgba(16, 185, 129, 0.1); border-radius: 0.75rem; border-left: 4px solid #10b981; font-size: 0.85rem; color: #cbd5e1;">
                                        <i class="fas fa-shield-check"></i> <b>Account Verified:</b> Your Binance addresses are locked. Contact Support to modify your payout destinations.
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <!-- TAB 2: SECURITY -->
                    <div id="tab-security" class="tab-content">
                        <div class="profile-card" style="margin-bottom: 1.5rem;">
                            <div class="card-header"><i class="fas fa-lock"></i> Password Management</div>
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e1; font-size: 0.9rem;">Current Password</label>
                                    <input type="password" name="current_password" class="form-input" placeholder="••••••••" required>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e1; font-size: 0.9rem;">New Password</label>
                                        <input type="password" name="new_password" class="form-input" placeholder="Min. 6 characters" required>
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 0.5rem; color: #cbd5e1; font-size: 0.9rem;">Confirm Password</label>
                                        <input type="password" name="confirm_password" class="form-input" placeholder="••••••••" required>
                                    </div>
                                </div>
                                <button type="submit" name="change_password" class="btn btn-primary" style="margin-top: 1rem;">
                                    <i class="fas fa-key" style="margin-right: 0.5rem;"></i> Update Password
                                </button>
                            </form>
                        </div>

                        <div class="profile-card">
                            <div class="card-header"><i class="fas fa-shield-virus"></i> Two-Factor Authentication</div>
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 2rem;">
                                <div style="flex: 1;">
                                    <p style="color: #cbd5e1; font-size: 0.95rem; margin-bottom: 0.5rem; font-weight: 600;">Secure your account with 2FA</p>
                                    <div style="font-size: 0.85rem; color: #94a3b8;">
                                        When enabled, you'll need to enter a verification code on login.
                                    </div>
                                </div>
                                <form method="POST" action="">
                                    <label class="switch">
                                        <input type="checkbox" name="two_factor_enable" onchange="this.form.submit()" <?= $user['two_factor_enabled'] ? 'checked' : '' ?>>
                                        <span class="slider"></span>
                                        <input type="hidden" name="toggle_2fa" value="1">
                                    </label>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: KYC -->
                    <div id="tab-kyc" class="tab-content">
                        <div class="profile-card">
                            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                                <span><i class="fas fa-id-card"></i> Identity Verification</span>
                                <?= getKycStatusBadge($kyc_status) ?>
                            </div>

                            <p style="color: #cbd5e1; margin-bottom: 2rem; line-height: 1.6;">
                                To comply with international financial regulations, please upload your identity documents. 
                                Your data is encrypted and stored securely.
                            </p>

                            <?php if($kyc_status == 'unverified' || $kyc_status == 'rejected'): ?>
                                <form method="POST" action="" enctype="multipart/form-data">
                                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
                                        <!-- Front ID -->
                                        <div class="upload-zone" onclick="document.getElementById('file-front').click()">
                                            <div class="upload-icon"><i class="fas fa-id-card"></i></div>
                                            <div style="font-weight: 600; margin-bottom: 0.5rem; color: #fff;">Front of ID</div>
                                            <div style="font-size: 0.8rem; color: #94a3b8;">JPG, PNG or PDF</div>
                                            <input type="file" name="id_front" id="file-front" hidden accept="image/*,.pdf">
                                            <div id="preview-front" style="margin-top: 0.5rem; font-size: 0.8rem; color: #10b981;"></div>
                                        </div>

                                        <!-- Back ID -->
                                        <div class="upload-zone" onclick="document.getElementById('file-back').click()">
                                            <div class="upload-icon"><i class="fas fa-id-card-alt"></i></div>
                                            <div style="font-weight: 600; margin-bottom: 0.5rem; color: #fff;">Back of ID</div>
                                            <div style="font-size: 0.8rem; color: #94a3b8;">JPG, PNG or PDF</div>
                                            <input type="file" name="id_back" id="file-back" hidden accept="image/*,.pdf">
                                            <div id="preview-back" style="margin-top: 0.5rem; font-size: 0.8rem; color: #10b981;"></div>
                                        </div>

                                        <!-- Selfie -->
                                        <div class="upload-zone" onclick="document.getElementById('file-selfie').click()">
                                            <div class="upload-icon"><i class="fas fa-camera"></i></div>
                                            <div style="font-weight: 600; margin-bottom: 0.5rem; color: #fff;">Selfie Photo</div>
                                            <div style="font-size: 0.8rem; color: #94a3b8;">Clear face photo</div>
                                            <input type="file" name="selfie" id="file-selfie" hidden accept="image/*">
                                            <div id="preview-selfie" style="margin-top: 0.5rem; font-size: 0.8rem; color: #10b981;"></div>
                                        </div>
                                    </div>

                                    <div style="text-align: right;">
                                        <button type="submit" name="upload_kyc" class="btn btn-primary">
                                            <i class="fas fa-upload" style="margin-right: 0.5rem;"></i> Submit Documents
                                        </button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <!-- VERIFIED STATUS -->
                                <?php if($kyc_status == 'verified'): ?>
                                    <div style="text-align: center; padding: 3rem; background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.05) 100%); border-radius: 1rem; border: 2px solid rgba(16, 185, 129, 0.3);">
                                        <div style="width: 80px; height: 80px; margin: 0 auto 1.5rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);">
                                            <i class="fas fa-check" style="font-size: 2.5rem; color: white;"></i>
                                        </div>
                                        <h3 style="margin-bottom: 0.75rem; font-size: 1.75rem; color: #34d399;">✓ Verified Account</h3>
                                        <p style="color: #86efac; line-height: 1.6; font-size: 1.05rem; margin-bottom: 1.5rem;">
                                            Your identity has been successfully verified. You now have full access to all platform features.
                                        </p>
                                        <div style="display: inline-flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.5rem; background: rgba(16, 185, 129, 0.15); border-radius: 2rem; border: 1px solid rgba(16, 185, 129, 0.3);">
                                            <i class="fas fa-shield-check" style="color: #34d399;"></i>
                                            <span style="color: #86efac; font-weight: 600;">Verified on <?= date('M d, Y', strtotime($user['created_at'])) ?></span>
                                        </div>
                                    </div>
                                
                                <!-- PENDING STATUS -->
                                <?php elseif($kyc_status == 'pending'): ?>
                                    <div style="text-align: center; padding: 3rem; background: linear-gradient(135deg, rgba(251, 191, 36, 0.1) 0%, rgba(245, 158, 11, 0.05) 100%); border-radius: 1rem; border: 2px solid rgba(251, 191, 36, 0.3);">
                                        <div style="width: 80px; height: 80px; margin: 0 auto 1.5rem; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 30px rgba(251, 191, 36, 0.3);">
                                            <i class="fas fa-hourglass-half" style="font-size: 2.5rem; color: white;"></i>
                                        </div>
                                        <h3 style="margin-bottom: 0.75rem; font-size: 1.75rem; color: #fbbf24;">Verification in Progress</h3>
                                        <p style="color: #fcd34d; line-height: 1.6; font-size: 1.05rem; margin-bottom: 1.5rem;">
                                            Your documents have been submitted and are currently under review by our compliance team. This usually takes 24-48 hours.
                                        </p>
                                        <div style="display: inline-flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.5rem; background: rgba(251, 191, 36, 0.15); border-radius: 2rem; border: 1px solid rgba(251, 191, 36, 0.3);">
                                            <i class="fas fa-clock" style="color: #fbbf24;"></i>
                                            <span style="color: #fcd34d; font-weight: 600;">Submitted on <?= date('M d, Y', strtotime($user['created_at'])) ?></span>
                                        </div>
                                    </div>
                                
                                <!-- REJECTED STATUS -->
                                <?php elseif($kyc_status == 'rejected'): ?>
                                    <div style="text-align: center; padding: 3rem; background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.05) 100%); border-radius: 1rem; border: 2px solid rgba(239, 68, 68, 0.3);">
                                        <div style="width: 80px; height: 80px; margin: 0 auto 1.5rem; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 30px rgba(239, 68, 68, 0.3);">
                                            <i class="fas fa-times" style="font-size: 2.5rem; color: white;"></i>
                                        </div>
                                        <h3 style="margin-bottom: 0.75rem; font-size: 1.75rem; color: #fca5a5;">Verification Rejected</h3>
                                        <p style="color: #fca5a5; line-height: 1.6; font-size: 1.05rem; margin-bottom: 1.5rem;">
                                            Unfortunately, your documents could not be verified. Please review the requirements and submit again with valid documents.
                                        </p>
                                        <a href="?resubmit=1" style="display: inline-flex; align-items: center; gap: 0.75rem; padding: 0.875rem 1.75rem; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; text-decoration: none; border-radius: 0.75rem; font-weight: 700; transition: all 0.3s;">
                                            <i class="fas fa-redo"></i>
                                            Resubmit Documents
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            
            // Hide all tab content
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }
            
            // Remove active class from buttons
            tablinks = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            
            // Show current tab and add active class to button
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }

        // File Input Preview Helper
        function setupFilePreview(inputId, previewId) {
            const input = document.getElementById(inputId);
            if (input) {
                input.addEventListener('change', function(e) {
                    if(this.files && this.files[0]) {
                        document.getElementById(previewId).textContent = "✓ " + this.files[0].name;
                        this.parentElement.style.borderColor = "#10b981";
                        this.parentElement.style.background = "rgba(16, 185, 129, 0.1)";
                    }
                });
            }
        }

        setupFilePreview('file-front', 'preview-front');
        setupFilePreview('file-back', 'preview-back');
        setupFilePreview('file-selfie', 'preview-selfie');
    </script>
</body>
</html>
