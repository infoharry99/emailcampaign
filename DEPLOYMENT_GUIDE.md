# AI Marketing Email Manager (Laravel + AWS SES + OpenAI)

A high-performance, compliant AI Marketing Email Manager built in Laravel with Amazon SES (`ap-south-1` Mumbai), Redis queue workers, OpenAI API personalization, and automated deliverability compliance.

---

## 1. Quick Start & Credentials

### Default Admin Login
- **URL**: `http://localhost:8000/login` (or your domain `/login`)
- **Email**: `admin@thenexteck.com`
- **Password**: `password`

### Database & Environment Setup
Ensure your `.env` file contains your configuration:

```env
APP_NAME="AI Marketing Email Manager"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://marketing.thenexteck.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=marketing_db
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Queue System
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Amazon SES (Mumbai ap-south-1)
AWS_ACCESS_KEY_ID=your_aws_access_key
AWS_SECRET_ACCESS_KEY=your_aws_secret_key
AWS_DEFAULT_REGION=ap-south-1
AWS_SES_CONFIGURATION_SET=marketing-tracking
MAIL_FROM_ADDRESS=noreply@marketing.thenexteck.com
MAIL_FROM_NAME="TheNexteck Marketing"
AWS_SES_SENDING_RATE=14
AWS_SES_DAILY_QUOTA=50000

# OpenAI API
OPENAI_API_KEY=sk-proj-...
OPENAI_MODEL=gpt-4o-mini
OPENAI_MAX_TOKENS=450
OPENAI_TEMPERATURE=0.7
```

---

## 2. GoDaddy DNS Configuration for `marketing.thenexteck.com`

In your GoDaddy DNS Management panel for `thenexteck.com`, create the following DNS records:

### 1. DKIM (DomainKeys Identified Mail) - CNAME Records
From your AWS SES Console (under Verified Identities -> `marketing.thenexteck.com` -> DKIM):
- CNAME 1: `token1._domainkey.marketing` -> `token1.dkim.amazonses.com`
- CNAME 2: `token2._domainkey.marketing` -> `token2.dkim.amazonses.com`
- CNAME 3: `token3._domainkey.marketing` -> `token3.dkim.amazonses.com`

### 2. Custom MAIL FROM Domain - MX & SPF
- Subdomain: `bounces.marketing`
- **MX Record**:
  - Host: `bounces.marketing`
  - Value: `feedback-smtp.ap-south-1.amazonses.com`
  - Priority: `10`
- **TXT Record (SPF)**:
  - Host: `bounces.marketing`
  - Value: `"v=spf1 include:amazonses.com ~all"`

### 3. DMARC Policy (Protects Sender Reputation)
- **TXT Record**:
  - Host: `_dmarc.marketing`
  - Value: `"v=DMARC1; p=quarantine; pct=100; rua=mailto:dmarc-reports@marketing.thenexteck.com"`

---

## 3. Hostinger VPS Deployment & Supervisor Setup

### Step 1: Install Redis & Supervisor
```bash
sudo apt update
sudo apt install redis-server supervisor php8.2-cli php8.2-curl php8.2-mysql php8.2-mbstring php8.2-xml -y
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

### Step 2: Set Permissions
```bash
cd /var/www/EMailsytem
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Step 3: Run Database Migrations & Seeds
```bash
php artisan migrate --force
php artisan db:seed --force
```

### Step 4: Configure Supervisor for Queue Workers
Create `/etc/supervisor/conf.d/marketing-worker.conf`:

```ini
[program:marketing-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/EMailsytem/artisan queue:work redis --queue=marketing,default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/EMailsytem/storage/logs/worker.log
stopwaitsecs=3600
```

Start Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

---

## 4. AWS SES Production Access Checklist

1. **Move Out of SES Sandbox**:
   - Navigate to **AWS Console** -> **Amazon SES** -> **Account dashboard** -> **Request production access**.
   - Select Mail type: **Transactional / Marketing**.
   - Website URL: `https://thenexteck.com`.
   - Describe opt-in process: Explicit affirmative consent via website forms, strict suppression list enforcement, RFC 8058 one-click unsubscribe links.
2. **Create SES Configuration Set**:
   - Create set named: `marketing-tracking`.
   - Enable Event Publishing to Amazon SNS topic: `ses-marketing-events`.
3. **Configure SNS Webhook Subscription**:
   - Create HTTPS Subscription to: `https://marketing.thenexteck.com/webhooks/aws/ses`.
   - This endpoint automatically auto-confirms subscriptions and records deliveries, hard bounces, and spam complaints.
