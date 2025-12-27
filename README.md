# Evolentra MLM Platform

A comprehensive Multi-Level Marketing (MLM) platform with cryptocurrency integration, featuring advanced compensation plans, Web3 wallet connectivity, and real-time trading analytics.

## Features

### Investment Plans
- **ROOT Plan**: $50 - $5,000 | 1.2% Daily ROI | $2,000 Daily Cap
- **RISE Plan**: $5,001 - $25,000 | 1.3% Daily ROI | $2,500 Daily Cap
- **TERRA Plan**: $25,001+ | 1.5% Daily ROI | $5,000 Daily Cap

All plans feature:
- 250-day duration limit
- Tiered daily earning caps with flash-out mechanism
- 9% referral commission
- 10% binary matching bonus

### Core Functionality
- **User Management**: Registration, KYC verification, profile management
- **Wallet System**: Multi-wallet architecture (ROI, Referral, Binary, Main)
- **Web3 Integration**: MetaMask/Trust Wallet connection with cryptographic proof of ownership
- **Payment Processing**: Binance Pay integration, manual USDT transfers
- **Compensation Engine**: Automated ROI, referral, and binary commission processing
- **Binary Tree**: Automatic placement and genealogy visualization
- **Ranking System**: Performance-based user ranking
- **Admin Panel**: Complete system management and oversight
- **Real-time Trading**: Live cryptocurrency price feeds and charts

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Web3**: ethers.js for wallet integration
- **Charts**: TradingView Lightweight Charts
- **APIs**: Binance API, Binance Pay API

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer
- Web server (Apache/Nginx) or PHP built-in server

### Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/MLM_Evolentra.git
   cd MLM_Evolentra
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Configure database**
   - Create a MySQL database
   - Copy `config_db.example.php` to `config_db.php`
   - Update database credentials in `config_db.php`

4. **Import database schema**
   ```bash
   mysql -u your_username -p your_database < database/schema.sql
   ```

5. **Configure Binance Pay (Optional)**
   - Update API keys in Admin Settings panel
   - Or manually insert into `mlm_system_settings` table

6. **Start the application**
   ```bash
   php -S localhost:8000
   ```

7. **Access the application**
   - Open browser: `http://localhost:8000`

## Configuration

### Database Configuration
Create `config_db.php` with your database credentials:
```php
<?php
session_start();
$servername = "localhost";
$username = "your_username";
$password = "your_password";
$dbname = "your_database";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
```

### Cron Jobs
Set up the following cron jobs for automated processing:

```bash
# Daily ROI Processing (runs at midnight)
0 0 * * * cd /path/to/MLM_Evolentra && php cron_roi.php

# Binary Commission Processing (runs every 6 hours)
0 */6 * * * cd /path/to/MLM_Evolentra && php cron_binary.php

# Rank Calculation (runs daily at 1 AM)
0 1 * * * cd /path/to/MLM_Evolentra && php calculate_ranks.php
```

## Security Features

- **Wallet Verification**: Cryptographic signature verification for Web3 wallets
- **Session Management**: Secure session handling
- **SQL Injection Protection**: Prepared statements throughout
- **XSS Prevention**: Input sanitization and output encoding
- **CSRF Protection**: Token-based form validation

## Project Structure

```
MLM_Evolentra/
├── admin/              # Admin panel files
├── api/                # API endpoints
├── css/                # Stylesheets
├── js/                 # JavaScript files
├── lib/                # Core libraries
│   ├── Compensation.php
│   ├── BinancePay.php
│   └── EllipticValidation.php
├── uploads/            # User uploads (KYC documents)
├── config_db.php       # Database configuration
├── index.php           # Landing page
├── dashboard.php       # User dashboard
├── invest.php          # Investment page
├── package.php         # Package selection
└── README.md           # This file
```

## License

Proprietary - All rights reserved

## Support

For support and inquiries, please contact the development team.

---

**Note**: This is a production MLM platform. Ensure all security measures are properly configured before deployment.
