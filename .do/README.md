# DigitalOcean App Platform Configuration

This directory contains configuration files for deploying to DigitalOcean App Platform.

## Files

### `app.yaml`
Main configuration file for DigitalOcean App Platform. Defines:
- PHP runtime environment
- MySQL database component
- Environment variables structure
- Health check configuration
- Build and run commands

## Quick Reference

### Update App Configuration

After modifying `app.yaml`, you can apply changes:

1. **Via Dashboard:**
   - Go to your app in DigitalOcean
   - Settings → App Spec
   - Edit and save

2. **Via CLI (doctl):**
   ```bash
   # Install doctl
   snap install doctl
   
   # Authenticate
   doctl auth init
   
   # Update app
   doctl apps update YOUR_APP_ID --spec .do/app.yaml
   ```

### Environment Variables

Set in DigitalOcean dashboard under:
**App → Settings → Environment Variables**

Required variables are listed in `.env.production.example`

### Database Connection

Database credentials are automatically injected as:
- `DATABASE_URL` - Full connection string
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` - Individual components

The app uses `lib/DatabaseConfig.php` to handle both formats.

### Health Checks

Health check endpoint: `/health.php`

Returns JSON with:
- Application status
- Database connectivity
- PHP version
- Required extensions

### Logs

View logs in DigitalOcean dashboard:
**App → Runtime Logs**

Or via CLI:
```bash
doctl apps logs YOUR_APP_ID --type run
```

### Scaling

Adjust in DigitalOcean dashboard:
**App → Settings → Resources → Edit Plan**

Available plans:
- Basic: $5/month (512 MB RAM)
- Professional: $12/month (1 GB RAM)
- Custom: Higher tiers available

## Deployment Workflow

1. **Push to GitHub:**
   ```bash
   git add .
   git commit -m "Your changes"
   git push origin main
   ```

2. **Auto-deploy:**
   - DigitalOcean detects push
   - Runs build command
   - Deploys new version
   - Runs health checks

3. **Monitor:**
   - Check deployment status in dashboard
   - View logs for any errors
   - Verify health endpoint

## Troubleshooting

### Build Fails
- Check build logs in Activity tab
- Verify `composer.json` is valid
- Ensure all dependencies are available

### Database Connection Issues
- Verify DATABASE_URL is set
- Check database is running
- Test with `/health.php` endpoint

### App Crashes
- Check runtime logs
- Verify environment variables
- Test health check endpoint

## Additional Resources

- [App Platform Docs](https://docs.digitalocean.com/products/app-platform/)
- [App Spec Reference](https://docs.digitalocean.com/products/app-platform/reference/app-spec/)
- [PHP on App Platform](https://docs.digitalocean.com/products/app-platform/languages-frameworks/php/)
