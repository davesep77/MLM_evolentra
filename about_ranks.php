<?php
require 'config_db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Define the SVG Assets as PHP functions for clarity
function getRankIcon($rank) {
    switch ($rank) {
        case 'Associate':
            return '<svg viewBox="0 0 100 100" class="rank-svg"><defs><linearGradient id="grad-asc" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:#cbd5e1;stop-opacity:1" /><stop offset="100%" style="stop-color:#64748b;stop-opacity:1" /></linearGradient></defs><circle cx="50" cy="50" r="40" fill="url(#grad-asc)" stroke="rgba(255,255,255,0.2)" stroke-width="2"/><path d="M50 30c-5.5 0-10 4.5-10 10s4.5 10 10 10 10-4.5 10-10-4.5-10-10-10zm0 24c-8.3 0-25 4.2-25 12.5V70h50v-3.5c0-8.3-16.7-12.5-25-12.5z" fill="white" opacity="0.9"/></svg>';
        case 'Bronze':
            return '<svg viewBox="0 0 100 100" class="rank-svg"><defs><linearGradient id="grad-bronze" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:#f97316;stop-opacity:1" /><stop offset="50%" style="stop-color:#9a3412;stop-opacity:1" /><stop offset="100%" style="stop-color:#431407;stop-opacity:1" /></linearGradient><filter id="shine-bronze"><feGaussianBlur in="SourceAlpha" stdDeviation="2" result="blur"/><feSpecularLighting in="blur" surfaceScale="5" specularConstant="1" specularExponent="20" lighting-color="#fb923c" result="specOut"><fePointLight x="-50" y="-100" z="200"/></feSpecularLighting><feComposite in="specOut" in2="SourceAlpha" operator="in" result="specOut"/><feComposite in="SourceGraphic" in2="specOut" operator="arithmetic" k1="0" k2="1" k3="1" k4="0"/></filter></defs><circle cx="50" cy="50" r="42" fill="url(#grad-bronze)" filter="url(#shine-bronze)"/><text x="50" y="65" font-family="Arial" font-size="40" fill="rgba(255,255,255,0.5)" text-anchor="middle" font-weight="bold">B</text></svg>';
        case 'Silver':
            return '<svg viewBox="0 0 100 100" class="rank-svg"><defs><linearGradient id="grad-silver" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:#f8fafc;stop-opacity:1" /><stop offset="50%" style="stop-color:#94a3b8;stop-opacity:1" /><stop offset="100%" style="stop-color:#475569;stop-opacity:1" /></linearGradient><filter id="shine-silver"><feGaussianBlur in="SourceAlpha" stdDeviation="2" result="blur"/><feSpecularLighting in="blur" surfaceScale="5" specularConstant="1.2" specularExponent="25" lighting-color="#fff" result="specOut"><fePointLight x="-50" y="-100" z="200"/></feSpecularLighting><feComposite in="specOut" in2="SourceAlpha" operator="in" result="specOut"/><feComposite in="SourceGraphic" in2="specOut" operator="arithmetic" k1="0" k2="1" k3="1" k4="0"/></filter></defs><circle cx="50" cy="50" r="42" fill="url(#grad-silver)" filter="url(#shine-silver)"/><text x="50" y="65" font-family="Arial" font-size="40" fill="rgba(0,0,0,0.2)" text-anchor="middle" font-weight="bold">S</text></svg>';
        case 'Gold':
            return '<svg viewBox="0 0 100 100" class="rank-svg"><defs><linearGradient id="grad-gold" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:#fef08a;stop-opacity:1" /><stop offset="50%" style="stop-color:#eab308;stop-opacity:1" /><stop offset="100%" style="stop-color:#854d0e;stop-opacity:1" /></linearGradient><filter id="shine-gold"><feGaussianBlur in="SourceAlpha" stdDeviation="2" result="blur"/><feSpecularLighting in="blur" surfaceScale="5" specularConstant="1.5" specularExponent="30" lighting-color="#fff" result="specOut"><fePointLight x="-50" y="-100" z="200"/></feSpecularLighting><feComposite in="specOut" in2="SourceAlpha" operator="in" result="specOut"/><feComposite in="SourceGraphic" in2="specOut" operator="arithmetic" k1="0" k2="1" k3="1" k4="0"/></filter></defs><circle cx="50" cy="50" r="42" fill="url(#grad-gold)" filter="url(#shine-gold)"/><text x="50" y="65" font-family="Arial" font-size="40" fill="rgba(0,0,0,0.2)" text-anchor="middle" font-weight="bold">G</text></svg>';
        case 'Platinum':
            return '<svg viewBox="0 0 100 100" class="rank-svg"><defs><linearGradient id="grad-plat" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:#e2e8f0;stop-opacity:1" /><stop offset="50%" style="stop-color:#94a3b8;stop-opacity:1" /><stop offset="100%" style="stop-color:#334155;stop-opacity:1" /></linearGradient></defs><rect x="20" y="25" width="60" height="50" rx="4" fill="url(#grad-plat)" transform="rotate(-15 50 50)"/><path d="M25 35l10-5 40 10-10 5z" fill="white" opacity="0.3" transform="rotate(-15 50 50)"/></svg>';
        case 'Ruby':
            return '<svg viewBox="0 0 100 100" class="rank-svg"><defs><linearGradient id="grad-ruby" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:#fb7185;stop-opacity:1" /><stop offset="50%" style="stop-color:#e11d48;stop-opacity:1" /><stop offset="100%" style="stop-color:#881337;stop-opacity:1" /></linearGradient><filter id="ruby-glow"><feGaussianBlur stdDeviation="2" result="blur"/><feComposite in="SourceGraphic" in2="blur" operator="over"/></filter></defs><path d="M50 15l25 25-25 45-25-45z" fill="url(#grad-ruby)" filter="url(#ruby-glow)"/><path d="M50 15l12 12-12 13-12-13z" fill="white" opacity="0.3"/></svg>';
        case 'Emerald':
            return '<svg viewBox="0 0 100 100" class="rank-svg"><defs><linearGradient id="grad-emerald" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:#4ade80;stop-opacity:1" /><stop offset="50%" style="stop-color:#16a34a;stop-opacity:1" /><stop offset="100%" style="stop-color:#064e3b;stop-opacity:1" /></linearGradient></defs><rect x="30" y="20" width="40" height="60" rx="2" fill="url(#grad-emerald)"/><path d="M30 20l20 10 20-10-20 10z" fill="white" opacity="0.3"/></svg>';
        case 'Diamond':
            return '<svg viewBox="0 0 100 100" class="rank-svg"><defs><linearGradient id="grad-diamond" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:#fff;stop-opacity:1" /><stop offset="50%" style="stop-color:#bae6fd;stop-opacity:1" /><stop offset="100%" style="stop-color:#38bdf8;stop-opacity:1" /></linearGradient></defs><path d="M50 15l30 30-30 40-30-40z" fill="url(#grad-diamond)"/><path d="M50 15l15 15-15 15-15-15z" fill="white" opacity="0.5"/><path d="M30 45l20 0-10-15z" fill="white" opacity="0.2"/></svg>';
        case 'Crown Diamond':
            return '<svg viewBox="0 0 100 100" class="rank-svg"><defs><linearGradient id="grad-crown" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:#fde047;stop-opacity:1" /><stop offset="100%" style="stop-color:#a16207;stop-opacity:1" /></linearGradient></defs><path d="M20 70h60v10H20zM25 65l-5-30 15 15 15-25 15 25 15-15-5 30z" fill="url(#grad-crown)"/><circle cx="35" cy="35" r="3" fill="#38bdf8"/><circle cx="50" cy="25" r="3" fill="#38bdf8"/><circle cx="65" cy="35" r="3" fill="#38bdf8"/></svg>';
    }
}

