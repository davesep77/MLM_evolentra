<div class="sidebar">
    <div style="padding: 1rem 0; text-align: center; margin-bottom: 1rem;">
       <h3 class="gradient-text" style="margin:0; font-size: 1.5rem;">Evolentra</h3>
    </div>

    <!-- Menu Items based on screenshot -->
    <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
        <span class="nav-icon">🏠</span> Dashboard
    </a>

    <a href="profile.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>">
        <span class="nav-icon">👤</span> Profile <span class="nav-arrow">▶</span>
    </a>

    <a href="invest.php" class="nav-link">
        <span class="nav-icon">💳</span> Deposit
    </a>

    <a href="#" class="nav-link">
        <span class="nav-icon">🛍️</span> Package <span class="nav-arrow">▶</span>
    </a>

    <!-- Highlighted Buttons -->
    <a href="#" class="nav-link nav-btn-dark-purple">
        <span class="nav-icon">⭐</span> Trading ROI
    </a>

    <a href="genealogy.php" class="nav-link nav-btn-pink">
        <span class="nav-icon">📊</span> Genealogy
    </a>

    <a href="#" class="nav-link nav-btn-purple">
        <span class="nav-icon">⭐</span> Franchise
    </a>

    <!-- Standard Items Continued -->
    <a href="#" class="nav-link">
        <span class="nav-icon">👥</span> Team <span class="nav-arrow">▶</span>
    </a>

    <a href="#" class="nav-link">
        <span class="nav-icon">💸</span> Transfer <span class="nav-arrow">▶</span>
    </a>

    <a href="withdraw.php" class="nav-link">
        <span class="nav-icon">⬇️</span> Withdrawal <span class="nav-arrow">▶</span>
    </a>

    <a href="#" class="nav-link">
        <span class="nav-icon">🔄</span> Income Report <span class="nav-arrow">▶</span>
    </a>

     <a href="#" class="nav-link">
        <span class="nav-icon">📈</span> Trading Section
    </a>

    <div style="margin-top: auto;">
        <a href="#" class="nav-link">
            <span class="nav-icon">🤖</span> Chatbot <span class="nav-arrow">▶</span>
        </a>
        <a href="login.php" class="nav-link" style="color: #f87171;">
             <span class="nav-icon">🚪</span> Log Out
        </a>
    </div>
</div>
