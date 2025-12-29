# Evolentra MLM Platform - Setup Complete! 🎉

Your Evolentra MLM platform has been successfully configured with Supabase as the database backend.

## What Was Done

### 1. Database Migration to Supabase
- Migrated from MySQL to Supabase (PostgreSQL)
- Created 17 comprehensive database tables:
  - User management (`mlm_users`, `mlm_wallets`)
  - Payment tracking (`mlm_crypto_payments`, `mlm_wallet_connections`)
  - Referral system (`mlm_referral_links`, `mlm_referral_earnings`, `mlm_referral_clicks`)
  - Commission tracking (`mlm_commission_history`)
  - Withdrawal management (`mlm_withdrawal_requests`)
  - Support system (`mlm_support_tickets`, `mlm_notifications`)
  - Rank tracking (`mlm_user_ranks`, `mlm_rank_history`)
  - Security (`mlm_login_history`, `mlm_admin_logs`)
  - System settings (`mlm_system_settings`, `mlm_user_settings`)

### 2. Security Implementation
- Enabled Row Level Security (RLS) on all tables
- Configured secure access policies
- Set up user authentication policies

### 3. Initial Data
- Created admin account (username: `admin`, password: `password`)
- Created demo user account (username: `demo_user`, password: `password`)
- Inserted default system settings
- Set up referral codes

## Quick Start

### Option 1: View Setup Status
Visit `verify_setup.php` to see your platform status and statistics:
```
http://your-server/verify_setup.php
```

### Option 2: Access the Platform
1. **Landing Page**: `index.php` - Public-facing homepage
2. **Login**: `login.php` - User/Admin login
3. **Register**: `register.php` - New user registration
4. **Dashboard**: `dashboard.php` - User dashboard (after login)

## Default Credentials

### Admin Account
- **Username**: `admin`
- **Email**: `admin@evolentra.com`
- **Password**: `password`
- **Role**: Administrator

### Demo User
- **Username**: `demo_user`
- **Email**: `demo@evolentra.com`
- **Password**: `password`
- **Role**: Regular user

**IMPORTANT**: Change these passwords immediately after first login!

## Environment Configuration

Your `.env` file is configured with:
- Supabase URL and API keys
- PostgreSQL database connection details
- Admin credentials

## Platform Features

### Investment Plans
1. **ROOT Plan** - $50-$5,000 (1.2% daily ROI)
2. **RISE Plan** - $5,001-$25,000 (1.3% daily ROI)
3. **TERRA Plan** - $25,001+ (1.5% daily ROI)

### Earning Mechanisms
- **ROI Income**: 1.2%-1.5% daily returns
- **Referral Income**: 9% direct referral bonus
- **Binary Income**: 10% matching bonus on weaker leg
- **Rank Rewards**: Career progression bonuses

### Payment Methods
- Bitcoin (BTC)
- Ethereum (ETH)
- Tether (USDT)
- Tron (TRX)
- Ripple (XRP)

## Next Steps

1. **Change Default Passwords**
   - Log in as admin
   - Update admin password
   - Delete or update demo user

2. **Configure System Settings**
   - Go to Admin Dashboard
   - Update withdrawal limits
   - Configure commission rates
   - Set processing times

3. **Setup Payment Integration**
   - Configure Binance Pay (if using)
   - Set up wallet addresses
   - Test payment flows

4. **Customize Branding**
   - Update logo and colors in `style.css`
   - Modify landing page content
   - Add company information

5. **Security Hardening**
   - Enable 2FA for admin account
   - Review RLS policies
   - Set up backup procedures
   - Configure SSL/HTTPS

## Running Locally

### Requirements
- PHP 7.4 or higher with PDO PostgreSQL extension
- Web server (Apache/Nginx/PHP built-in)
- Composer (for dependencies)

### Start Development Server
```bash
# Using PHP built-in server
php -S localhost:8000

# Or with run_locally.bat
run_locally.bat
```

### Access the Platform
Open your browser and go to:
```
http://localhost:8000
```

## Database Access

### Supabase Dashboard
Access your database at: https://supabase.com/dashboard

### Connection Details
- **Host**: `db.mjjefeasezknphfjnzsm.supabase.co`
- **Port**: `5432`
- **Database**: `postgres`
- **User**: `postgres`

## Troubleshooting

### Database Connection Issues
1. Check that PostgreSQL PDO extension is installed
2. Verify Supabase credentials in `.env`
3. Ensure database password is set
4. Check firewall/security group settings

### Login Issues
1. Verify user exists in `mlm_users` table
2. Check password hash format
3. Review session configuration
4. Check PHP error logs

### Missing Data
1. Run `verify_setup.php` to check table status
2. Re-run migrations if needed
3. Check RLS policies in Supabase

## Support Files

- `config_db.php` - Database configuration
- `lib/DatabaseConfig.php` - Database connection class
- `verify_setup.php` - Setup verification tool

## Important Notes

1. **Database Password**: You may need to get your Supabase database password from the Supabase dashboard under Settings > Database
2. **Security**: All sensitive operations require authentication
3. **RLS Policies**: Currently set to allow access for testing. Review and tighten for production.
4. **Backups**: Set up regular database backups in Supabase

## Additional Resources

- [Supabase Documentation](https://supabase.com/docs)
- [Project README](README.md)
- [Deployment Guide](DEPLOYMENT_GUIDE.md)
- [Troubleshooting Guide](TROUBLESHOOTING.md)

## Platform Structure

```
/project
├── index.php              # Landing page
├── login.php              # Authentication
├── register.php           # User registration
├── dashboard.php          # User dashboard
├── config_db.php          # Database config
├── verify_setup.php       # Setup verification
├── admin/                 # Admin panel
├── api/                   # API endpoints
├── lib/                   # Core libraries
└── js/                    # Frontend scripts
```

## Getting Help

If you encounter any issues:
1. Check `verify_setup.php` for system status
2. Review PHP error logs
3. Check Supabase logs in dashboard
4. Refer to TROUBLESHOOTING.md

---

**Your platform is now ready! Visit `verify_setup.php` to confirm everything is working correctly.**
