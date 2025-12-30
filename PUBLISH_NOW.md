# Publish Your MLM Platform Now

## Quick Start - Choose Your Platform

Your application is **READY TO DEPLOY**. Choose one of these options:

---

## 🚀 Option 1: Vercel (Fastest - 2 Minutes)

### Steps:

1. **Install Vercel CLI** (if not already installed):
   ```bash
   npm install -g vercel
   ```

2. **Login to Vercel**:
   ```bash
   vercel login
   ```

3. **Deploy**:
   ```bash
   vercel --prod
   ```

4. **Add Environment Variables** (in Vercel Dashboard):
   - Go to your project settings
   - Add all variables from your `.env` file

**Done!** Your app is live at `https://your-project.vercel.app`

---

## 🌊 Option 2: DigitalOcean App Platform (Recommended for PHP)

### Steps:

1. **Push to GitHub**:
   ```bash
   git init
   git add .
   git commit -m "Initial commit - Evolentra MLM Platform"
   git branch -M main
   git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO.git
   git push -u origin main
   ```

2. **Create App on DigitalOcean**:
   - Go to https://cloud.digitalocean.com/apps
   - Click "Create App"
   - Choose "GitHub" and select your repository
   - Branch: `main`

3. **Configure Settings**:
   - Environment: **PHP**
   - HTTP Port: **8080**
   - Run Command: `heroku-php-apache2`
   - Health Check: `/health.php`

4. **Add Environment Variables**:
   Go to Settings → Environment Variables and add:
   ```
   DB_TYPE=pgsql
   DB_HOST=db.mjjefeasezknphfjnzsm.supabase.co
   DB_PORT=5432
   DB_NAME=postgres
   DB_USER=postgres
   DB_PASSWORD=<GET_FROM_SUPABASE_DASHBOARD>
   VITE_SUPABASE_URL=https://mjjefeasezknphfjnzsm.supabase.co
   VITE_SUPABASE_ANON_KEY=<FROM_YOUR_ENV_FILE>
   ```

5. **Deploy**:
   - Click "Create Resources"
   - Wait 3-5 minutes
   - Your app is live!

**Domain**: `https://your-app-name.ondigitalocean.app`

---

## 🟣 Option 3: Heroku (Classic Platform)

### Steps:

1. **Install Heroku CLI**:
   - Windows: Download from https://devcenter.heroku.com/articles/heroku-cli
   - Mac: `brew install heroku/brew/heroku`
   - Linux: `curl https://cli-assets.heroku.com/install.sh | sh`

2. **Login**:
   ```bash
   heroku login
   ```

3. **Create App**:
   ```bash
   heroku create evolentra-mlm
   ```

4. **Set Environment Variables**:
   ```bash
   heroku config:set DB_TYPE=pgsql
   heroku config:set DB_HOST=db.mjjefeasezknphfjnzsm.supabase.co
   heroku config:set DB_PORT=5432
   heroku config:set DB_NAME=postgres
   heroku config:set DB_USER=postgres
   heroku config:set DB_PASSWORD=your_password_here
   heroku config:set VITE_SUPABASE_URL=https://mjjefeasezknphfjnzsm.supabase.co
   heroku config:set VITE_SUPABASE_ANON_KEY=your_anon_key_here
   ```

5. **Deploy**:
   ```bash
   git add .
   git commit -m "Deploy to Heroku"
   git push heroku main
   ```

**Domain**: `https://evolentra-mlm.herokuapp.com`

---

## 📦 Option 4: Netlify

### Steps:

1. **Install Netlify CLI**:
   ```bash
   npm install -g netlify-cli
   ```

2. **Login**:
   ```bash
   netlify login
   ```

3. **Initialize and Deploy**:
   ```bash
   netlify init
   netlify deploy --prod
   ```

4. **Set Environment Variables**:
   - Go to Netlify Dashboard
   - Site Settings → Environment Variables
   - Add all variables from `.env`

**Domain**: `https://your-site-name.netlify.app`

---

## ⚡ Option 5: GitHub Pages (Static Frontend Only)

**Note**: This works for the landing page only, not the full PHP application.

1. **Enable GitHub Pages**:
   - Go to your repo settings
   - Pages → Source → main branch
   - Save

2. **Access**:
   `https://YOUR_USERNAME.github.io/YOUR_REPO_NAME/static.html`

---

## 🔑 Getting Your Supabase Password

Your Supabase database is already set up, but you need the password:

1. Go to https://supabase.com/dashboard
2. Select your project
3. Go to Settings → Database
4. Find "Connection String" or "Database Password"
5. Copy the password

**Or reset it**:
- Settings → Database → Reset Database Password

---

## 📊 After Deployment - Verify

Visit these URLs (replace `your-domain.com` with your actual domain):

1. ✅ **Static Test**: `https://your-domain.com/static.html`
2. ✅ **PHP Test**: `https://your-domain.com/ping.php`
3. ✅ **System Info**: `https://your-domain.com/info.php`
4. ✅ **Health Check**: `https://your-domain.com/health.php`
5. ✅ **Landing Page**: `https://your-domain.com/`

All should work!

---

## 🔐 Default Admin Credentials

After deployment, create an admin account at:
```
https://your-domain.com/create_admin.php
```

Or use the defaults from your `.env`:
- **Username**: admin
- **Email**: admin@evolentra.com
- **Password**: Admin123! (change this immediately!)

---

## 🐛 Troubleshooting

### Issue: "Database connection failed"

**Solution**:
1. Check `DB_PASSWORD` is set correctly
2. Verify Supabase project is active
3. Check `/health.php` for detailed error

### Issue: "404 Not Found"

**Solution**:
1. Ensure `.htaccess` is deployed
2. Check platform supports PHP
3. Verify run command is correct

### Issue: "Page loads but no data"

**Solution**:
1. Check database has data (2 test users exist)
2. Verify RLS policies in Supabase
3. Check environment variables are set

---

## 📱 Test the Complete Flow

1. **Landing Page** → Visit homepage
2. **Register** → Create a new user account
3. **Login** → Sign in with credentials
4. **Dashboard** → View user dashboard
5. **Referral Link** → Generate and test referral link
6. **Admin Panel** → Login as admin and manage users

---

## 🎉 You're Ready!

Your Evolentra MLM Platform is fully configured and ready to deploy. Choose any platform above and follow the steps. Your Supabase database is already populated and waiting!

**Questions?** Check `/health.php` first - it shows your system status.

---

## 🚀 Fastest Path to Live

**For immediate deployment** (< 5 minutes):

```bash
# 1. Push to GitHub
git init
git add .
git commit -m "Evolentra MLM Platform"
gh repo create MLM_Evolentra --public --push --source=.

# 2. Deploy to Vercel
npm install -g vercel
vercel --prod

# 3. Add environment variables in Vercel Dashboard
# 4. Done!
```

Your app is now LIVE!
