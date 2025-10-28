# 🚀 Arkan ERP System

<div align="center">

![Laravel](https://img.shields.io/badge/Laravel-10.x-red.svg)
![PHP](https://img.shields.io/badge/PHP-8.1+-blue.svg)
![License](https://img.shields.io/badge/License-Proprietary-yellow.svg)
![Status](https://img.shields.io/badge/Status-Production-green.svg)

**Enterprise Resource Planning System for Modern Businesses**

[Documentation](#documentation) •
[Installation](#installation) •
[Features](#features) •
[Support](#support)

</div>

---

## 📖 About Arkan

Arkan is a comprehensive Enterprise Resource Planning (ERP) system built with Laravel, designed to streamline business operations across project management, task tracking, human resources, and customer relationship management.

### 🎯 Core Objectives
- **Simplify Project Management**: From initiation to delivery with full approval workflows
- **Enhance Team Collaboration**: Real-time notifications, file sharing, and communication
- **Optimize Employee Management**: Attendance, performance reviews, and gamification
- **Improve Client Relations**: Ticket system, call logs, and client tracking
- **Data-Driven Insights**: Comprehensive dashboards and analytics

---

## ✨ Key Features

### 🗂️ Project Management
- ✅ Multi-service project support
- ✅ Wasabi S3 file storage
- ✅ Secure file sharing with tokens
- ✅ Two-level approval system (Administrative & Technical)
- ✅ Project analytics and dashboards
- ✅ Smart team recommendations
- ✅ Automatic status updates

### ✅ Task Management
- ✅ Regular tasks & template tasks
- ✅ NTP-based time tracking
- ✅ Automatic pause/resume
- ✅ Task revisions and approvals
- ✅ Task transfers with points tracking
- ✅ Additional tasks (bonus system)
- ✅ Task deliveries with approvals

### 👥 Employee Management
- ✅ Attendance system (check-in/out)
- ✅ Leave requests (absence, permission, overtime)
- ✅ Hierarchical approval workflow
- ✅ Performance evaluations (4 types)
- ✅ KPI tracking
- ✅ Skill management
- ✅ Work shift scheduling
- ✅ Salary sheet processing

### 🎮 Gamification System
- ✅ Competitive seasons
- ✅ Achievement badges
- ✅ Points system
- ✅ Leaderboards
- ✅ Automatic demotion rules
- ✅ Employee competition dashboard

### 📞 CRM (Customer Relationship Management)
- ✅ Client database
- ✅ Call log tracking
- ✅ Support ticket system
- ✅ Multi-user ticket assignments
- ✅ @mentions in comments
- ✅ Client interests tracking
- ✅ CRM analytics dashboard

### 🔔 Multi-Channel Notifications
- ✅ Database notifications
- ✅ Firebase push notifications (mobile/web)
- ✅ Slack integration (channels & DMs)
- ✅ Email notifications
- ✅ Real-time broadcasting

### 🎪 Meeting Management
- ✅ Meeting scheduling (internal/client/training)
- ✅ Participant management
- ✅ Approval workflow for client meetings
- ✅ Meeting notes with @mentions
- ✅ Meeting history

---

## 🛠️ Technology Stack

### Backend
- **Framework**: Laravel 10.x
- **Language**: PHP 8.1+
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Cache**: Redis (optional)
- **Queue**: Database / Redis

### Frontend
- **Template Engine**: Blade
- **UI Framework**: Livewire
- **JavaScript**: Alpine.js
- **CSS**: Tailwind CSS
- **Charts**: Chart.js

### External Services
- **Firebase**: Cloud Messaging & Firestore
- **Slack**: Workspace integration
- **Wasabi**: S3-compatible storage
- **NTP**: Time synchronization

### Key Packages
- **Laravel Jetstream**: Authentication & teams
- **Spatie Permission**: Role & permission management
- **Spatie Activitylog**: Activity tracking
- **PhpSpreadsheet**: Excel processing
- **Google Cloud Firestore**: Document storage

---

## 📋 System Requirements

### Server Requirements
- PHP >= 8.1
- MySQL >= 5.7 or MariaDB >= 10.3
- Composer >= 2.x
- Node.js >= 16.x
- Git

### PHP Extensions
```
- BCMath
- Ctype
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO
- Tokenizer
- XML
- GD or Imagick
- Redis (optional)
```

### Web Server
- **Nginx** (recommended) or Apache
- SSL Certificate (for production)

---

## 🚀 Installation

### 1. Clone Repository
```bash
git clone <repository-url>
cd arkan-last-version-master
```

### 2. Install Dependencies
```bash
# PHP dependencies
composer install

# JavaScript dependencies
npm install
```

### 3. Environment Configuration
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Configure Environment Variables
Edit `.env` file with your settings:

```env
# Application
APP_NAME=Arkan
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=arkan_db
DB_USERNAME=db_user
DB_PASSWORD=db_password

# Firebase Configuration
FIREBASE_CREDENTIALS=path/to/firebase-credentials.json
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_SERVER_KEY=your-server-key

# Slack Integration
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/...
SLACK_BOT_TOKEN=xoxb-...

# Wasabi S3 Storage
WASABI_ACCESS_KEY_ID=your-access-key
WASABI_SECRET_ACCESS_KEY=your-secret-key
WASABI_BUCKET=your-bucket-name
WASABI_REGION=us-east-1
WASABI_ENDPOINT=https://s3.wasabisys.com

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"

# Queue Configuration
QUEUE_CONNECTION=database
```

### 5. Database Setup
```bash
# Run migrations
php artisan migrate

# Seed database (optional)
php artisan db:seed
```

### 6. Storage Links
```bash
# Create symbolic link for storage
php artisan storage:link
```

### 7. Build Assets
```bash
# Development
npm run dev

# Production
npm run build
```

### 8. Queue Worker (Production)
```bash
# Using Supervisor (recommended)
# Create supervisor configuration file
sudo nano /etc/supervisor/conf.d/arkan-worker.conf
```

Add:
```ini
[program:arkan-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/worker.log
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start arkan-worker:*
```

### 9. Scheduler Setup
Add to crontab:
```bash
crontab -e
```

Add line:
```
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔧 Configuration

### Firebase Setup

1. **Create Firebase Project**:
   - Go to [Firebase Console](https://console.firebase.google.com)
   - Create new project
   - Enable Cloud Messaging

2. **Download Credentials**:
   - Download `firebase-credentials.json`
   - Place in project root or specify path in `.env`

3. **Get Server Key**:
   - Go to Project Settings → Cloud Messaging
   - Copy Server Key to `.env`

### Slack Setup

1. **Create Slack App**:
   - Go to [Slack API](https://api.slack.com/apps)
   - Create new app
   - Enable Incoming Webhooks

2. **Configure Webhooks**:
   - Add webhook URLs to `.env`
   - Test connection

### Wasabi S3 Setup

1. **Create Wasabi Account**:
   - Sign up at [Wasabi](https://wasabi.com)
   - Create bucket

2. **Get Credentials**:
   - Generate Access Key and Secret Key
   - Add to `.env`

---

## 📚 Documentation

Comprehensive documentation is available:

- **[Project Documentation](PROJECT_DOCUMENTATION.md)**: Complete system overview
- **[Database Schema](DATABASE_SCHEMA.md)**: Database structure and relationships
- **[API Documentation](API_DOCUMENTATION.md)**: Routes and endpoints reference

---

## 🎮 Usage

### First Login

1. **Access Application**:
   ```
   https://your-domain.com
   ```

2. **Default Admin Credentials** (if seeded):
   ```
   Email: admin@arkan.com
   Password: password
   ```
   ⚠️ **Change immediately after first login!**

### Basic Workflow

#### Creating a Project
1. Navigate to **Projects** → **Create New**
2. Fill project details (name, client, services)
3. Select team members
4. Set dates and budget
5. Submit

#### Assigning a Task
1. Go to **Tasks** → **Create New**
2. Fill task details
3. Assign to user(s)
4. Set priority and deadline
5. Submit

#### Managing Attendance
1. Employees check in via **Attendance** page
2. System tracks time with NTP sync
3. Auto check-out at end of day
4. View reports in HR Dashboard

---

## 🔐 Security

### Best Practices Implemented
- ✅ Password hashing (bcrypt)
- ✅ CSRF protection
- ✅ XSS prevention
- ✅ SQL injection prevention
- ✅ Rate limiting
- ✅ Two-factor authentication
- ✅ Secure file uploads
- ✅ Encrypted sensitive data
- ✅ Activity logging
- ✅ Role-based access control

### Security Recommendations
1. Use strong passwords
2. Enable 2FA for all users
3. Keep Laravel and packages updated
4. Use HTTPS in production
5. Regular database backups
6. Monitor activity logs
7. Restrict admin access
8. Use environment-specific configurations

---

## 🧪 Testing

### Run Tests
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

---

## 🐛 Troubleshooting

### Common Issues

#### Queue Jobs Not Processing
```bash
# Check queue worker status
sudo supervisorctl status arkan-worker:*

# Restart workers
sudo supervisorctl restart arkan-worker:*

# Clear failed jobs
php artisan queue:flush
```

#### Cache Issues
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

#### Storage Permission Issues
```bash
# Fix storage permissions
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage
```

#### Database Connection Issues
```bash
# Test connection
php artisan db:show

# Check migrations status
php artisan migrate:status
```

---

## 📈 Performance Optimization

### Production Optimizations
```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize

# Optimize for production
php artisan optimize
```

### Database Optimizations
- Add indexes to frequently queried columns
- Use eager loading to prevent N+1 queries
- Regular database maintenance
- Consider Redis for caching

---

## 🔄 Updates & Maintenance

### Updating the Application
```bash
# Pull latest changes
git pull origin main

# Update dependencies
composer update
npm update

# Run migrations
php artisan migrate

# Clear caches
php artisan optimize:clear

# Rebuild assets
npm run build

# Restart queue workers
sudo supervisorctl restart arkan-worker:*
```

### Backup Strategy
1. **Database Backup**: Daily automated backups
2. **File Backup**: Weekly backup of uploaded files
3. **Configuration Backup**: Version control for configs
4. **Testing**: Verify backups regularly

---

## 📊 Monitoring

### Application Logs
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Queue worker logs
tail -f storage/logs/worker.log

# Nginx/Apache logs
tail -f /var/log/nginx/error.log
```

### Performance Monitoring
- Monitor database queries
- Track API response times
- Monitor queue job processing
- Track memory usage
- Monitor disk space

---

## 🤝 Contributing

### Development Workflow
1. Fork the repository
2. Create feature branch
3. Make changes
4. Write tests
5. Submit pull request

### Coding Standards
- Follow PSR-12 coding standards
- Write meaningful comments
- Document public methods
- Use type hints
- Follow Laravel best practices

---

## 📄 License

This project is proprietary software. All rights reserved.

Unauthorized copying, modification, distribution, or use of this software,
via any medium, is strictly prohibited.

---

## 📞 Support

### Getting Help
- **Documentation**: See docs folder
- **Issues**: Report bugs via issue tracker
- **Email**: support@arkan.com

### Professional Support
For enterprise support, customization, or training:
- Email: enterprise@arkan.com
- Website: https://arkan.com

---

## 🎯 Roadmap

### Upcoming Features
- [ ] Mobile applications (iOS & Android)
- [ ] Advanced AI recommendations
- [ ] Video conferencing integration
- [ ] GraphQL API
- [ ] Multi-language support
- [ ] Dark mode
- [ ] Advanced reporting tools
- [ ] Automated testing suite
- [ ] More third-party integrations

---

## 🙏 Credits

### Built With
- [Laravel](https://laravel.com) - PHP Framework
- [Laravel Jetstream](https://jetstream.laravel.com) - Authentication
- [Spatie Packages](https://spatie.be) - Laravel packages
- [Livewire](https://laravel-livewire.com) - Dynamic UI
- [Alpine.js](https://alpinejs.dev) - JavaScript framework
- [Tailwind CSS](https://tailwindcss.com) - CSS framework

---

## 📸 Screenshots

### Dashboard
![Dashboard](docs/screenshots/dashboard.png)

### Project Management
![Projects](docs/screenshots/projects.png)

### Task Tracking
![Tasks](docs/screenshots/tasks.png)

---

<div align="center">

**Built with ❤️ for Modern Businesses**

© 2024 Arkan ERP. All rights reserved.

[⬆ Back to Top](#-arkan-erp-system)

</div>

