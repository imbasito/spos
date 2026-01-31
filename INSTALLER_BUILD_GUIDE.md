# Fresh Installation Preparation Guide

## Pre-Build Checklist

Before building the installer, ensure:

1. ✅ All development features tested and working
2. ✅ Production .env configured (sanitize.php will reset it)
3. ✅ Database seeders ready (DatabaseSeeder.php)
4. ✅ All documentation finalized
5. ✅ Version number updated in package.json

## Building Clean Installer

### Step 1: Run Sanitization Script

```powershell
# Clean all development artifacts
php sanitize.php
```

**This script will:**
- Remove all development files (README, docs, tests, etc.)
- Clear database and create fresh empty schema
- Clear all caches and logs
- Reset .env to production defaults
- Set proper file permissions
- Create first-run activation flag

### Step 2: Build Installer

```powershell
# Build Windows installer
npm run dist:installer
```

**Output:** `dist_production/SPOS-Setup-1.0.5.exe`

## What Happens on Client's First Launch

1. **Splash Screen Shows:**
   - "Initializing systems..."
   - "Starting MySQL..."
   - "Loading database..."
   - "Checking activation status..." (NEW)
   - "Preparing first-time setup..." (NEW)
   - "Generating security keys..." (NEW)
   - "Creating database schema..." (NEW)
   - "Creating default accounts..." (NEW)
   - "Finalizing..."

2. **Behind the Scenes:**
   - Detects `storage/app/first_run_pending` flag
   - Generates unique APP_KEY
   - Runs database migrations (fresh schema)
   - Seeds default admin user (admin@spos.com/admin123)
   - Removes first-run flag
   - Creates `storage/app/activated_at` marker

3. **Login Screen:**
   - Clean, professional interface
   - Ready for admin login
   - No demo data, no clutter

## Testing Fresh Install

### Test on Clean Machine:

1. **Uninstall** any existing SPOS installation
2. **Delete** leftover data:
   - `C:\Program Files\SPOS`
   - `%APPDATA%\SPOS`
3. **Install** fresh from new installer
4. **Verify:**
   - First launch takes 2-3 minutes (migrations run)
   - Splash shows all activation steps
   - Login screen appears with no errors
   - Can login with admin@spos.com/admin123
   - Dashboard is empty (no demo data)
   - All features work correctly

## Production .env (Auto-configured)

```env
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=sqlite
LOG_LEVEL=error
SESSION_LIFETIME=10080
```

## Security Notes

- Fresh APP_KEY generated per installation
- No hardcoded credentials in installer
- Clean logs and cache on first run
- Production mode enabled by default

## Troubleshooting

**If first-run hangs:**
- Check PHP executable exists in `php/php.exe`
- Check artisan file exists
- Check database directory writable
- Check logs in `storage/logs/desktop.log`

**If migrations fail:**
- Ensure SQLite database file writable
- Check PHP version compatibility (8.1+)
- Verify all migration files present

**If seeder fails:**
- Check DatabaseSeeder.php calls StartUpSeeder
- Verify StartUpSeeder creates default users
- Check database constraints

## Version Control

Before releasing:

1. Update version in `package.json`
2. Tag release in git
3. Build with sanitize.php
4. Test on clean VM
5. Distribute to clients

## Client Documentation

Provide clients with:
- `SPOS_COMPLETE_DOCUMENTATION.md` (or PDF)
- Default credentials: admin@spos.com/admin123
- Installation video/guide
- Support contact details

---

**Last Updated:** January 31, 2026
