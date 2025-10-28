# 🚀 Arkan ERP - Deployment Guide

## Overview
This guide provides detailed instructions for deploying the Arkan ERP system to production servers.

---

## 📋 Table of Contents
1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [Server Setup](#server-setup)
3. [Application Deployment](#application-deployment)
4. [Web Server Configuration](#web-server-configuration)
5. [SSL Certificate Setup](#ssl-certificate-setup)
6. [Queue Workers](#queue-workers)
7. [Scheduled Tasks](#scheduled-tasks)
8. [Performance Optimization](#performance-optimization)
9. [Monitoring & Logging](#monitoring--logging)
10. [Backup Strategy](#backup-strategy)

---

## ✅ Pre-Deployment Checklist

### Requirements Verification
- [ ] Server meets minimum requirements
- [ ] Domain name configured
- [ ] SSL certificate obtained
- [ ] Database server ready
- [ ] Redis installed (optional but recommended)
- [ ] Supervisor installed
- [ ] Git access configured
- [ ] Environment variables prepared
- [ ] Backup strategy planned

### Credentials & Keys
- [ ] Database credentials
- [ ] Firebase credentials
- [ ] Slack webhook URLs
- [ ] Wasabi S3 credentials
- [ ] SMTP credentials
- [ ] Application key generated
- [ ] API keys secured

---

## 🖥️ Server Setup

### 1. Update System
```bash
sudo apt update
sudo apt upgrade -y
```

### 2. Install Required Packages
```bash
# PHP 8.1 and extensions
sudo apt install -y php8.1-fpm php8.1-cli php8.1-common \
    php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl \
    php8.1-zip php8.1-gd php8.1-bcmath php8.1-redis \
    php8.1-intl php8.1-soap

# MySQL/MariaDB
sudo apt install -y mariadb-server

# Nginx
sudo apt install -y nginx

# Redis
sudo apt install -y redis-server

# Supervisor
sudo apt install -y supervisor

# Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 3. Configure MySQL
```bash
# Secure installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE arkan_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'arkan_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON arkan_db.* TO 'arkan_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 4. Configure Redis
```bash
# Edit Redis configuration
sudo nano /etc/redis/redis.conf

# Set password
requirepass your_redis_password

# Restart Redis
sudo systemctl restart redis-server
sudo systemctl enable redis-server
```

---

## 📦 Application Deployment

### 1. Create Application Directory
```bash
# Create directory
sudo mkdir -p /var/www/arkan
sudo chown -R $USER:www-data /var/www/arkan
cd /var/www/arkan
```

### 2. Clone Repository
```bash
# Clone from Git
git clone <repository-url> .

# Or upload files via FTP/SFTP
```

### 3. Install Dependencies
```bash
# PHP dependencies
composer install --optimize-autoloader --no-dev

# JavaScript dependencies
npm install
npm run build
```

### 4. Configure Environment
```bash
# Copy environment file
cp .env.example .env

# Edit environment variables
nano .env
```

**Production `.env` Configuration:**
```env
APP_NAME=Arkan
APP_ENV=production
APP_KEY=base64:... # Generate with php artisan key:generate
APP_DEBUG=false
APP_URL=https://your-domain.com

LOG_CHANNEL=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=arkan_db
DB_USERNAME=arkan_user
DB_PASSWORD=strong_password

BROADCAST_DRIVER=redis
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379

# Firebase
FIREBASE_CREDENTIALS=/var/www/arkan/firebase-credentials.json
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_SERVER_KEY=your-server-key

# Slack
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/...
SLACK_BOT_TOKEN=xoxb-...

# Wasabi
WASABI_ACCESS_KEY_ID=your-access-key
WASABI_SECRET_ACCESS_KEY=your-secret-key
WASABI_BUCKET=your-bucket
WASABI_REGION=us-east-1
WASABI_ENDPOINT=https://s3.wasabisys.com

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 5. Generate Application Key
```bash
php artisan key:generate
```

### 6. Run Migrations
```bash
php artisan migrate --force
```

### 7. Seed Database (if needed)
```bash
php artisan db:seed --force
```

### 8. Setup Storage
```bash
# Create symbolic link
php artisan storage:link

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 9. Optimize Application
```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize
php artisan optimize
```

---

## 🌐 Web Server Configuration

### Nginx Configuration

#### 1. Create Site Configuration
```bash
sudo nano /etc/nginx/sites-available/arkan
```

**Nginx Configuration File:**
```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com www.your-domain.com;
    
    root /var/www/arkan/public;
    index index.php index.html index.htm;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval';" always;

    # Max upload size
    client_max_body_size 100M;

    # Logging
    access_log /var/log/nginx/arkan-access.log;
    error_log /var/log/nginx/arkan-error.log;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/javascript application/json;

    # Laravel routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        
        # Increase timeouts for long-running requests
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
    }

    # Deny access to sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~ /\.ht {
        deny all;
    }

    location ~ /\.env {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|gif|png|css|js|ico|xml|svg|woff|woff2|ttf|eot)$ {
        expires 365d;
        add_header Cache-Control "public, immutable";
    }
}
```

#### 2. Enable Site
```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/arkan /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

#### 3. Configure PHP-FPM
```bash
# Edit PHP-FPM pool configuration
sudo nano /etc/php/8.1/fpm/pool.d/www.conf
```

**Recommended Settings:**
```ini
[www]
user = www-data
group = www-data
listen = /var/run/php/php8.1-fpm.sock
listen.owner = www-data
listen.group = www-data

pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

php_admin_value[error_log] = /var/log/php-fpm/www-error.log
php_admin_flag[log_errors] = on
php_admin_value[memory_limit] = 512M
php_admin_value[upload_max_filesize] = 100M
php_admin_value[post_max_size] = 100M
php_admin_value[max_execution_time] = 300
```

```bash
# Restart PHP-FPM
sudo systemctl restart php8.1-fpm
```

---

## 🔒 SSL Certificate Setup

### Using Let's Encrypt (Free)

#### 1. Install Certbot
```bash
sudo apt install -y certbot python3-certbot-nginx
```

#### 2. Obtain Certificate
```bash
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

#### 3. Auto-Renewal
```bash
# Test renewal
sudo certbot renew --dry-run

# Certbot automatically adds cron job
# Verify with:
sudo systemctl status certbot.timer
```

---

## ⚙️ Queue Workers

### 1. Create Supervisor Configuration
```bash
sudo nano /etc/supervisor/conf.d/arkan-worker.conf
```

**Supervisor Configuration:**
```ini
[program:arkan-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/arkan/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=300
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/arkan/storage/logs/worker.log
stopwaitsecs=3600
```

### 2. Start Workers
```bash
# Update Supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Start workers
sudo supervisorctl start arkan-worker:*

# Check status
sudo supervisorctl status
```

### 3. Manage Workers
```bash
# Stop workers
sudo supervisorctl stop arkan-worker:*

# Restart workers
sudo supervisorctl restart arkan-worker:*

# View logs
tail -f /var/www/arkan/storage/logs/worker.log
```

---

## ⏰ Scheduled Tasks

### Setup Cron Job
```bash
# Edit crontab for www-data user
sudo crontab -u www-data -e
```

**Add Line:**
```
* * * * * cd /var/www/arkan && php artisan schedule:run >> /dev/null 2>&1
```

### Verify Scheduler
```bash
# Check if scheduler is working
sudo tail -f /var/www/arkan/storage/logs/laravel.log
```

---

## 🚄 Performance Optimization

### 1. OPcache Configuration
```bash
sudo nano /etc/php/8.1/fpm/conf.d/10-opcache.ini
```

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=0
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1
```

### 2. Redis Configuration
```bash
sudo nano /etc/redis/redis.conf
```

```ini
maxmemory 512mb
maxmemory-policy allkeys-lru
save ""
```

### 3. Database Optimization
```sql
-- Optimize tables regularly
OPTIMIZE TABLE users, projects, tasks;

-- Add indexes
CREATE INDEX idx_user_status ON users(employee_status);
CREATE INDEX idx_project_status ON projects(status);
CREATE INDEX idx_task_status ON tasks(status);
```

---

## 📊 Monitoring & Logging

### 1. Application Logs
```bash
# Laravel logs
tail -f /var/www/arkan/storage/logs/laravel.log

# Queue worker logs
tail -f /var/www/arkan/storage/logs/worker.log
```

### 2. Web Server Logs
```bash
# Nginx access log
tail -f /var/log/nginx/arkan-access.log

# Nginx error log
tail -f /var/log/nginx/arkan-error.log
```

### 3. PHP-FPM Logs
```bash
tail -f /var/log/php8.1-fpm.log
```

### 4. System Monitoring
```bash
# Install monitoring tools
sudo apt install -y htop iotop nethogs

# Monitor resources
htop
```

### 5. Log Rotation
```bash
sudo nano /etc/logrotate.d/arkan
```

```
/var/www/arkan/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

---

## 💾 Backup Strategy

### 1. Database Backup Script
```bash
sudo nano /usr/local/bin/arkan-backup-db.sh
```

```bash
#!/bin/bash

# Configuration
DB_NAME="arkan_db"
DB_USER="arkan_user"
DB_PASS="strong_password"
BACKUP_DIR="/var/backups/arkan/database"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_backup_$DATE.sql.gz

# Delete backups older than 30 days
find $BACKUP_DIR -name "db_backup_*.sql.gz" -mtime +30 -delete

echo "Database backup completed: db_backup_$DATE.sql.gz"
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/arkan-backup-db.sh
```

### 2. Files Backup Script
```bash
sudo nano /usr/local/bin/arkan-backup-files.sh
```

```bash
#!/bin/bash

# Configuration
APP_DIR="/var/www/arkan"
BACKUP_DIR="/var/backups/arkan/files"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup storage directory
tar -czf $BACKUP_DIR/storage_backup_$DATE.tar.gz -C $APP_DIR storage

# Delete backups older than 30 days
find $BACKUP_DIR -name "storage_backup_*.tar.gz" -mtime +30 -delete

echo "Files backup completed: storage_backup_$DATE.tar.gz"
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/arkan-backup-files.sh
```

### 3. Automate Backups
```bash
# Edit crontab
sudo crontab -e
```

```
# Database backup daily at 2 AM
0 2 * * * /usr/local/bin/arkan-backup-db.sh

# Files backup weekly on Sunday at 3 AM
0 3 * * 0 /usr/local/bin/arkan-backup-files.sh
```

### 4. Offsite Backup
```bash
# Install rclone for cloud backup
curl https://rclone.org/install.sh | sudo bash

# Configure rclone (follow prompts)
rclone config

# Create sync script
sudo nano /usr/local/bin/arkan-sync-backup.sh
```

```bash
#!/bin/bash

# Sync to cloud storage
rclone sync /var/backups/arkan remote:arkan-backups --progress

echo "Backup sync completed"
```

---

## 🔄 Deployment Updates

### Zero-Downtime Deployment

#### 1. Create Deployment Script
```bash
nano /var/www/arkan/deploy.sh
```

```bash
#!/bin/bash

echo "Starting deployment..."

# Enable maintenance mode
php artisan down --message="System is being updated. Please wait..."

# Pull latest changes
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Run migrations
php artisan migrate --force

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Restart queue workers
sudo supervisorctl restart arkan-worker:*

# Disable maintenance mode
php artisan up

echo "Deployment completed successfully!"
```

```bash
# Make executable
chmod +x deploy.sh
```

#### 2. Run Deployment
```bash
cd /var/www/arkan
./deploy.sh
```

---

## 🔧 Troubleshooting

### Common Issues & Solutions

#### 1. Permission Denied Errors
```bash
sudo chown -R www-data:www-data /var/www/arkan
sudo chmod -R 775 /var/www/arkan/storage
sudo chmod -R 775 /var/www/arkan/bootstrap/cache
```

#### 2. Queue Jobs Not Processing
```bash
# Check worker status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart arkan-worker:*

# Check logs
tail -f /var/www/arkan/storage/logs/worker.log
```

#### 3. 500 Internal Server Error
```bash
# Check PHP-FPM logs
sudo tail -f /var/log/php8.1-fpm.log

# Check Nginx error logs
sudo tail -f /var/log/nginx/arkan-error.log

# Check Laravel logs
tail -f /var/www/arkan/storage/logs/laravel.log
```

#### 4. Database Connection Issues
```bash
# Test database connection
php artisan db:show

# Check MySQL service
sudo systemctl status mariadb

# Verify credentials in .env
```

#### 5. Redis Connection Issues
```bash
# Check Redis service
sudo systemctl status redis-server

# Test connection
redis-cli -a your_redis_password ping
```

---

## 📋 Post-Deployment Checklist

### Verification Steps
- [ ] Website accessible via HTTPS
- [ ] SSL certificate valid
- [ ] Database connection working
- [ ] Queue workers running
- [ ] Scheduler working
- [ ] File uploads working
- [ ] Email sending working
- [ ] Firebase notifications working
- [ ] Slack notifications working
- [ ] Backups configured
- [ ] Monitoring setup
- [ ] Performance optimized

### Security Checks
- [ ] `.env` file protected
- [ ] Directory listing disabled
- [ ] Sensitive files not accessible
- [ ] Firewall configured
- [ ] SSH keys only (no password)
- [ ] Fail2ban installed
- [ ] Regular security updates scheduled

---

**Deployment Version**: 1.0  
**Last Updated**: 2024  
**Status**: Production Ready

