<?php
require '../config_db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Admin check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Handle Approve/Reject Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve_kyc'])) {
        $user_id = intval($_POST['user_id']);
        
        $sql1 = "UPDATE mlm_users SET kyc_status='verified' WHERE id=$user_id";
        $sql2 = "UPDATE mlm_kyc_documents SET status='approved' WHERE user_id=$user_id";
        
        $result1 = $conn->query($sql1);
        $result2 = $conn->query($sql2);
        
        if ($result1 && $result2) {
            $message = "✅ KYC approved successfully for User ID: $user_id";
        } else {
            $error = "❌ Error approving KYC: " . $conn->error;
        }
        
        // Redirect to prevent form resubmission
        header("Location: kyc.php?msg=" . urlencode($message));
        exit;
        
    } elseif (isset($_POST['reject_kyc'])) {
        $user_id = intval($_POST['user_id']);
        
        $sql1 = "UPDATE mlm_users SET kyc_status='rejected' WHERE id=$user_id";
        $sql2 = "UPDATE mlm_kyc_documents SET status='rejected' WHERE user_id=$user_id";
        
        $result1 = $conn->query($sql1);
        $result2 = $conn->query($sql2);
        
        if ($result1 && $result2) {
            $message = "✅ KYC rejected for User ID: $user_id";
        } else {
            $error = "❌ Error rejecting KYC: " . $conn->error;
        }
        
        // Redirect to prevent form resubmission
        header("Location: kyc.php?msg=" . urlencode($message));
        exit;
    }
}

// Initialize variables
$message = "";
$error = "";


// Check for message from redirect
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

// Fetch all KYC submissions
$kyc_query = "SELECT DISTINCT u.id, u.username, u.email, u.kyc_status, u.created_at as user_created
              FROM mlm_users u
              WHERE u.kyc_status IN ('pending', 'verified', 'rejected')
              ORDER BY 
                CASE u.kyc_status 
                    WHEN 'pending' THEN 1
                    WHEN 'verified' THEN 2
                    WHEN 'rejected' THEN 3
                END,
                u.id DESC";
$kyc_result = $conn->query($kyc_query);

