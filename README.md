# Mentah Laravel Live Panel

Panel admin Laravel untuk mengelola flow login user (OTP, password, device approval, upload dokumen) dengan kontrol via **Telegram**, plus laporan traffic (visitor & block logs).

## Fitur utama

- **Admin panel** — Dashboard traffic, Login logs, Access sessions, Settings
- **Flow user** — Waiting → OTP/AUTH → Password → Device → Document → Logout
- **Telegram** — Notifikasi login/OTP/review, tombol kontrol inline (webhook atau polling)
- **Keamanan** — Anti-bot (UA), bot IP blocklist, anti-VPN/proxy, block ISP/ASN, wrong entry param, one-time IP redirect
- **Traffic log** — Visitor (Real) + block reason (Bot, VPN, ISP, Wrong Parameter, One-time)

## Persyaratan

| Software | Versi |
|----------|--------|
| PHP | 8.3+ (extensions: mbstring, openssl, pdo, tokenizer, xml, curl, fileinfo) |
| Composer | 2.x |
| Node.js | 18+ |
| Database | MySQL 8 / MariaDB (production) atau SQLite (dev) |
| Web server | Nginx + PHP-FPM, atau `php artisan serve` (dev) |

Opsional:

- **Cloudflare Tunnel** — HTTPS untuk domain tanpa buka port 443
- **API keys** — ipapi.is, proxycheck.io, AbuseIPDB (Settings → Keamanan)

### Instalasi di aaPanel

Lihat **[INSTALL_AAPANEL.md](INSTALL_AAPANEL.md)** — panduan lengkap + script:

```bash
bash scripts/install-aapanel.sh
```

## Instalasi

### 1. Clone repository

```bash
git clone git@github.com:younglord420/Mentah-Laravel-Live-Panel.git
cd Mentah-Laravel-Live-Panel
```

### 2. Install dependencies

```bash
composer install
npm install
npm run build
```

Atau satu perintah:

```bash
composer run setup
```

### 3. Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
APP_NAME="Live Panel"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-kamu.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=your_password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

> **Penting:** `APP_URL` harus **HTTPS** jika pakai Telegram webhook. Kalau masih HTTP, gunakan **polling** (Settings → Telegram).

### 4. Database

```bash
# Buat database MySQL dulu, lalu:
php artisan migrate --force
php artisan db:seed
```

Default admin setelah seed:

| Field | Value |
|-------|--------|
| URL | `/admin` |
| Email | `admin@example.com` |
| Password | `password` |

**Ganti password admin segera setelah login pertama.**

### 5. Permission storage

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache   # sesuaikan user web server
```

### 6. Upload file (PHP)

Untuk upload dokumen (max 10MB di app), set di PHP:

```ini
upload_max_filesize = 20M
post_max_size = 25M
```

Contoh file: `/etc/php/8.3/fpm/conf.d/99-uploads.ini` lalu restart PHP-FPM.

### 7. Scheduler (wajib untuk Telegram polling & bot IP sync)

Tambahkan cron:

```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler menjalankan:

- `telegram:poll` — setiap menit (jika tidak pakai webhook)
- `bot-ip-blocklist:sync` — setiap 6 jam

Atau jalankan manual untuk tes:

```bash
php artisan telegram:poll
php artisan bot-ip-blocklist:sync
```

### 8. Jalankan aplikasi

**Development:**

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

**Production:** arahkan Nginx ke `public/`, contoh:

```nginx
root /path/to/project/public;
index index.php;
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
location ~ \.php$ {
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    include fastcgi_params;
}
```

## Konfigurasi awal (Admin)

Login ke `/admin` → **Settings**:

### Tab Umum

- **Entry parameter** — URL masuk user, contoh `?login`
- **Fallback redirect** — redirect jika parameter salah
- **One-time IP** — redirect IP yang sudah selesai upload document

### Tab Keamanan

- Anti-bot, bot IP blocklist, anti-VPN, block ISP
- API keys: ipapi.is, proxycheck.io, AbuseIPDB
- Sync bot IP blocklist & kelola IP blacklist manual

### Tab Telegram

1. Isi **Bot Token** & **Chat ID** (channel: jadikan bot admin → **Deteksi Chat ID**)
2. **Simpan**
3. HTTPS aktif → **Set Webhook**
4. HTTP saja → **Aktifkan Polling** (+ cron scheduler)

### Tab Data

- Kelola **One-time IP** (hapus per IP atau clear semua)

## Flow user (ringkas)

1. User buka URL entry: `https://domain.com/?login` (sesuai setting)
2. Login email + password → masuk waiting
3. Admin/Telegram arahkan ke OTP, AUTH, Password, Device, Document
4. Setelah upload document → IP dicatat one-time → redirect

## Cloudflare Tunnel (opsional)

Jika pakai domain + tunnel:

```bash
# Setelah cloudflared login & tunnel dibuat
bash scripts/cloudflare-tunnel-marlomail.sh
```

Pastikan `APP_URL=https://domain-kamu.com` lalu set Telegram webhook di Settings.

## Perintah berguna

```bash
php artisan migrate --force          # migrate database
php artisan config:cache             # cache config (production)
php artisan view:clear               # clear view cache
php artisan telegram:diagnose        # cek koneksi Telegram
php artisan telegram:poll            # poll update Telegram manual
```

## Struktur penting

```
app/Http/Middleware/     # BlockBots, BlockVpn, BlockIsp, dll.
app/Services/            # TelegramService, IpIntelService
app/Support/             # TrafficLog, BlockLogger, VisitorLogger
resources/views/admin/   # Dashboard, Access, Settings
routes/web.php           # Routes admin & user flow
```

## Keamanan

- Jangan commit file `.env`
- Ganti password admin default setelah install
- Simpan Bot Token Telegram hanya di server
- Production: `APP_DEBUG=false`

## Lisensi

MIT