$rank_info = [
    [
        'name' => 'Associate',
        'icon_type' => 'Associate',
        'color' => '#94a3b8',
        'glow' => 'rgba(148, 163, 184, 0.3)',
        'subtitle' => 'The Beginning of Your Journey',
        'description' => 'Welcome to Evolentra! As an Associate, you\'re taking your first steps into the world of network marketing. This is where everyone begins – it\'s your foundation.',
        'significance' => 'Entry-level position where you learn the ropes and establish your presence in the network.',
        'inspired_by' => 'Similar to "Distributor" or "Member" ranks in companies like Amway.'
    ],
    [
        'name' => 'Bronze',
        'icon_type' => 'Bronze',
        'color' => '#fb923c',
        'glow' => 'rgba(251, 146, 60, 0.3)',
        'subtitle' => 'Your First Achievement',
        'description' => 'Congratulations on reaching Bronze! You\'ve proven you\'re serious about building your business. With $500 in team volume and 2 direct referrals.',
        'significance' => 'First recognition level proving you can build a small team and generate initial sales volume.',
        'inspired_by' => 'Modeled after "Bronze Consultant" in Avon.'
    ],
    [
        'name' => 'Silver',
        'icon_type' => 'Silver',
        'color' => '#cbd5e1',
        'glow' => 'rgba(203, 213, 225, 0.3)',
        'subtitle' => 'Rising Through the Ranks',
        'description' => 'Silver achievers are building momentum! With $2,000 in team volume and 5 direct referrals, you\'re developing real leadership skills.',
        'significance' => 'Demonstrates consistent growth and ability to develop a productive team.',
        'inspired_by' => 'Based on "Silver Director" from Nu Skin.'
    ],
    [
        'name' => 'Gold',
        'icon_type' => 'Gold',
        'color' => '#facc15',
        'glow' => 'rgba(250, 204, 21, 0.3)',
        'subtitle' => 'Proven Leader',
        'description' => 'Gold is where leaders truly shine! Achieving $5,000 in team volume with 10 direct referrals shows you\'re a skilled recruiter.',
        'significance' => 'Major milestone indicating strong leadership and a growing organization.',
        'inspired_by' => 'Inspired by "Gold Director" (Mary Kay).'
    ],
    [
        'name' => 'Platinum',
        'icon_type' => 'Platinum',
        'color' => '#94a3b8',
        'glow' => 'rgba(148, 163, 184, 0.3)',
        'subtitle' => 'Elite Status Achieved',
        'description' => 'Platinum members are elite performers! With $15,000 team volume, 20 direct referrals, and 2 qualifying Gold legs.',
        'significance' => 'Elite tier with significant passive income potential through team depth.',
        'inspired_by' => 'Modeled after "Platinum" ranks in Younique.'
    ],
    [
        'name' => 'Ruby',
        'icon_type' => 'Ruby',
        'color' => '#f43f5e',
        'glow' => 'rgba(244, 63, 94, 0.3)',
        'subtitle' => 'Precious Achievement',
        'description' => 'Ruby is a precious achievement! $50,000 in team volume, 50 direct referrals, and 3 Platinum legs means you\'re running a serious empire.',
        'significance' => 'High-level achievement with substantial income and recognition.',
        'inspired_by' => 'Based on precious stone ranks across the MLM industry.'
    ],
    [
        'name' => 'Emerald',
        'icon_type' => 'Emerald',
        'color' => '#22c55e',
        'glow' => 'rgba(34, 197, 94, 0.3)',
        'subtitle' => 'Exemplary Excellence',
        'description' => 'Emerald achievers exemplify excellence! With $150,000 team volume, 100 direct referrals, and 5 Ruby legs.',
        'significance' => 'Top-tier leadership position with global recognition.',
        'inspired_by' => 'Inspired by "Emerald" in Amway.'
    ],
    [
        'name' => 'Diamond',
        'icon_type' => 'Diamond',
        'color' => '#38bdf8',
        'glow' => 'rgba(56, 189, 248, 0.3)',
        'subtitle' => 'Extraordinary Success',
        'description' => 'Diamond achievers have reached extraordinary heights! $500,000 in team volume and 200 direct referrals.',
        'significance' => 'Pinnacle achievement representing true mastery of network marketing.',
        'inspired_by' => 'Modeled after "Diamond" ranks in major MLM companies.'
    ],
    [
        'name' => 'Crown Diamond',
        'icon_type' => 'Crown Diamond',
        'color' => '#fde047',
        'glow' => 'rgba(253, 224, 71, 0.5)',
        'subtitle' => 'The Ultimate Achievement',
        'description' => 'Crown Diamond is the absolute peak reserved for industry legends! With $1.5M+ team volume and 5 Diamond legs.',
        'significance' => 'Legendary status – the highest honor representing extraordinary achievement.',
        'inspired_by' => 'The absolute highest ranks in the digital economy.'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evolentra - Ranking Mastery</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-deep: #020617;
            --accent-primary: #8b5cf6;
            --accent-secondary: #ec4899;
            --glass-bg: rgba(15, 23, 42, 0.6);
            --glass-border: rgba(255, 255, 255, 0.1);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-deep);
            color: var(--text-primary);
            overflow-x: hidden;
            min-height: 100vh;
        }

        /* Ambient Background */
        .ambient-orb {
            position: fixed;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            filter: blur(120px);
            z-index: -1;
            opacity: 0.15;
            pointer-events: none;
        }
        .orb-1 { top: -100px; right: -100px; background: var(--accent-primary); animation: pulse 10s infinite alternate; }
        .orb-2 { bottom: -100px; left: -100px; background: var(--accent-secondary); animation: pulse 12s infinite alternate-reverse; }

        @keyframes pulse {
            from { transform: scale(1) translate(0, 0); }
            to { transform: scale(1.2) translate(50px, 50px); }
        }

        .app-container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            margin-left: 280px; 
            padding: 3rem 2rem;
            max-width: 1400px;
            margin-right: auto;
        }

        /* Hero Section */
        .hero {
            text-align: center;
            margin-bottom: 4rem;
            padding-top: 2rem;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        .hero p {
            color: var(--text-secondary);
            font-size: 1.25rem;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Glass Cards */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .glass-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at top left, rgba(255,255,255,0.05), transparent);
            pointer-events: none;
        }

        /* Rank Cards Grid */
        .rank-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }

        .rank-card {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s ease, transform 0.6s ease, border-color 0.4s, box-shadow 0.4s;
        }

        .rank-card.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .rank-card:hover {
            border-color: var(--rank-color);
            box-shadow: 0 0 30px var(--rank-glow);
        }

        .rank-header {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .rank-icon-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            padding: 10px;
        }

        .rank-card:hover .rank-icon-wrapper {
            transform: rotate(-5deg) scale(1.15) translateY(-5px);
            background: rgba(255, 255, 255, 0.1);
        }

        .rank-svg {
            width: 100%;
            height: 100%;
            filter: drop-shadow(0 5px 15px var(--rank-glow));
        }

        .rank-header h2 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .rank-subtitle {
            color: var(--rank-color);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .rank-body {
            border-top: 1px solid var(--glass-border);
            padding-top: 1.5rem;
        }

        .rank-section {
            margin-bottom: 1.25rem;
        }

        .rank-section h4 {
            font-size: 0.85rem;
            text-transform: uppercase;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .rank-section p {
            font-size: 1rem;
            color: #cbd5e1;
            line-height: 1.6;
        }

        /* CTA Footer */
        .cta-section {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(236, 72, 153, 0.1));
            border: 1px dashed rgba(139, 92, 246, 0.3);
            text-align: center;
            padding: 4rem 2rem;
            margin-top: 4rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-primary), #6d28d9);
            color: white;
            box-shadow: 0 10px 20px rgba(124, 58, 237, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(124, 58, 237, 0.4);
        }

        .btn-outline {
            border: 1px solid var(--accent-secondary);
            color: var(--accent-secondary);
        }
        .btn-outline:hover {
            background: rgba(236, 72, 153, 0.1);
        }

        @media (max-width: 1024px) {
            .main-content { margin-left: 0; padding: 2rem 1rem; }
            .hero h1 { font-size: 2.5rem; }
        }
    </style>
</head>
<body>
    <div class="ambient-orb orb-1"></div>
    <div class="ambient-orb orb-2"></div>

    <div class="app-container">
        <?php include 'sidebar_nav.php'; ?>
        
        <div class="main-content">
            <header class="hero">
                <h1>Ranking Mastery</h1>
                <p>Ascend through 9 tiers of industry-leading excellence. Each rank is a testament to your leadership and a gateway to extraordinary rewards.</p>
            </header>

            <!-- Global Philosophy Card -->
            <div class="glass-card" style="border-left: 4px solid var(--accent-primary);">
                <div style="display:flex; gap: 2rem; align-items: center;">
                    <div style="font-size: 3rem; color: var(--accent-primary);"><i class="fas fa-crown"></i></div>
                    <div>
                        <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem; color: #fff;">The Evolentra Standard</h3>
                        <p style="color: var(--text-secondary); line-height: 1.8;">
                            Our ranking system isn't just a ladder; it's a mentorship framework. From your first steps as an Associate to the legendary status of Crown Diamond, we reward the creation of value, the growth of people, and the sustainability of your digital empire.
                        </p>
                    </div>
                </div>
            </div>

            <div class="rank-grid">
                <?php foreach ($rank_info as $info): ?>
                <div class="glass-card rank-card" style="--rank-color: <?= $info['color'] ?>; --rank-glow: <?= $info['glow'] ?>;">
                    <div class="rank-header">
                        <div class="rank-icon-wrapper">
                            <?= getRankIcon($info['icon_type']) ?>
                        </div>
                        <div>
                            <p class="rank-subtitle"><?= $info['subtitle'] ?></p>
                            <h2><?= $info['name'] ?></h2>
                        </div>
                    </div>
                    <div class="rank-body">
                        <div class="rank-section">
                            <h4><i class="fas fa-feather-pointed"></i> Purpose</h4>
                            <p><?= $info['description'] ?></p>
                        </div>
                        <div class="rank-section">
                            <h4><i class="fas fa-bolt"></i> Impact</h4>
                            <p><?= $info['significance'] ?></p>
                        </div>
                        <div class="rank-section">
                            <h4><i class="fas fa-compass"></i> Pedigree</h4>
                            <p style="font-style: italic; opacity: 0.8;"><?= $info['inspired_by'] ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <footer class="glass-card cta-section">
                <i class="fas fa-fire-alt" style="font-size: 3rem; color: var(--accent-secondary); margin-bottom: 1.5rem;"></i>
                <h2 style="font-size: 2.25rem; margin-bottom: 1rem;">Forge Your Legacy</h2>
                <p style="color: var(--text-secondary); margin-bottom: 2.5rem; max-width: 600px; margin-inline: auto;">
                    The summit is within reach. Start building your team, empower your direct referrals, and watch your rank ascend to the elite tiers of Evolentra.
                </p>
                <div style="display: flex; gap: 1.5rem; justify-content: center; flex-wrap: wrap;">
                    <a href="ranks.php" class="btn btn-primary">Track My Ascent <i class="fas fa-rocket"></i></a>
                    <a href="team.php" class="btn btn-outline">Analyze My Team <i class="fas fa-users-viewfinder"></i></a>
                </div>
            </footer>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.rank-card');
            
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.classList.add('visible');
                        }, (index % 3) * 150); 
                    }
                });
            }, observerOptions);

            cards.forEach(card => observer.observe(card));
        });
    </script>
</body>
</html>
