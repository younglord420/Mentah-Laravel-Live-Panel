# Instalasi di aaPanel

Panduan deploy **Mentah Laravel Live Panel** di [aaPanel](https://www.aapanel.com/).

## Ringkasan

| Item | Nilai |
|------|--------|
| PHP | 8.3+ |
| Document root | `.../public` (bukan root project) |
| User web aaPanel | biasanya `www` |
| Cron | `schedule:run` setiap 1 menit |

---

## 1. Install stack (aaPanel App Store)

Install dari **App Store**:

- **Nginx**
- **MySQL 8** / MariaDB
- **PHP 8.3**
- **Composer** (opsional, bisa via terminal)
- **Node.js** (opsional, untuk `npm run build` di server)

### Extension PHP 8.3

**App Store → PHP 8.3 → Settings → Install extensions:**

`fileinfo`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `curl`, `bcmath`, `zip`

### Upload limit (upload dokumen user)

**PHP 8.3 → Settings → Configuration:**

```ini
upload_max_filesize = 20M
post_max_size = 25M
```

Save → **Restart PHP**.

---

## 2. Buat website

1. **Website → Add site**
2. Domain: `domain-kamu.com`
3. Root: `/www/wwwroot/domain-kamu.com`
4. PHP: **8.3**

### Document root ke `public`

**Website → domain → Site directory / Settings:**

```
/www/wwwroot/domain-kamu.com/public
```

Atau edit **Config** Nginx, pastikan:

```nginx
root /www/wwwroot/domain-kamu.com/public;
index index.php;

location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

---

## 3. Clone project

Via **Terminal** aaPanel atau SSH:

```bash
cd /www/wwwroot/domain-kamu.com

# Kosongkan folder default aaPanel jika perlu
# rm -rf /www/wwwroot/domain-kamu.com/*

git clone git@github.com:younglord420/Mentah-Laravel-Live-Panel.git .
# atau HTTPS:
# git clone https://github.com/younglord420/Mentah-Laravel-Live-Panel.git .
```

---

## 4. Buat database

**Database → Add database**

Catat:

- Database name
- Username
- Password

---

## 5. Jalankan script instalasi

Dari root project:

```bash
cd /www/wwwroot/domain-kamu.com
chmod +x scripts/install-aapanel.sh
bash scripts/install-aapanel.sh
```

Script akan:

- Cek PHP & Composer
- `composer install --no-dev`
- `npm run build` (jika Node tersedia)
- Buat `.env` dari `.env.example` (jika belum ada)
- `php artisan key:generate`
- `php artisan migrate --force`
- `php artisan db:seed` (opsional, konfirmasi di script)
- Set permission `storage` & `bootstrap/cache`
- Cache config/route/view (production)

### Variabel environment (opsional)

Tanpa prompt interaktif:

```bash
APP_URL=https://domain-kamu.com \
DB_DATABASE=livepanel \
DB_USERNAME=livepanel_user \
DB_PASSWORD=secret_password \
bash scripts/install-aapanel.sh --yes
```

---

## 6. Edit `.env` manual (jika belum lengkap)

```env
APP_NAME="Live Panel"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-kamu.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=livepanel
DB_USERNAME=livepanel_user
DB_PASSWORD=secret_password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Setelah edit:

```bash
php artisan config:cache
```

---

## 7. SSL (Let's Encrypt)

**Website → domain → SSL → Let's Encrypt → Apply**

Wajib jika pakai **Telegram webhook**.

---

## 8. Cron (wajib)

**Cron → Add task → Every 1 minute**

```bash
cd /www/wwwroot/domain-kamu.com && /www/server/php/83/bin/php artisan schedule:run >> /dev/null 2>&1
```

> Sesuaikan path PHP. Cek: `which php` atau lihat di **PHP 8.3 → Settings**.

Menjalankan:

- `telegram:poll` — tiap menit (tanpa webhook)
- `bot-ip-blocklist:sync` — tiap 6 jam

---

## 9. Login admin

| | |
|---|---|
| URL | `https://domain-kamu.com/admin` |
| Email | `admin@example.com` |
| Password | `password` |

**Ganti password segera setelah login.**

Lalu buka **Settings** → atur entry URL, keamanan, Telegram.

---

## 10. Telegram

1. Settings → Telegram → isi Bot Token & Chat ID
2. **Simpan**
3. HTTPS aktif → **Set Webhook**
4. Belum HTTPS → **Aktifkan Polling** + pastikan cron jalan

---

## Troubleshooting

| Error | Solusi |
|-------|--------|
| 404 semua halaman | Document root belum ke `/public` |
| 500 permission | `chown -R www:www storage bootstrap/cache` |
| `open_basedir` | Site → PHP → nonaktifkan restriction atau tambah path project |
| `putenv` disabled | PHP Settings → hapus dari `disable_functions` |
| Upload gagal | Naikkan `upload_max_filesize` & `post_max_size` |
| Composer not found | Install dari App Store atau `curl` installer Composer |
| CSS/JS kosong | Jalankan `npm install && npm run build` atau upload folder `public/build` |

---

## Struktur folder

```
/www/wwwroot/domain-kamu.com/
├── app/
├── public/          ← document root Nginx
│   └── index.php
├── storage/
├── .env
├── scripts/
│   └── install-aapanel.sh
└── INSTALL_AAPANEL.md
```

---

## Update dari GitHub

```bash
cd /www/wwwroot/domain-kamu.com
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Jika ada perubahan frontend:

```bash
npm install && npm run build
```