echo "<!-- DEBUG: Found " . ($kyc_result ? $kyc_result->num_rows : 0) . " KYC submissions -->\n";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC Verification - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css">
    <style>
        .kyc-grid {
            display: grid;
            gap: 1.5rem;
        }
        .kyc-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 1.5rem;
            transition: all 0.3s;
        }
        .kyc-card:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(167, 139, 250, 0.3);
        }
        .kyc-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .user-info h3 {
            margin: 0 0 0.25rem 0;
            font-size: 1.1rem;
        }
        .user-info p {
            margin: 0;
            font-size: 0.85rem;
            color: #94a3b8;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-pending { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .status-verified { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .status-rejected { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }
        
        .documents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .doc-item {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 0.75rem;
            padding: 1rem;
            text-align: center;
        }
        .doc-preview {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .doc-preview:hover {
            transform: scale(1.05);
        }
        .doc-label {
            font-size: 0.85rem;
            color: #cbd5e1;
            font-weight: 600;
            text-transform: uppercase;
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1rem;
        }
        .btn-approve, .btn-reject {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.75rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-approve {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        .btn-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        }
        .btn-reject {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 2px solid rgba(239, 68, 68, 0.3);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
        }
        .btn-reject:hover {
            background: rgba(239, 68, 68, 0.25);
            border-color: #ef4444;
            color: #fff;
            transform: translateY(-2px);
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            align-items: center;
            justify-content: center;
        }
        .modal.active {
            display: flex;
        }
        .modal img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 1rem;
        }
        .modal-close {
            position: absolute;
            top: 2rem;
            right: 2rem;
            font-size: 2rem;
            color: white;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="container">
                <h2 style="margin-bottom: 2rem;">
                    <i class="fas fa-id-card" style="margin-right: 0.75rem; color: #a78bfa;"></i>
                    KYC Verification
                </h2>

                <?php if($message): ?>
                    <div style="background: rgba(16, 185, 129, 0.2); color: #86efac; padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.3);">
                        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <?php if($error): ?>
                    <div style="background: rgba(239, 68, 68, 0.2); color: #fca5a5; padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; border: 1px solid rgba(239, 68, 68, 0.3);">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="kyc-grid">
                    <?php if ($kyc_result && $kyc_result->num_rows > 0): ?>
                        <?php while($user = $kyc_result->fetch_assoc()): ?>
                            <?php
                            $user_id = $user['id'];
                            $docs_query = "SELECT * FROM mlm_kyc_documents WHERE user_id=$user_id ORDER BY document_type";
                            $docs_result = $conn->query($docs_query);
                            ?>
                            
                            <div class="kyc-card">
                                <div class="kyc-header">
                                    <div class="user-info">
                                        <h3><?= htmlspecialchars($user['username']) ?></h3>
                                        <p><?= htmlspecialchars($user['email']) ?> • ID: <?= $user['id'] ?></p>
                                    </div>
                                    <span class="status-badge status-<?= $user['kyc_status'] ?>">
                                        <?= strtoupper($user['kyc_status']) ?>
                                    </span>
                                </div>

                                <?php if ($docs_result && $docs_result->num_rows > 0): ?>
                                    <div class="documents-grid">
                                        <?php while($doc = $docs_result->fetch_assoc()): ?>
                                            <div class="doc-item">
                                                <?php
                                                $file_path = $doc['file_path'];
                                                $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                                                ?>
                                                <?php if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                                    <img src="../<?= htmlspecialchars($file_path) ?>" 
                                                         class="doc-preview" 
                                                         onclick="openModal(this.src)"
                                                         alt="<?= $doc['document_type'] ?>">
                                                <?php else: ?>
                                                    <div class="doc-preview" style="display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.05);">
                                                        <i class="fas fa-file-pdf" style="font-size: 3rem; color: #ef4444;"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="doc-label"><?= str_replace('_', ' ', $doc['document_type']) ?></div>
                                                <a href="../<?= htmlspecialchars($file_path) ?>" target="_blank" style="font-size: 0.75rem; color: #a78bfa; text-decoration: none;">
                                                    <i class="fas fa-external-link-alt"></i> View Full
                                                </a>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <p style="color: #94a3b8; text-align: center; padding: 2rem;">No documents uploaded yet.</p>
                                <?php endif; ?>

                                <?php if ($user['kyc_status'] == 'pending'): ?>
                                    <div class="action-buttons">
                                        <!-- DEBUG: Creating forms for user ID <?= $user['id'] ?> -->
                                        <form method="POST" action="kyc.php" style="display: inline;" onsubmit="return confirmAction(event, 'reject', '<?= addslashes(htmlspecialchars($user['username'])) ?>')">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="reject_kyc" value="1">
                                            <button type="submit" class="btn-reject">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </form>
                                        <form method="POST" action="kyc.php" style="display: inline;" onsubmit="return confirmAction(event, 'approve', '<?= addslashes(htmlspecialchars($user['username'])) ?>')">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="approve_kyc" value="1">
                                            <button type="submit" class="btn-approve">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                    </div>
                                <?php elseif ($user['kyc_status'] == 'verified'): ?>
                                    <div style="text-align: right; color: #34d399; font-weight: 600;">
                                        <i class="fas fa-check-circle"></i> KYC Approved
                                    </div>
                                <?php elseif ($user['kyc_status'] == 'rejected'): ?>
                                    <div style="text-align: right; color: #fca5a5; font-weight: 600;">
                                        <i class="fas fa-times-circle"></i> KYC Rejected
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 4rem; color: #94a3b8;">
                            <i class="fas fa-inbox" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                            <h3>No KYC Submissions</h3>
                            <p>There are no KYC verification requests at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="imageModal" class="modal" onclick="closeModal()">
        <span class="modal-close">&times;</span>
        <img id="modalImage" src="" alt="Document Preview">
    </div>

    <script>
        console.log('KYC page loaded');
        
        function confirmAction(event, action, username) {
            const message = action === 'approve' 
                ? `Are you sure you want to APPROVE KYC for ${username}?` 
                : `Are you sure you want to REJECT KYC for ${username}?`;
                
            if (confirm(message)) {
                return true;
            } else {
                event.preventDefault();
                return false;
            }
        }
        
        function openModal(src) {
            document.getElementById('imageModal').classList.add('active');
            document.getElementById('modalImage').src = src;
        }

        function closeModal() {
            document.getElementById('imageModal').classList.remove('active');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
        
        // Log all form submissions
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                console.log('Form submitting:', this);
                console.log('Form data:', new FormData(this));
            });
        });
    </script>
</body>
</html>
