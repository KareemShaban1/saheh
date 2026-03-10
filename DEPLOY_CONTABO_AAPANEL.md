# Clinic SaaS Deployment Guide (Contabo + aaPanel)

This guide explains how to deploy this project on a Contabo VPS using aaPanel.

## 1) Recommended Production Architecture

- `api.yourdomain.com` -> Laravel backend (`backend/`)
- `app.yourdomain.com` -> React frontend build (`dist/`)
- MySQL + Redis on the same VPS (or managed services)

This is the simplest setup with this codebase because the frontend uses `VITE_BASE_URL` for API calls.

---

## 2) Prepare Contabo VPS

Use Ubuntu 22.04 LTS (recommended).

SSH into server:

```bash
ssh root@YOUR_SERVER_IP
```

Update packages:

```bash
apt update && apt upgrade -y
```

Set timezone (optional):

```bash
timedatectl set-timezone Africa/Cairo
```

---

## 3) Install aaPanel

Install aaPanel (Ubuntu/Debian script):

```bash
URL=https://www.aapanel.com/script/install_7.0_en.sh && bash $URL
```

After install, aaPanel prints:
- panel URL
- username
- password

Open aaPanel in browser and log in.

---

## 4) Install Required Software in aaPanel

From aaPanel App Store, install:

- Nginx
- MySQL (8.x preferred)
- PHP 8.2 (or your project-required version)
- Redis
- phpMyAdmin
- Supervisor Manager (or Process Manager plugin)

Also install Composer globally if missing:

```bash
apt install composer -y
```

Install Node.js 20 LTS (outside aaPanel):

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs
```

---

## 5) Clone Project

Choose a directory (example):

```bash
mkdir -p /www/wwwroot
cd /www/wwwroot
git clone YOUR_REPO_URL clinic-saas
cd clinic-saas
```

---

## 6) Backend Deployment (Laravel API)

### 6.1 Create API site in aaPanel

Create website:
- Domain: `api.yourdomain.com`
- Root: `/www/wwwroot/clinic-saas/backend/public`
- PHP version: `8.2`

### 6.2 Configure environment

```bash
cd /www/wwwroot/clinic-saas/backend
cp .env.example .env
```

Edit `.env` for production:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://api.yourdomain.com`
- DB credentials
- Redis credentials
- Mail credentials
- Pusher credentials (if used)
- `QUEUE_CONNECTION=database` (or `redis`)

### 6.3 Install dependencies and app key

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
```

### 6.4 Database migrate/seed

```bash
php artisan migrate --force
# optional:
# php artisan db:seed --force
```

### 6.5 Permissions

```bash
chown -R www:www /www/wwwroot/clinic-saas/backend
chmod -R 775 storage bootstrap/cache
```

### 6.6 Optimize Laravel

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 7) Frontend Deployment (React/Vite)

### 7.1 Create frontend env

At project root:

```bash
cd /www/wwwroot/clinic-saas
cp .env.example .env 2>/dev/null || true
```

Create/update frontend env values (important):

```env
VITE_BASE_URL=https://api.yourdomain.com/api/v1
```

### 7.2 Build frontend

```bash
npm install
npm run build
```

This generates `dist/`.

### 7.3 Create app site in aaPanel

Create website:
- Domain: `app.yourdomain.com`
- Root: `/www/wwwroot/clinic-saas/dist`
- Static site (Nginx)

### 7.4 SPA rewrite (Nginx)

In site rewrite config:

```nginx
location / {
    try_files $uri $uri/ /index.html;
}
```

---

## 8) SSL + Domains

In aaPanel for both sites (`api` and `app`):

- Issue Let's Encrypt SSL
- Enable Force HTTPS

DNS records:
- `A api.yourdomain.com -> YOUR_SERVER_IP`
- `A app.yourdomain.com -> YOUR_SERVER_IP`

---

## 9) Queue Worker and Scheduler

### 9.1 Queue worker (Supervisor)

Create program (example):

- Name: `clinic-queue`
- Command:

```bash
php /www/wwwroot/clinic-saas/backend/artisan queue:work --sleep=3 --tries=3 --timeout=90
```

- Working dir: `/www/wwwroot/clinic-saas/backend`
- User: `www`
- Auto start: enabled

### 9.2 Laravel scheduler (cron)

Add cron job:

```bash
* * * * * cd /www/wwwroot/clinic-saas/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## 10) Optional: Build Automation (on update)

When you pull new code:

```bash
cd /www/wwwroot/clinic-saas
git pull

# frontend
npm install
npm run build

# backend
cd backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Then restart services:
- PHP-FPM (from aaPanel)
- queue worker (Supervisor restart)

---

## 11) Health Check

- Frontend: `https://app.yourdomain.com`
- API test: `https://api.yourdomain.com/api/v1/patient/home` (or a known endpoint)
- Check browser network calls use `VITE_BASE_URL`
- Verify login, file upload, chat, and role-protected dashboards

---

## 12) Common Issues

- `CORS` errors:
  - Add proper allowed origins in Laravel CORS config (`app.yourdomain.com`).
- `404 on refresh` in frontend:
  - SPA rewrite missing (`try_files ... /index.html`).
- `500` in Laravel:
  - Check `/backend/storage/logs/laravel.log`
  - Verify `.env`, DB credentials, permissions.
- Upload/media issues:
  - Ensure `storage` writable.
  - Run:
    ```bash
    php artisan storage:link
    ```
- Wrong API URL:
  - Rebuild frontend after changing `VITE_BASE_URL`.

---

## 13) Security Minimum Checklist

- Disable root SSH password login (use SSH keys).
- Enable firewall (`ufw`):
  - allow `22`, `80`, `443`.
- Keep `APP_DEBUG=false` in production.
- Rotate panel/DB credentials.
- Run regular backups (DB + `/backend/storage` + project files).

