# Evolentra MLM Platform - Deployment Guide

## Database Status

Your Supabase PostgreSQL database is **READY** and configured with all MLM tables:

### Existing Tables:
- `mlm_users` (2 rows) - User accounts and profiles
- `mlm_wallets` (2 rows) - User wallet balances
- `mlm_referral_links` (2 rows) - Referral tracking
- `mlm_withdrawal_requests` - Withdrawal management
- `mlm_notifications` - User notifications
- `mlm_commission_history` - Commission tracking
- `mlm_crypto_payments` - Crypto payment tracking
- `mlm_wallet_connections` - Wallet connections
- `mlm_referral_clicks` - Referral analytics
- `mlm_referral_earnings` - Referral earnings
- `mlm_user_ranks` - User rank system
- `mlm_rank_history` - Rank progression
- `mlm_support_tickets` - Support system
- `mlm_user_settings` - User preferences
- `mlm_admin_logs` - Admin activity logs
- `mlm_login_history` - Login tracking
- `mlm_system_settings` (6 rows) - System configuration

**All tables have Row Level Security (RLS) enabled** for data protection.

## Database Configuration

Your `.env` file is configured with:
```
DB_TYPE=pgsql
DB_HOST=db.mjjefeasezknphfjnzsm.supabase.co
DB_PORT=5432
DB_NAME=postgres
DB_USER=postgres
```

## What's Been Fixed

1. **DatabaseConfig.php** - Now supports PostgreSQL (Supabase) and MySQL
2. **Environment Loading** - Reads from `.env` file automatically
3. **Health Check** - Non-blocking health check at `/health.php`
4. **Build Configuration** - Proper composer.json and package.json
5. **Apache Configuration** - `.htaccess` and Procfile for deployment

## Deployment Options

### Option 1: DigitalOcean App Platform (Recommended)

1. **Create a New App**
   - Go to DigitalOcean App Platform
   - Click "Create App"
   - Connect your GitHub repository

2. **Configure Build Settings**
   - Environment: PHP
   - Build Command: `composer install --no-dev --optimize-autoloader`
   - Run Command: `heroku-php-apache2`

3. **Set Environment Variables**
   Add these in the App Settings:
   ```
   DB_TYPE=pgsql
   DB_HOST=db.mjjefeasezknphfjnzsm.supabase.co
   DB_PORT=5432
   DB_NAME=postgres
   DB_USER=postgres
   DB_PASSWORD=<your_supabase_password>

   VITE_SUPABASE_URL=https://mjjefeasezknphfjnzsm.supabase.co
   VITE_SUPABASE_ANON_KEY=<your_anon_key>

   ADMIN_USERNAME=admin
   ADMIN_EMAIL=admin@evolentra.com
   ADMIN_PASSWORD=<secure_password>
   ```

4. **Deploy**
   - Click "Deploy"
   - Wait 2-5 minutes for build
   - Your app will be live!

### Option 2: Heroku

1. **Install Heroku CLI**
   ```bash
   npm install -g heroku
   ```

2. **Login and Create App**
   ```bash
   heroku login
   heroku create evolentra-mlm
   ```

3. **Add Buildpack**
   ```bash
   heroku buildpacks:add heroku/php
   ```

4. **Set Environment Variables**
   ```bash
   heroku config:set DB_TYPE=pgsql
   heroku config:set DB_HOST=db.mjjefeasezknphfjnzsm.supabase.co
   heroku config:set DB_PORT=5432
   heroku config:set DB_NAME=postgres
   heroku config:set DB_USER=postgres
   heroku config:set DB_PASSWORD=<your_password>
   ```

5. **Deploy**
   ```bash
   git push heroku main
   ```

### Option 3: Vercel (Serverless)

1. **Install Vercel CLI**
   ```bash
   npm install -g vercel
   ```

2. **Deploy**
   ```bash
   vercel
   ```

3. **Add Environment Variables** in Vercel Dashboard
   - Same variables as DigitalOcean

### Option 4: Netlify

1. **Connect Repository**
   - Go to Netlify Dashboard
   - Click "New site from Git"
   - Select your repository

2. **Configure Build**
   - Build Command: `composer install`
   - Publish Directory: `/`

3. **Add Environment Variables**
   - Same as above

## Testing After Deployment

Test these URLs in order:

1. **Static Test**: `https://your-domain.com/static.html`
   - Pure HTML, always works

2. **PHP Test**: `https://your-domain.com/ping.php`
   - Returns JSON with status

3. **System Info**: `https://your-domain.com/info.php`
   - Shows PHP configuration

4. **Health Check**: `https://your-domain.com/health.php`
   - Shows database status

5. **Main Landing**: `https://your-domain.com/`
   - Full MLM landing page

## Admin Access

After deployment, create an admin account by accessing:
```
https://your-domain.com/create_admin.php
```

Or use the default credentials from your .env file:
- Username: admin
- Email: admin@evolentra.com
- Password: (from .env)

## Supabase Configuration

Your Supabase project is already configured. No additional setup needed!

**Supabase URL**: https://mjjefeasezknphfjnzsm.supabase.co

### To Access Supabase Dashboard:
1. Go to https://supabase.com/dashboard
2. Select your project
3. View tables, run SQL queries, check logs

## Next Steps After Deployment

1. **Verify Database Connection**
   - Check health.php shows "healthy" status

2. **Create Admin Account**
   - Access create_admin.php or use existing admin

3. **Test User Registration**
   - Register a test user at /register.php

4. **Configure System Settings**
   - Login as admin
   - Go to Admin Dashboard
   - Set withdrawal limits, fees, etc.

5. **Test Referral System**
   - Create referral links
   - Test registration with referral codes

6. **Configure Payment Gateways**
   - Add Binance Pay credentials
   - Configure crypto wallet addresses

## Security Checklist

- [ ] Change default admin password
- [ ] Set strong SESSION_SECRET
- [ ] Configure CORS properly
- [ ] Enable HTTPS (handled by platform)
- [ ] Review RLS policies in Supabase
- [ ] Test authentication flows
- [ ] Verify withdrawal limits

## Support

If you encounter issues:

1. **Check Logs**
   - Platform deployment logs
   - Application error logs
   - Supabase database logs

2. **Test Database**
   - Visit /health.php
   - Check connection status

3. **Verify Environment Variables**
   - Ensure all required vars are set
   - Check for typos in variable names

## File Structure

```
/
├── .env (your configuration)
├── .htaccess (Apache config)
├── Procfile (deployment config)
├── composer.json (PHP dependencies)
├── package.json (build config)
├── index.php (landing page)
├── login.php
├── register.php
├── dashboard.php
├── health.php (health check)
├── info.php (PHP info)
├── ping.php (simple test)
├── static.html (fallback test page)
├── lib/
│   ├── DatabaseConfig.php (✓ Updated for PostgreSQL)
│   ├── BinancePay.php
│   ├── Compensation.php
│   └── ... other libraries
└── admin/
    ├── dashboard.php
    ├── users.php
    └── ... other admin pages
```

## Your Application is Ready to Deploy!

All configurations are in place. Simply push to your hosting platform and your MLM application will be live.
