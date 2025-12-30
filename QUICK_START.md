# Evolentra MLM - Quick Start Guide

## Your Application is Ready!

Everything is configured and ready to deploy. Follow these simple steps:

---

## Step 1: Get Your Database Password

1. Go to https://supabase.com/dashboard
2. Select project: `mjjefeasezknphfjnzsm`
3. Go to **Settings → Database**
4. Find **Database Password** or **Connection String**
5. Copy the password

---

## Step 2: Choose Deployment Platform

### Option A: DigitalOcean (Recommended)

**Fastest for PHP apps**

1. Push code to GitHub:
   ```bash
   git init
   git add .
   git commit -m "Evolentra MLM Platform"
   git remote add origin https://github.com/USERNAME/REPO.git
   git push -u origin main
   ```

2. Create app:
   - Go to https://cloud.digitalocean.com/apps
   - Click "Create App"
   - Connect your GitHub repo
   - Choose PHP environment

3. Add environment variable:
   ```
   DB_PASSWORD=<paste_your_supabase_password>
   ```

4. Deploy! (takes 3-5 minutes)

### Option B: Vercel (2 minutes)

```bash
npm install -g vercel
vercel --prod
```

Then add `DB_PASSWORD` in Vercel dashboard.

### Option C: Heroku

```bash
heroku create evolentra-mlm
heroku config:set DB_PASSWORD=your_password
git push heroku main
```

---

## Step 3: Test Your Deployment

After deployment, visit these URLs:

1. **Main Site**: `https://your-app.com/`
2. **Health Check**: `https://your-app.com/health.php`
3. **Test Connection**: `https://your-app.com/test_supabase_connection.php`

All should work!

---

## Step 4: Login

### Admin Account
- **Email**: `admin@evolentra.com`
- **Password**: Check your `.env` file or create new admin at `/create_admin.php`

### Demo Account
- **Email**: `demo@evolentra.com`
- **Username**: `demo_user`
- Has test data: $125 ROI, $90 Referral, $75 Binary

---

## What's Already Configured

### Database
- ✓ 17 MLM tables created
- ✓ 2 test users with wallets
- ✓ Security policies enabled
- ✓ System settings configured

### Application
- ✓ Landing page with investment plans
- ✓ User registration & login
- ✓ Dashboard with wallet management
- ✓ Referral system
- ✓ Binary tree visualization
- ✓ Withdrawal system
- ✓ Admin panel
- ✓ Support tickets
- ✓ KYC management

### Security
- ✓ Row Level Security (RLS)
- ✓ Password hashing
- ✓ SQL injection protection
- ✓ Session management
- ✓ HTTPS ready

---

## System Settings (Pre-configured)

| Setting | Value |
|---------|-------|
| Min Withdrawal | $15 |
| Withdrawal Fee | 7% |
| Max Daily Withdrawal | $10,000 |
| Maintenance Mode | Off |
| ROI Processing | Daily at 00:00:00 |
| Binary Processing | Daily at 00:00:00 |

---

## Investment Plans

### ROOT Plan
- Range: $50 - $5,000
- Daily ROI: 1.2%
- Referral Bonus: 9%
- Binary Bonus: 10%
- Duration: 250 days

### RISE Plan (Popular)
- Range: $5,001 - $25,000
- Daily ROI: 1.3%
- Referral Bonus: 9%
- Binary Bonus: 10%
- Duration: 250 days

### TERRA Plan
- Range: $25,001+
- Daily ROI: 1.5%
- Referral Bonus: 9%
- Binary Bonus: 10%
- Duration: 250 days

---

## Supported Payment Methods

- BTC (Bitcoin)
- ETH (Ethereum)
- USDT (Tether)
- TRX (Tron)
- XRP (Ripple)
- Binance Pay (requires API setup)

---

## Next Steps After Deployment

### 1. Configure Admin
   - Login as admin
   - Change default password
   - Set up admin profile

### 2. Configure Payments
   - Add Binance Pay API keys (optional)
   - Set up crypto wallet addresses
   - Test payment flow

### 3. Customize Settings
   - Update system settings if needed
   - Configure withdrawal schedule
   - Set custom fees

### 4. Test Features
   - Create test user
   - Test referral system
   - Test withdrawal request
   - Check admin panel

### 5. Go Live
   - Remove test accounts
   - Update branding
   - Add real payment addresses
   - Start marketing!

---

## Important Files

- `test_supabase_connection.php` - Test database connection
- `health.php` - System health check
- `create_admin.php` - Create admin account
- `dashboard.php` - User dashboard
- `admin/dashboard.php` - Admin panel

---

## Troubleshooting

### "Database connection failed"
- Check DB_PASSWORD is set in environment variables
- Verify Supabase project is active
- Run `/test_supabase_connection.php` for details

### "Page not found"
- Check `.htaccess` is deployed
- Verify platform supports PHP
- Check run command is correct

### "Login not working"
- Create admin: `/create_admin.php`
- Check session configuration
- Verify database has users

---

## Support & Documentation

- **Full Setup Guide**: `SUPABASE_SETUP_COMPLETE.md`
- **Deployment Guide**: `PUBLISH_NOW.md`
- **Technical Docs**: `DEPLOYMENT_README.md`

---

## Supabase Dashboard

**URL**: https://supabase.com/dashboard/project/mjjefeasezknphfjnzsm

From here you can:
- View all tables
- Run SQL queries
- Check logs
- Monitor performance
- Manage users

---

## You're All Set!

Your Evolentra MLM platform is production-ready with:
- Secure Supabase PostgreSQL database
- Complete MLM functionality
- Modern landing page
- Admin panel
- Payment system ready

**Just deploy and start earning!**

---

Need help? Check `TROUBLESHOOTING.md` or review the complete setup in `SUPABASE_SETUP_COMPLETE.md`.
