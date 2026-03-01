# Production Deployment Guide

## Prerequisites
- Ubuntu 22 with Apache, Node.js 18+
- Apache modules: `proxy`, `proxy_http`

## 1. Build the app

On your development machine (or in Replit):

```bash
npm run build
```

This creates the `dist/` folder with everything bundled — no `node_modules` needed on the server.

## 2. Copy dist to server

```bash
scp -r dist/ yourserver:/opt/property-listing/dist/
```

Only the `dist/` folder is needed. It contains:
- `dist/index.cjs` — the server bundle (all dependencies included)
- `dist/public/` — the built frontend assets

## 3. Set up environment variables

```bash
sudo mkdir -p /etc/property-listing
sudo cp env.example /etc/property-listing/env
sudo nano /etc/property-listing/env
# Fill in your real ESPOCRM_URL, ESPOCRM_API_KEY, SESSION_SECRET
sudo chmod 600 /etc/property-listing/env
```

## 4. Install the systemd service

```bash
sudo cp property-listing.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable property-listing
sudo systemctl start property-listing
```

Check status:
```bash
sudo systemctl status property-listing
sudo journalctl -u property-listing -f
```

## 5. Configure Apache

Enable proxy modules if not already:
```bash
sudo a2enmod proxy proxy_http
```

Add to your Apache site config (or copy `apache-listing.conf`):
```apache
<Location /listing/>
    ProxyPass http://127.0.0.1:5001/
    ProxyPassReverse http://127.0.0.1:5001/
    ProxyPreserveHost On
</Location>
```

Then reload:
```bash
sudo systemctl reload apache2
```

## 6. Test

Visit `https://yourdomain.com/listing/property/YOUR_PROPERTY_ID`

## Updating

To deploy a new version:
```bash
npm run build
scp -r dist/ yourserver:/opt/property-listing/dist/
ssh yourserver 'sudo systemctl restart property-listing'
```

## Notes

- The app listens on port 5001 by default in production (configurable via PORT in the env file)
- All verbose logging is suppressed in production; only errors are logged
- The Vite build uses `/listing/` as the base path so all asset URLs are correct behind the proxy
- The client-side router (wouter) is configured to handle the `/listing/` prefix automatically
