# Railway Deployment Guide

## Prerequisites
- Railway account (free tier available)
- OpenAI API key
- GitHub repository with the code

## Step 1: Create Railway Project
1. Go to [Railway.app](https://railway.app)
2. Sign in with GitHub
3. Click "New Project"
4. Select "Deploy from GitHub repo"
5. Choose your repository

## Step 2: Configure Environment Variables
Set these in Railway dashboard under "Variables":

```
APP_NAME=Cover Letter Generator
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://your-app-name.railway.app

DB_CONNECTION=sqlite
DB_DATABASE=/app/database/database.sqlite

OPENAI_API_KEY=your_openai_api_key_here
OPENAI_BASE=https://api.openai.com
MODEL=gpt-4o-mini

LOG_CHANNEL=stack
LOG_LEVEL=info

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

FILESYSTEM_DISK=local

SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

PORT=8000

# Railway-specific variables (automatically set by Railway)
RAILWAY_PUBLIC_DOMAIN=your-app-name.railway.app
RAILWAY_PRIVATE_DOMAIN=your-app-name.railway.internal
```

## Step 3: Generate APP_KEY
Run this command locally and use the output:
```bash
php artisan key:generate --show
```

## Step 4: Deploy
1. Railway will automatically detect the `railway.json` file
2. RAILPACK builder will automatically:
   - Install PHP 8.2 and required dependencies (including poppler-utils for pdftotext)
   - Run `composer install --no-dev --optimize-autoloader`
   - Generate application key
   - Cache configurations
   - Run database migrations
   - Start the server

## Step 5: Verify Deployment
1. Check the live URL: `https://your-app-name.railway.app`
2. Test `/healthz` endpoint
3. Upload a test CV and verify functionality
4. Check security headers at [securityheaders.com](https://securityheaders.com)

## Step 6: Run Golden Tests
Execute the golden test suite against production:
```bash
php scripts/eval-production.php --url=https://your-app-name.railway.app
```

## Step 7: Verify Security Headers
Check security headers:
```bash
php scripts/verify-security-headers.php --url=https://your-app-name.railway.app
```

## Monitoring
- View logs in Railway dashboard
- Monitor `/healthz` endpoint for uptime
- Check token usage and costs in logs

## Troubleshooting
- Check Railway logs for errors
- Verify all environment variables are set
- Ensure OpenAI API key is valid
- Check database migrations completed successfully
- Verify pdftotext binary is available (installed automatically by RAILPACK)