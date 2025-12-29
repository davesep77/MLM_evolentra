# 🚀 Deploying Evolentra to DigitalOcean App Platform

Complete step-by-step guide to deploy your Evolentra MLM platform to DigitalOcean App Platform.

---

## 📋 Prerequisites

Before you begin, ensure you have:

- ✅ **DigitalOcean Account** - [Sign up here](https://www.digitalocean.com) (New users get $200 credit for 60 days)
- ✅ **GitHub Account** - [Sign up here](https://github.com) (Free)
- ✅ **Git installed** on your local machine
- ✅ **Your API Keys ready**:
  - Binance Pay API Key & Secret
  - Telegram Bot Token
  - BSC Contract Address (if using blockchain features)

---

## 🎯 Deployment Overview

**Estimated Time:** 30-45 minutes

**Steps:**
1. Prepare your GitHub repository
2. Create DigitalOcean App
3. Configure environment variables
4. Set up database
5. Deploy and verify

---

## Step 1: Prepare Your GitHub Repository

### 1.1 Initialize Git (if not already done)

```bash
cd d:\xampp\htdocs\MLM_Evolentra
git init
```

### 1.2 Create GitHub Repository

1. Go to [GitHub](https://github.com/new)
2. Create a new repository named `MLM_Evolentra`
3. **Do NOT** initialize with README (we already have code)
4. Click "Create repository"

### 1.3 Push Your Code to GitHub

```bash
# Add remote repository
git remote add origin https://github.com/YOUR_USERNAME/MLM_Evolentra.git

# Add all files
git add .

# Commit
git commit -m "Initial commit - Evolentra MLM Platform"

# Push to GitHub
git branch -M main
git push -u origin main
```

**Important:** Replace `YOUR_USERNAME` with your actual GitHub username.

---

## Step 2: Create DigitalOcean App

### 2.1 Login to DigitalOcean

1. Go to [DigitalOcean](https://cloud.digitalocean.com)
2. Login or create an account
3. Navigate to **Apps** in the left sidebar
4. Click **Create App**

### 2.2 Connect GitHub Repository

1. Click **GitHub** as your source
2. Authorize DigitalOcean to access your GitHub
3. Select your `MLM_Evolentra` repository
4. Choose the `main` branch
5. Check **Autodeploy** (deploys automatically on git push)
6. Click **Next**

### 2.3 Configure Resources

DigitalOcean will auto-detect your app. You should see:

**Web Service:**
- Name: `web`
- Type: Web Service
- Environment: PHP
- Build Command: `composer install --no-dev --optimize-autoloader`
- Run Command: `heroku-php-apache2`

Click **Edit Plan** and select:
- **Basic Plan**
- **$5/month** (512 MB RAM, 1 vCPU) - Good for starting
- Click **Back**

### 2.4 Add Database

1. Click **Add Resource** → **Database**
2. Select **MySQL**
3. Choose:
   - **Name:** `evolentra-db`
   - **Version:** MySQL 8
   - **Plan:** Basic ($15/month for production)
   - **Development Database:** $7/month (for testing)
4. Click **Add Database**

### 2.5 Environment Variables

Click **Edit** next to Environment Variables and add the following:

**Application Settings:**
```
APP_ENV=production
APP_DEBUG=false
```

**Binance Pay API:**
```
BINANCE_API_KEY=your_actual_binance_api_key
BINANCE_API_SECRET=your_actual_binance_secret
BINANCE_ENVIRONMENT=production
```

**Telegram Bot:**
```
TELEGRAM_BOT_TOKEN=your_telegram_bot_token
TELEGRAM_WEBHOOK_SECRET=random_secret_string_here
```

**BSC/Web3 (if using):**
```
BSC_NETWORK=mainnet
BSC_RPC_URL=https://bsc-dataseed.binance.org/
CONTRACT_ADDRESS=your_contract_address
PRIVATE_KEY=your_private_key
BSCSCAN_API_KEY=your_bscscan_key
```

**Session Security:**
```
SESSION_SECRET=generate_random_32_char_string
```

**Payment Settings:**
```
WITHDRAWAL_FEE_PERCENTAGE=2.5
MIN_WITHDRAWAL_AMOUNT=50
USDT_CONTRACT_ADDRESS=0x55d398326f99059fF775485246999027B3197955
```

> **Note:** The database credentials (DATABASE_URL, DB_HOST, etc.) will be automatically added by DigitalOcean when you add the database component.

### 2.6 Review and Create

1. Review your app configuration
2. Click **Create Resources**
3. Wait for deployment (5-10 minutes)

---

## Step 3: Set Up Database

### 3.1 Access Database Console

1. In your DigitalOcean App dashboard, click on **evolentra-db**
2. Click **Connection Details**
3. Copy the connection string

### 3.2 Export Your Local Database

On your local machine:

```bash
# Export your current database
# Replace with your actual credentials
mysqldump -u root -p evolentra > database_export.sql
```

### 3.3 Import to DigitalOcean Database

**Option A: Using DigitalOcean Console**

1. In database settings, click **Console**
2. Use the web-based MySQL client
3. Copy and paste your SQL schema

**Option B: Using MySQL Workbench or CLI**

```bash
# Connect to DigitalOcean database
mysql -h your-db-host -P 25060 -u doadmin -p your-database-name < database_export.sql
```

Get the connection details from DigitalOcean database dashboard.

### 3.4 Create Admin User (if needed)

Connect to your database and run:

```sql
-- Create admin user
INSERT INTO users (username, email, password, role, status) 
VALUES ('admin', 'admin@evolentra.com', MD5('your_secure_password'), 'admin', 'active');
```

---

## Step 4: Configure Webhooks

### 4.1 Get Your App URL

After deployment, DigitalOcean will provide a URL like:
```
https://evolentra-mlm-xxxxx.ondigitalocean.app
```

### 4.2 Configure Binance Pay Webhook

1. Login to [Binance Merchant Portal](https://merchant.binance.com)
2. Go to **Settings** → **Webhooks**
3. Set webhook URL to:
   ```
   https://your-app.ondigitalocean.app/api/binance_pay_webhook.php
   ```
4. Save changes

### 4.3 Configure Telegram Bot Webhook

Run this command (replace with your values):

```bash
curl -X POST "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://your-app.ondigitalocean.app/telegram_webhook.php",
    "secret_token": "your_webhook_secret"
  }'
```

Or visit in browser:
```
https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook?url=https://your-app.ondigitalocean.app/telegram_webhook.php
```

---

## Step 5: Verify Deployment

### 5.1 Check Health Endpoint

Visit: `https://your-app.ondigitalocean.app/health.php`

You should see:
```json
{
  "status": "healthy",
  "checks": {
    "database": {
      "status": "healthy",
      "message": "Database connection successful"
    },
    "php": {
      "status": "healthy",
      "version": "8.1.x"
    }
  }
}
```

### 5.2 Test Application

1. **Homepage:** `https://your-app.ondigitalocean.app/`
2. **Login:** `https://your-app.ondigitalocean.app/login.php`
3. **Register:** `https://your-app.ondigitalocean.app/register.php`

### 5.3 Test Features

- ✅ User registration
- ✅ Login/logout
- ✅ Dashboard loads
- ✅ Investment page displays
- ✅ Web3 wallet connection
- ✅ Binance Pay integration
- ✅ Telegram bot responds

---

## 🔧 Post-Deployment Configuration

### Set Up Custom Domain (Optional)

1. In DigitalOcean App dashboard, click **Settings**
2. Click **Domains**
3. Click **Add Domain**
4. Enter your domain (e.g., `app.evolentra.com`)
5. Add the provided CNAME record to your DNS provider
6. Wait for SSL certificate (auto-provisioned)

### Set Up Cron Jobs

DigitalOcean App Platform doesn't support cron directly. Use one of these options:

**Option 1: External Cron Service**
- Use [cron-job.org](https://cron-job.org) (free)
- Set up jobs to hit:
  - `https://your-app.ondigitalocean.app/cron_roi.php?secret=YOUR_SECRET`
  - `https://your-app.ondigitalocean.app/cron_binary.php?secret=YOUR_SECRET`
  - `https://your-app.ondigitalocean.app/cron_ranks.php?secret=YOUR_SECRET`

**Option 2: DigitalOcean Worker Component**
- Add a worker component in app.yaml
- Run cron jobs as background processes

### Enable Monitoring

1. In App dashboard, click **Insights**
2. View:
   - Request metrics
   - Error rates
   - Response times
   - Database performance

---

## 🐛 Troubleshooting

### App Won't Deploy

**Check build logs:**
1. Go to App dashboard
2. Click **Activity** tab
3. View build logs for errors

**Common issues:**
- Missing PHP extensions → Add to `composer.json`
- Composer dependencies → Run `composer update` locally first
- Build timeout → Optimize dependencies

### Database Connection Failed

**Check:**
1. Environment variables are set correctly
2. Database is running (check Components tab)
3. `lib/DatabaseConfig.php` is loaded
4. Health check endpoint: `/health.php`

**Fix:**
```bash
# Verify DATABASE_URL is set
# In App Settings → Environment Variables
```

### Webhooks Not Working

**Binance Pay:**
1. Check webhook URL is correct in Binance Merchant Portal
2. Verify SSL certificate is active
3. Check logs: `/api/binance_pay_webhook.php`

**Telegram:**
```bash
# Check webhook status
curl https://api.telegram.org/bot<TOKEN>/getWebhookInfo
```

### 500 Internal Server Error

**Check:**
1. PHP error logs in DigitalOcean dashboard
2. Database connectivity
3. File permissions (should be automatic)
4. Missing environment variables

**Enable debug mode temporarily:**
```
APP_DEBUG=true
```

---

## 💰 Pricing Estimate

**Monthly Costs:**

| Component | Plan | Price |
|-----------|------|-------|
| Web App | Basic (512MB) | $5 |
| Database | Development | $7 |
| **Total** | | **$12/month** |

**Production Setup:**
| Component | Plan | Price |
|-----------|------|-------|
| Web App | Professional (1GB) | $12 |
| Database | Basic (1GB) | $15 |
| **Total** | | **$27/month** |

**Free Credits:**
- New users get **$200 credit** for 60 days
- Enough to run for ~6 months free!

---

## 🔄 Updating Your App

### Deploy Updates

Simply push to GitHub:

```bash
# Make changes to your code
git add .
git commit -m "Update feature X"
git push origin main
```

DigitalOcean will automatically deploy (if Autodeploy is enabled).

### Manual Deployment

1. Go to App dashboard
2. Click **Actions** → **Force Rebuild and Deploy**

---

## 📚 Additional Resources

- [DigitalOcean App Platform Docs](https://docs.digitalocean.com/products/app-platform/)
- [PHP on App Platform](https://docs.digitalocean.com/products/app-platform/languages-frameworks/php/)
- [Managed Databases](https://docs.digitalocean.com/products/databases/)
- [Custom Domains](https://docs.digitalocean.com/products/app-platform/how-to/manage-domains/)

---

## 🆘 Need Help?

- **DigitalOcean Support:** [Submit ticket](https://cloud.digitalocean.com/support/tickets)
- **Community:** [DigitalOcean Community](https://www.digitalocean.com/community)
- **Documentation:** [App Platform Docs](https://docs.digitalocean.com/products/app-platform/)

---

## ✅ Deployment Checklist

- [ ] GitHub repository created and code pushed
- [ ] DigitalOcean account created
- [ ] App created in DigitalOcean App Platform
- [ ] Database provisioned
- [ ] Environment variables configured
- [ ] Database schema imported
- [ ] Application deployed successfully
- [ ] Health check passing (`/health.php`)
- [ ] Login/registration working
- [ ] Binance Pay webhook configured
- [ ] Telegram bot webhook configured
- [ ] Custom domain configured (optional)
- [ ] Cron jobs set up
- [ ] Monitoring enabled

---

**🎉 Congratulations! Your Evolentra MLM Platform is now live on DigitalOcean!**
