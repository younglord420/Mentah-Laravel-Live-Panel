#!/bin/bash
# Cloudflare Tunnel setup for marlomail.com
# Jalankan setelah domain marlomail.com ditambahkan ke Cloudflare (akun yang sama dengan cloudflared login).

set -euo pipefail

TUNNEL_NAME="marlomail"
DOMAIN="marlomail.com"

echo "==> Tunnel info"
cloudflared tunnel info "$TUNNEL_NAME" || true

echo ""
echo "==> Route DNS (butuh domain marlomail.com di Cloudflare dashboard)"
cloudflared tunnel route dns -f "$TUNNEL_NAME" "$DOMAIN"
cloudflared tunnel route dns -f "$TUNNEL_NAME" "www.$DOMAIN"

echo ""
echo "==> Restart cloudflared"
systemctl restart cloudflared
systemctl status cloudflared --no-pager | head -8

echo ""
echo "==> Test HTTPS"
curl -sI --max-time 15 "https://$DOMAIN/up" | head -5 || echo "Belum bisa — pastikan nameserver marlomail.com sudah ke Cloudflare."

echo ""
echo "Selesai. APP_URL harus: https://$DOMAIN"
echo "Lalu di Admin → Settings → Telegram → Set Webhook"
