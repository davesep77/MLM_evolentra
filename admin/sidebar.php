<div class="sidebar-nav">
    <div class="sidebar-logo">
        Evolentra 
        <span style="font-size: 0.8rem; opacity: 0.7; display: block; font-weight: 400; color: #a78bfa;">Admin Panel</span>
    </div>
    
    <!-- BACK TO USER DASHBOARD -->
    <a href="../dashboard.php" class="nav-item">
        <span class="nav-icon">←</span>
        <span class="nav-text">User Dashboard</span>
    </a>

    <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 0.5rem 1.5rem 1rem;"></div>

    <!-- ADMIN OVERVIEW -->
    <a href="dashboard.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
        <span class="nav-icon">📊</span>
        <span class="nav-text">Overview</span>
    </a>

    <!-- USER MANAGEMENT -->
    <div class="nav-group">
        <a href="javascript:void(0)" onclick="toggleMenu('menu-users', this)" class="nav-item <?= in_array(basename($_SERVER['PHP_SELF']), ['users.php', 'kyc.php', 'add_rich_users.php']) ? 'active' : '' ?>">
            <span class="nav-icon">👥</span>
            <span class="nav-text">User Management</span>
            <span class="nav-arrow">▶</span>
        </a>
        <div id="menu-users" class="submenu <?= in_array(basename($_SERVER['PHP_SELF']), ['users.php', 'kyc.php', 'add_rich_users.php']) ? 'open' : '' ?>">
            <a href="users.php" class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">All Users</a>
            <a href="kyc.php" class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'kyc.php' ? 'active' : '' ?>">KYC Verification</a>
            <a href="add_rich_users.php" class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'add_rich_users.php' ? 'active' : '' ?>">Add Fake Users</a>
        </div>
    </div>

    <!-- FINANCIALS -->
    <div class="nav-group">
        <a href="javascript:void(0)" onclick="toggleMenu('menu-finance', this)" class="nav-item <?= in_array(basename($_SERVER['PHP_SELF']), ['withdrawals.php', 'transfers.php']) ? 'active' : '' ?>">
            <span class="nav-icon">💰</span>
            <span class="nav-text">Finance</span>
            <span class="nav-arrow">▶</span>
        </a>
        <div id="menu-finance" class="submenu <?= in_array(basename($_SERVER['PHP_SELF']), ['withdrawals.php', 'transfers.php']) ? 'open' : '' ?>">
            <a href="binance_manage.php" class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'binance_manage.php' ? 'active' : '' ?>">Binance Connections</a>
            <a href="deposits.php" class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'deposits.php' ? 'active' : '' ?>">Deposit Verification</a>
            <a href="withdrawals.php" class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'withdrawals.php' ? 'active' : '' ?>">Withdrawal Requests</a>
            <a href="transfers.php" class="submenu-item <?= basename($_SERVER['PHP_SELF']) == 'transfers.php' ? 'active' : '' ?>">Transfer Approvals</a>
        </div>
    </div>

    <a href="support.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'support.php' ? 'active' : '' ?>">
        <span class="nav-icon">🎫</span>
        <span class="nav-text">Support Tickets</span>
    </a>

    <a href="settings.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
        <span class="nav-icon">⚙️</span>
        <span class="nav-text">System Settings</span>
    </a>

    <div style="margin-top: auto;">
        <a href="../blueprint_vision.php" class="nav-item" style="color: #34d399; background: rgba(52, 211, 153, 0.1);">
            <span class="nav-icon">🗺️</span>
            <span class="nav-text">Strategy Blueprint</span>
        </a>
        <a href="../login.php" class="nav-item" style="color: #f87171;">
            <span class="nav-icon">🚪</span>
            <span class="nav-text">Log Out</span>
        </a>
    </div>
</div>

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
    max-height: 500px;
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
</style>

<script>
function toggleMenu(menuId, element) {
    const submenu = document.getElementById(menuId);
    
    // Check if open
    const isOpen = submenu.classList.contains('open');

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
