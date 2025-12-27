<div class="sidebar-nav">
    <div class="sidebar-logo">Evolentra</div>
    
    <!-- WALLET CONNECT (Trust/MetaMask/Binance) -->
    <div style="padding: 0 1.5rem; margin-bottom: 2rem;">
        <button id="connect-wallet-btn" onclick="connectWallet()" class="connect-wallet-btn">
            <img src="https://upload.wikimedia.org/wikipedia/commons/3/36/MetaMask_Fox.svg" alt="Fox" width="24" height="24">
            <span>Connect Wallet</span>
        </button>
    </div>

    <style>
        .connect-wallet-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: #f0b90b; /* Yellow/Gold from image */
            color: #000;
            border: none;
            padding: 12px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .connect-wallet-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(240, 185, 11, 0.3);
            filter: brightness(1.05);
        }

        .connect-wallet-btn.connected {
            background: rgba(16, 185, 129, 0.15) !important;
            color: #34d399 !important;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
    </style>

    <a href="dashboard.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
        <span class="nav-icon">🏠</span>
        <span class="nav-text">Dashboard</span>
    </a>

    <!-- PROFILE (Direct Link) -->
    <a href="profile.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>">
        <span class="nav-icon">👤</span>
        <span class="nav-text">Profile</span>
        <span class="nav-arrow">▶</span>
    </a>

    <!-- DEPOSIT (Direct Link) -->
    <a href="invest.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'invest.php' ? 'active' : '' ?>">
        <span class="nav-icon">💳</span>
        <span class="nav-text">Deposit</span>
    </a>

    <!-- PACKAGE MENU -->
    <div class="nav-group">
        <a href="javascript:void(0)" onclick="toggleMenu('menu-package', this)" class="nav-item <?= in_array(basename($_SERVER['PHP_SELF']), ['package.php']) ? 'active' : '' ?>">
            <span class="nav-icon">📦</span>
            <span class="nav-text">Package</span>
            <span class="nav-arrow">▶</span>
        </a>
        <div id="menu-package" class="submenu <?= in_array(basename($_SERVER['PHP_SELF']), ['package.php']) ? 'open' : '' ?>">
            <a href="package.php" class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'package.php' ? 'active' : '' ?>">Buy Package</a>
            <a href="my_packages.php" class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'my_packages.php' ? 'active' : '' ?>">My Packages</a>
        </div>
    </div>

    <a href="trading_roi.php" class="nav-item nav-highlight-purple <?= basename($_SERVER['PHP_SELF']) == 'trading_roi.php' ? 'active' : '' ?>">
        <span class="nav-icon">⭐</span>
        <span class="nav-text">Trading ROI</span>
    </a>

    <a href="bsc_staking.php" class="nav-item nav-highlight-purple <?= basename($_SERVER['PHP_SELF']) == 'bsc_staking.php' ? 'active' : '' ?>">
        <span class="nav-icon">🔗</span>
        <span class="nav-text">BSC Staking</span>
    </a>

    <a href="genealogy.php" class="nav-item nav-highlight-pink <?= basename($_SERVER['PHP_SELF']) == 'genealogy.php' ? 'active' : '' ?>">
        <span class="nav-icon">📊</span>
        <span class="nav-text">Genealogy</span>
    </a>

    <a href="franchise.php" class="nav-item nav-highlight-purple <?= basename($_SERVER['PHP_SELF']) == 'franchise.php' ? 'active' : '' ?>">
        <span class="nav-icon">⭐</span>
        <span class="nav-text">Franchise</span>
    </a>

    <!-- RANKS MENU -->
    <div class="nav-group">
        <a href="javascript:void(0)" onclick="toggleMenu('menu-ranks', this)" class="nav-item nav-highlight-pink <?= in_array(basename($_SERVER['PHP_SELF']), ['ranks.php', 'about_ranks.php']) ? 'active' : '' ?>">
            <span class="nav-icon">🏆</span>
            <span class="nav-text">Ranks & Achievements</span>
            <span class="nav-arrow">▶</span>
        </a>
        <div id="menu-ranks" class="submenu <?= in_array(basename($_SERVER['PHP_SELF']), ['ranks.php', 'about_ranks.php']) ? 'open' : '' ?>">
            <a href="ranks.php" class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'ranks.php' ? 'active' : '' ?>">Leaderboard & Progress</a>
            <a href="about_ranks.php" class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'about_ranks.php' ? 'active' : '' ?>">📚 About Our Ranks</a>
        </div>
    </div>

    <!-- TEAM MENU -->


    <div class="nav-group">
        <a href="javascript:void(0)" onclick="toggleMenu('menu-team', this)" class="nav-item <?= in_array(basename($_SERVER['PHP_SELF']), ['team.php']) ? 'active' : '' ?>">
            <span class="nav-icon">👥</span>
            <span class="nav-text">Team</span>
            <span class="nav-arrow">▶</span>
        </a>
        <div id="menu-team" class="submenu <?= in_array(basename($_SERVER['PHP_SELF']), ['team.php']) ? 'open' : '' ?>">
            <a href="team.php" class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'team.php' ? 'active' : '' ?>">Team View</a>
            <a href="#" class="submenu-item">Direct Referrals</a>
            <a href="#" class="submenu-item">Level Report</a>
        </div>
    </div>

    <!-- TRANSFER MENU -->
    <div class="nav-group">
        <a href="javascript:void(0)" onclick="toggleMenu('menu-transfer', this)" class="nav-item <?= in_array(basename($_SERVER['PHP_SELF']), ['transfer.php']) ? 'active' : '' ?>">
            <span class="nav-icon">💸</span>
            <span class="nav-text">Transfer</span>
            <span class="nav-arrow">▶</span>
        </a>
        <div id="menu-transfer" class="submenu <?= in_array(basename($_SERVER['PHP_SELF']), ['transfer.php']) ? 'open' : '' ?>">
            <a href="transfer.php" class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'transfer.php' ? 'active' : '' ?>">Transfer Funds</a>
            <a href="transfer_history.php" class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'transfer_history.php' ? 'active' : '' ?>">Transfer History</a>
        </div>
    </div>

    <!-- WITHDRAWAL MENU -->
    <div class="nav-group">
        <a href="javascript:void(0)" onclick="toggleMenu('menu-withdraw', this)" class="nav-item <?= in_array(basename($_SERVER['PHP_SELF']), ['withdraw.php']) ? 'active' : '' ?>">
            <span class="nav-icon">⬇️</span>
            <span class="nav-text">Withdrawal</span>
            <span class="nav-arrow">▶</span>
        </a>
        <div id="menu-withdraw" class="submenu <?= in_array(basename($_SERVER['PHP_SELF']), ['withdraw.php']) ? 'open' : '' ?>">
            <a href="withdraw.php" class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'withdraw.php' ? 'active' : '' ?>">Request Withdrawal</a>
            <a href="withdrawal_history.php" class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'withdrawal_history.php' ? 'active' : '' ?>">Withdrawal Log</a>
        </div>
    </div>

    <a href="notifications.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : '' ?>">
        <span class="nav-icon">🔔</span>
        <span class="nav-text">Notifications</span>
    </a>

    <!-- INCOME REPORT MENU -->
    <div class="nav-group">
        <a href="javascript:void(0)" onclick="toggleMenu('menu-income', this)" class="nav-item <?= in_array(basename($_SERVER['PHP_SELF']), ['income_report.php']) ? 'active' : '' ?>">
            <span class="nav-icon">🔄</span>
            <span class="nav-text">Income Report</span>
            <span class="nav-arrow">▶</span>
        </a>
        <div id="menu-income" class="submenu <?= in_array(basename($_SERVER['PHP_SELF']), ['income_report.php']) ? 'open' : '' ?>">
            <a href="income_report.php" class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'income_report.php' ? 'active' : '' ?>">Summary</a>
            <a href="#" class="submenu-item">ROI Log</a>
            <a href="#" class="submenu-item">Binary Log</a>
            <a href="#" class="submenu-item">Referral Log</a>
        </div>
    </div>

    <a href="trading_roi.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'trading_roi.php' ? 'active' : '' ?>">
        <span class="nav-icon">📊</span>
        <span class="nav-text">Trading Section</span>
    </a>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <a href="admin/dashboard.php" class="nav-item">
        <span class="nav-icon">🛡️</span>
        <span class="nav-text">Admin Panel</span>
        <span class="nav-arrow">▶</span>
    </a>
    <?php endif; ?>
    
    <div style="margin-top: auto;">
        <a href="blueprint_vision.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'blueprint_vision.php' ? 'active' : '' ?>" style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #10b981;">
            <span class="nav-icon">📜</span>
            <span class="nav-text">Strategy Blueprint</span>
        </a>

        <a href="support.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'support.php' ? 'active' : '' ?>">
            <span class="nav-icon">🎫</span>
            <span class="nav-text">Support</span>
        </a>

        <a href="login.php" class="nav-item" style="color: #f87171;">
            <span class="nav-icon">🚪</span>
            <span class="nav-text">Log Out</span>
        </a>
    </div>
</div>

<?php include __DIR__ . '/components/chatbot.php'; ?>

<style>
.sidebar-nav {
    position: fixed;
    left: 0;
    top: 0;
    width: 280px;
    height: 100vh;
    background: rgba(15, 23, 42, 0.95);
    backdrop-filter: blur(20px);
    padding: 1.5rem 0;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    z-index: 1000;
    border-right: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-logo {
    font-size: 1.75rem;
    font-weight: 800;
    color: white;
    padding: 0 1.5rem;
    margin-bottom: 2rem;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.875rem 1.5rem;
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
    margin: 0.25rem 0.75rem;
    border-radius: 0.75rem;
    cursor: pointer;
}

.nav-item:hover {
    background: rgba(255, 255, 255, 0.05);
    color: white;
}

.nav-item.active {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.nav-icon {
    font-size: 1.25rem;
    width: 24px;
    text-align: center;
}

.nav-text {
    flex: 1;
    font-size: 0.95rem;
    font-weight: 500;
}

.nav-arrow {
    font-size: 0.75rem;
    opacity: 0.5;
    transition: transform 0.3s ease;
}

.nav-item.expanded .nav-arrow {
    transform: rotate(90deg);
    opacity: 1;
}

/* Submenu Styling */
.submenu {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s ease-out;
    background: rgba(0, 0, 0, 0.2);
    margin: 0 0.75rem;
    border-radius: 0 0 0.75rem 0.75rem;
}

.submenu.open {
    max-height: 500px; /* Arbitrary large height for animation */
}

.submenu-item {
    display: block;
    padding: 0.75rem 1.5rem 0.75rem 3.5rem;
    color: rgba(255, 255, 255, 0.5);
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.2s;
    border-left: 2px solid transparent;
}

.submenu-item:hover {
    color: white;
    background: rgba(255,255,255,0.03);
    border-left: 2px solid rgba(167, 139, 250, 0.5);
}

.submenu-item.active {
    color: #a78bfa;
    border-left: 2px solid #a78bfa;
    background: rgba(167, 139, 250, 0.1);
}

.nav-highlight-purple {
    background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
    color: white !important;
    font-weight: 600;
}

.nav-highlight-pink {
    background: linear-gradient(135deg, #ec4899 0%, #f472b6 100%);
    color: white !important;
    font-weight: 600;
}

.nav-highlight-purple:hover,
.nav-highlight-pink:hover {
    transform: translateX(5px);
    box-shadow: 0 5px 20px rgba(167, 139, 250, 0.3);
}

/* Scrollbar styling */
.sidebar-nav::-webkit-scrollbar {
    width: 6px;
}

.sidebar-nav::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
}

.sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(167, 139, 250, 0.5);
    border-radius: 3px;
}

.sidebar-nav::-webkit-scrollbar-thumb:hover {
    background: rgba(167, 139, 250, 0.7);
}

#connect-wallet-btn.connected {
    background: rgba(16, 185, 129, 0.2) !important;
    color: #34d399 !important;
    border: 1px solid rgba(16, 185, 129, 0.5) !important;
}
</style>

<script>
function toggleMenu(menuId, element) {
    const submenu = document.getElementById(menuId);
    const arrow = element.querySelector('.nav-arrow');
    
    // Check if open
    const isOpen = submenu.classList.contains('open');

    // Close all other menus (Accordion style - optional, can remove if multi-open desired)
    // document.querySelectorAll('.submenu').forEach(el => el.classList.remove('open'));
    // document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('expanded'));

    if (!isOpen) {
        submenu.classList.add('open');
        element.classList.add('expanded');
    } else {
        submenu.classList.remove('open');
        element.classList.remove('expanded');
    }
}

// Expand menu if active link is inside (on load)
document.addEventListener('DOMContentLoaded', () => {
    const activeSubItems = document.querySelectorAll('.submenu-item.active');
    activeSubItems.forEach(item => {
        const parentSubmenu = item.closest('.submenu');
        if (parentSubmenu) {
            parentSubmenu.classList.add('open');
            // Find toggle trigger
            const previousSibling = parentSubmenu.previousElementSibling;
            if (previousSibling && previousSibling.classList.contains('nav-item')) {
                previousSibling.classList.add('expanded');
            }
        }
    });
});
</script>
<script src="js/wallet_connect.js"></script>
