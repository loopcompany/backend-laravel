# راهنمای انتقال Laravel به سرور ابری ابر آروان

این راهنما برای پروژه backend-laravel است. نسخه فعلی روی cPanel پارس وب سالم می‌ماند؛ سرور جدید جدا ساخته و با زیردامنه تست می‌شود و فقط در پایان DNS تغییر می‌کند.

## اصل مهم: cPanel را نگه دار

- فایل‌ها، دیتابیس، دامنه و Cron فعلی cPanel را حذف یا overwrite نکن.
- IP فعلی cPanel را یادداشت کن؛ برای rollback لازم است.
- سرور جدید را با IP یا زیردامنه جدا تست کن.
- در cutover فقط A record را تغییر بده.

ابر آروان امکاناتی مانند Cloud Server، فایروال، شبکه عمومی/خصوصی و snapshot دارد؛ قبل از تغییرات مهم snapshot بگیر. [معرفی زیرساخت ابری ابر آروان](https://arvan.netlify.app/)

## ۱. اطلاعات اولیه

این موارد را خارج از Git یادداشت کن:

~~~text
دامنه اصلی: example.com
دامنه API: api.example.com
دامنه پنل کاربر: user-panel.example.com
IP فعلی cPanel: OLD_CPANEL_IP
نام دیتابیس و کاربر دیتابیس: ...
Document Root فعلی: ...
Repository: https://github.com/loopcompany/backend-laravel.git
~~~

این موارد نباید commit شوند:

- .env و APP_KEY
- Firebase Service Account JSON
- رمز دیتابیس، درگاه، SMS، SMTP و API keyها
- dump دیتابیس و فایل‌های آپلودی

## ۲. backup از cPanel

### MySQL/MariaDB

~~~bash
mkdir -p ~/backups/loop
mysqldump --single-transaction --routines --triggers \
  -u DB_USER -p DB_NAME > ~/backups/loop/db-$(date +%F-%H%M).sql
gzip ~/backups/loop/db-*.sql
sha256sum ~/backups/loop/db-*.sql.gz
~~~

به‌جای DB_USER و DB_NAME مقادیر واقعی cPanel را بگذار. اگر mysqldump نداری، از phpMyAdmin گزینه Export با فرمت SQL بگیر.

### SQLite

compose فعلی پروژه به‌صورت پیش‌فرض SQLite دارد. هنگام کم‌ترافیک یا بعد از توقف write:

~~~bash
cp database/database.sqlite ~/backups/loop/database-$(date +%F-%H%M).sqlite
sha256sum ~/backups/loop/database-*.sqlite
~~~

یک SQLite را هم‌زمان از دو سرور writable نکن.

### فایل‌های آپلودی

~~~bash
tar -czf ~/backups/loop/storage-public-$(date +%F).tar.gz storage/app/public
sha256sum ~/backups/loop/storage-public-*.tar.gz
~~~

مسیر واقعی فایل‌های آپلودی cPanel را در صورت تفاوت جایگزین کن. logهای قدیمی را برای بررسی نگه دار، ولی لازم نیست به سرور جدید منتقل شوند.

## ۳. ساخت سرور ابر آروان

در پنل آروان:

1. Cloud Server با Ubuntu LTS بساز.
2. IPv4 عمومی و کلید SSH اختصاص بده.
3. پورت 22 را فقط از IP شخصی خودت باز کن.
4. پورت‌های 80 و 443 را عمومی باز کن.
5. پورت‌های 3306، 6379 و 8000 را عمومی نکن.

برای شروع ۲ vCPU، چهار GB RAM و دیسک متناسب با uploadها نقطه شروع است؛ اندازه نهایی را با مصرف واقعی تنظیم کن.

## ۴. SSH امن

~~~bash
ssh root@NEW_SERVER_IP
adduser deploy
usermod -aG sudo deploy
install -d -m 700 /home/deploy/.ssh
~~~

کلید public خودت را در /home/deploy/.ssh/authorized_keys بگذار:

~~~bash
chmod 700 /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys
chown -R deploy:deploy /home/deploy/.ssh
ssh deploy@NEW_SERVER_IP
~~~

ورود با deploy را از Terminal جدید تست کن؛ فقط بعد از موفقیت دسترسی root و password را ببند:

~~~bash
sudo tee /etc/ssh/sshd_config.d/99-loop.conf >/dev/null <<'EOF'
PermitRootLogin no
PasswordAuthentication no
EOF
sudo sshd -t
sudo systemctl reload ssh
~~~

فایروال را با IP عمومی خودت تنظیم کن:

~~~bash
sudo apt update
sudo apt install -y ufw
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow from MY_PUBLIC_IP to any port 22 proto tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
sudo ufw status verbose
~~~

## ۵. نصب Docker و Compose

روش رسمی Docker برای Ubuntu را استفاده کن. [نصب رسمی Docker روی Ubuntu](https://docs.docker.com/engine/install/ubuntu/) و [نصب رسمی Compose](https://docs.docker.com/compose/install/linux/)

~~~bash
sudo apt update
sudo apt install -y ca-certificates curl
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | sudo tee /etc/apt/sources.list.d/docker.list >/dev/null
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo usermod -aG docker "$USER"
newgrp docker
docker run hello-world
docker compose version
~~~

پورت 8000 را فقط روی localhost bind کن و Nginx را جلوی آن بگذار؛ [هشدار رسمی Docker درباره firewall و published ports](https://docs.docker.com/engine/install/ubuntu/).

## ۶. دریافت پروژه

برای repository خصوصی، Deploy Key فقط‌خواندنی بساز:

~~~bash
sudo mkdir -p /opt/loop
sudo chown deploy:deploy /opt/loop
cd /opt/loop
ssh-keygen -t ed25519 -f ~/.ssh/loop_deploy_key -C loop-production
cat ~/.ssh/loop_deploy_key.pub
~~~

کلید public را در GitHub در Settings -> Deploy keys اضافه کن و سپس:

~~~bash
ssh-keyscan github.com >> ~/.ssh/known_hosts
GIT_SSH_COMMAND='ssh -i ~/.ssh/loop_deploy_key' git clone git@github.com:loopcompany/backend-laravel.git app
cd /opt/loop/app
cp .env.example .env
nano .env
~~~

مقادیر اصلی production:

~~~dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com
APP_KEY=همان_APP_KEY_فعلی
LOG_CHANNEL=stderr_json
LOG_LEVEL=info
LOG_REQUESTS=true
LOG_DAILY_DAYS=14
FIREBASE_ENABLED=true
FIREBASE_PROJECT_ID=loop-1efb6
FIREBASE_CREDENTIALS_PATH=/run/secrets/firebase-service-account.json
~~~

APP_KEY را عوض نکن؛ اگر داده رمز‌شده داری، تغییر آن داده‌ها را خراب می‌کند.

## ۷. Firebase و دیتابیس

فایل Firebase خارج از repository باشد:

~~~bash
sudo mkdir -p /opt/loop/secrets
sudo nano /opt/loop/secrets/firebase-service-account.json
sudo chown root:deploy /opt/loop/secrets/firebase-service-account.json
sudo chmod 640 /opt/loop/secrets/firebase-service-account.json
~~~

اگر compose فعلی secret را mount نمی‌کند، آن را به‌صورت volume read-only یا secret محیط اجرا mount کن؛ JSON را داخل Git، public یا log نگذار.

برای MySQL/MariaDB:

~~~dotenv
DB_CONNECTION=mysql
DB_HOST=DB_PRIVATE_IP_OR_HOST
DB_PORT=3306
DB_DATABASE=loop
DB_USERNAME=loop_app
DB_PASSWORD=یک_رمز_قوی
~~~

پورت دیتابیس را عمومی نکن. برای SQLite، backup database.sqlite را قبل از اولین migration به volume دیتابیس منتقل کن و فقط یک سرور را writable نگه دار. برای سایت دارای سفارش و پرداخت، MySQL/MariaDB معمولاً مناسب‌تر از SQLite است.

## ۸. بالا آوردن نسخه آزمایشی

~~~bash
cd /opt/loop/app
git status
docker compose build app
docker compose up -d
docker compose ps
docker compose logs -f --tail=100 app
~~~

در Terminal دیگر:

~~~bash
curl -i http://127.0.0.1:8000/up
docker compose exec app php artisan about
docker compose exec app php artisan migrate:status
docker compose exec app php artisan route:list
~~~

entrypoint این پروژه migration و storage:link را انجام می‌دهد؛ وضعیت را بررسی کن:

~~~bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan storage:link
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan config:cache
docker compose exec app php artisan view:cache
~~~

## ۹. Nginx و reverse proxy

~~~bash
sudo apt update
sudo apt install -y nginx
sudo nano /etc/nginx/sites-available/loop
~~~

محتوا:

~~~nginx
server {
    listen 80;
    listen [::]:80;
    server_name example.com www.example.com api.example.com user-panel.example.com;
    client_max_body_size 50M;

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Request-ID $request_id;
        proxy_read_timeout 120s;
    }
}
~~~

~~~bash
sudo ln -s /etc/nginx/sites-available/loop /etc/nginx/sites-enabled/loop
sudo nginx -t
sudo systemctl reload nginx
curl -I -H 'Host: example.com' http://127.0.0.1
~~~

Laravel باید از public/index.php سرو شود؛ در این پروژه container روی localhost گوش می‌دهد و Nginx جلوی آن است. [راهنمای deployment رسمی Laravel](https://laravel.com/framework/docs/master/deployment)

## ۱۰. تست با زیردامنه و DNS

قبل از تغییر دامنه اصلی:

~~~text
cloud-test.example.com  A  NEW_SERVER_IP
~~~

دامنه اصلی هنوز روی cPanel بماند. با زیردامنه login، logout، سفارش، پرداخت، upload، Firebase و admin را تست کن.

۲۴ ساعت قبل از cutover، TTL را روی 300 بگذار. سپس A recordها را تغییر بده:

~~~text
example.com              A  NEW_SERVER_IP
www.example.com          A  NEW_SERVER_IP
api.example.com          A  NEW_SERVER_IP
user-panel.example.com  A  NEW_SERVER_IP
~~~

رکوردهای قدیمی را حذف نکن:

~~~bash
dig +short example.com
dig +short api.example.com
nslookup user-panel.example.com
~~~

برای migration کم‌ریسک، nameserver را تغییر نده و فعلاً همان DNS provider را نگه دار؛ فقط A record را عوض کن. اگر DNS را به آروان منتقل می‌کنی، nameserver اعلام‌شده در پنل آروان را در registrar دامنه تنظیم کن.

## ۱۱. SSL

بعد از رسیدن DNS به سرور جدید:

~~~bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d example.com -d www.example.com -d api.example.com -d user-panel.example.com
sudo certbot renew --dry-run
~~~

اگر CDN/proxy آروان فعال است، ابتدا origin را مستقیم با HTTPS تست کن و بعد proxy را فعال کن.

## ۱۲. cutover نهایی

1. در cPanel maintenance کوتاه یا توقف write سفارش/پرداخت بگذار.
2. آخرین dump دیتابیس و archive فایل‌ها را بگیر.
3. آن‌ها را روی سرور جدید restore کن.
4. migration را با force اجرا کن.
5. .env و Firebase JSON را verify کن.
6. DNS را عوض کن.
7. از اینترنت موبایل و یک شبکه دیگر login و health check بگیر.

چک‌لیست: /up، login/logout کاربر و technician، پروفایل، انتخاب جامع/سیستماتیک، ثبت سفارش، callback پرداخت، upload، Firebase، admin و queue.

## ۱۳. مشاهده لاگ‌های جدید

در Docker:

~~~bash
docker compose logs -f --tail=200 app
docker compose logs --since=10m app
~~~

در file channel:

~~~bash
tail -F storage/logs/app.json-$(date +%F).log
tail -F storage/logs/security.json-$(date +%F).log
tail -F storage/logs/payments.json-$(date +%F).log
tail -F storage/logs/firebase.json-$(date +%F).log
tail -F storage/logs/engagement.json-$(date +%F).log
~~~

هر response یک X-Request-ID دارد:

~~~bash
docker compose logs app | grep REQUEST_ID_HERE
~~~

بدنه request، query string، password، token، cookie و Firebase private key عمداً log نمی‌شوند یا redact می‌شوند.

## ۱۴. queue و scheduler

~~~bash
docker compose exec app supervisorctl status
crontab -e
~~~

اگر scheduler استفاده می‌شود:

~~~cron
* * * * * cd /opt/loop/app && docker compose exec -T app php artisan schedule:run >> /var/log/loop-scheduler.log 2>&1
~~~

## ۱۵. deploy نسخه‌های بعدی

~~~bash
cd /opt/loop/app
git fetch --prune
git checkout main
git pull --ff-only
docker compose exec app php artisan down --render='errors::503' --retry=60
docker compose build app
docker compose up -d
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan config:cache
docker compose exec app php artisan up
docker compose ps
~~~

قبل از هر deploy backup دیتابیس بگیر. migrationهای destructive را به migrationهای سازگار با نسخه قدیم و جدید تقسیم کن.

## ۱۶. rollback به cPanel

اگر مشکل جدی شد:

1. سرور جدید را موقتاً maintenance کن تا write جدید کم شود.
2. A recordها را به OLD_CPANEL_IP برگردان.
3. cache DNS و SSL را بررسی کن.
4. خطا را از logهای Docker و Nginx پیدا کن.

تغییر DNS دیتابیس را rollback نمی‌کند. اگر بعد از cutover سفارش یا پرداخت جدید روی سرور ابری ثبت شده باشد، دیتابیس cPanel قدیمی است؛ بدون بررسی و reconcile کردن داده‌ها کورکورانه برنگرد.

## ۱۷. backup و نگهداری

- snapshot قبل از migration و releaseهای بزرگ
- dump روزانه دیتابیس با حداقل ۷ نسخه نگهداری
- backup جداگانه storage/app/public
- تست restore ماهانه
- alert برای disk، RAM، 5xx و queue
- secret خارج از Git، log و Docker image
- بازبینی SSH و Deploy Key

## خطاهای رایج

### 502 Bad Gateway

~~~bash
docker compose ps
docker compose logs --tail=200 app
ss -lntp | grep 8000
sudo nginx -t
~~~

### session/login خراب است

APP_KEY، APP_URL، SESSION_DOMAIN، HTTPS و header X-Forwarded-Proto را بررسی کن.

### Firebase خراب است

مسیر داخل container، FIREBASE_PROJECT_ID=loop-1efb6، دسترسی JSON و channel firebase را بررسی کن.

### فایل‌های قبلی دیده نمی‌شوند

~~~bash
docker compose exec app php artisan storage:link
~~~

این command لینک public/storage را به storage عمومی Laravel وصل می‌کند؛ [مستند رسمی filesystem لاراول](https://laravel.com/framework/docs/filesystem).

## موفقیت migration

مهاجرت فقط وقتی تمام‌شده است که backup و restore تست شده باشد، cPanel حداقل یک روز سالم و آماده fallback بماند، login/سفارش/پرداخت/Firebase/admin تست شوند، DNS به IP جدید resolve شود، APP_DEBUG=false باشد و logها با request ID قابل جست‌وجو باشند.
