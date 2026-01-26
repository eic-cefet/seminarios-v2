<div align="center">

# Updating CEFET-RJ Seminários

**Guide for updating the application to the latest version**

</div>

---

## Requirements

Before updating, ensure your system meets the following requirements:

| Requirement | Version | Notes |
|-------------|---------|-------|
| PHP | 8.2+ | Required |
| Node.js | 18+ | Required |
| pnpm | Latest | Required |
| Git | Latest | Required |
| Composer | Latest | Optional (auto-installed as `composer.phar`) |

---

## Quick Update

```bash
# Run the automated update script
./update.sh
```

The script will automatically:
1. Verify all dependencies (installs `composer.phar` if needed)
2. Check for new releases
3. Exit if already on latest version (safe for cronjobs)
4. Enable maintenance mode
5. Update to the latest version
6. Install dependencies
7. Run migrations
8. Build assets
9. Optimize caches
10. Restart queue workers
11. Disable maintenance mode

> **Note:** The script is non-interactive and safe for cronjob use. It will exit gracefully if no updates are available.

---

## Automated Updates (Cronjob)

The script is designed to run unattended. Add to crontab for automatic updates:

```bash
# Check for updates daily at 3am
0 3 * * * cd /path/to/seminarios-eic && ./update.sh >> /var/log/seminarios-update.log 2>&1
```

---

## Manual Update

<details>
<summary><strong>Step-by-step manual update process</strong></summary>

### 1. Enable Maintenance Mode

```bash
php artisan down --retry=60 --refresh=15
```

### 2. Fetch Latest Tags

```bash
git fetch --tags
```

### 3. Check Latest Release

```bash
# List all tags
git tag -l

# Get the latest tag
git describe --tags --abbrev=0 $(git rev-list --tags --max-count=1)
```

### 4. Checkout New Version

```bash
# Replace vX.Y.Z with the actual tag
git checkout vX.Y.Z
```

### 5. Install Dependencies

```bash
# PHP dependencies (production)
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Node.js dependencies
pnpm install --frozen-lockfile
```

### 6. Run Migrations

```bash
php artisan migrate --force
```

### 7. Build Assets

```bash
pnpm run build
```

### 8. Optimize Caches

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 9. Restart Queue Workers

```bash
php artisan queue:restart
```

### 10. Disable Maintenance Mode

```bash
php artisan up
```

</details>

---

## Rollback

If something goes wrong, you can rollback to a previous version:

```bash
# Enable maintenance mode
php artisan down

# Checkout previous version (replace vX.Y.Z with the tag)
git checkout vX.Y.Z

# Rollback migrations (if needed, be careful!)
php artisan migrate:rollback --step=N

# Reinstall dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
pnpm install --frozen-lockfile

# Rebuild assets
pnpm run build

# Clear caches
php artisan optimize:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Disable maintenance mode
php artisan up
```

---

## Troubleshooting

### Update script permission denied

```bash
chmod +x update.sh
```

### Uncommitted changes error

The update script will fail if uncommitted changes are detected. Handle them before running the update:

```bash
# Option 1: Stash changes
git stash push -m "Before update"
./update.sh
git stash pop

# Option 2: Commit changes
git add .
git commit -m "WIP: work in progress"
./update.sh
```

### Migration failed

If a migration fails:

```bash
# Check migration status
php artisan migrate:status

# Try running migrations again
php artisan migrate --force

# If still failing, check logs
tail -f storage/logs/laravel.log
```

### Assets not loading after update

```bash
# Clear all caches
php artisan optimize:clear

# Rebuild assets
pnpm run build

# Rebuild caches
php artisan optimize
```

### Queue workers not processing

```bash
# Check queue status
php artisan queue:monitor

# Restart workers
php artisan queue:restart

# If using Supervisor, also restart it
sudo supervisorctl restart all
```

---

## Version History

To see all available versions:

```bash
git tag -l --sort=-v:refname
```

To see changes between versions:

```bash
git log --oneline v1.0.0..v1.1.0
```

---

<div align="center">

Made with ❤️ for **CEFET-RJ EIC**

</div>
