# SPOS 1.1.0 Installer Testing Guide

## Overview
This guide helps you test the professional update system and installer to ensure smooth deployments.

---

## Pre-Build Checklist

### 1. Environment Setup
- [ ] Ensure Node.js is installed and accessible via `nodejs\node.exe`
- [ ] Ensure PHP is installed and accessible via `php\php.exe`
- [ ] Verify `package.json` version is `1.1.0`
- [ ] Verify `config/app.php` version is `1.1.0`
- [ ] Check that all dependencies are installed (`node_modules`, `vendor`)

### 2. Code Verification
- [ ] All services created: `UpdateService`, `HealthCheckService`, `VersionService`, `RecoveryService`
- [ ] `LicenseHelper.php` updated to check `system_state.json`
- [ ] `main.cjs` includes professional migration system
- [ ] `splash.html` has multi-stage progress UI
- [ ] `installer.nsh` includes data preservation logic

### 3. Build Preparation
- [ ] Run `npm run build` to compile frontend assets
- [ ] Verify `public/build` directory is created
- [ ] Run `.build-scripts/cleanup.php` to sanitize configs
- [ ] Check that sensitive data is removed from configs

---

## Building the Installer

### Option 1: Using Build Script (Recommended)
```batch
build-installer.bat
```

### Option 2: Manual Build
```batch
# Step 1: Clean
php\php.exe .build-scripts\cleanup.php

# Step 2: Build frontend
npm run build

# Step 3: Build installer
npm run dist:installer
```

### Expected Output
- Installer location: `dist_production\SPOS-Setup-1.1.0.exe`
- Size: ~600-800 MB (includes MySQL, PHP, Node.js)
- Artifacts: `latest.yml`, `.blockmap` files

---

## Testing Scenarios

### Scenario 1: Fresh Installation (Clean Install)

**Setup:**
- Clean Windows machine or VM
- No previous SPOS installation

**Steps:**
1. Run `SPOS-Setup-1.1.0.exe`
2. Choose installation directory
3. Wait for installation to complete
4. Launch SPOS

**Expected Behavior:**
- ✅ Splash screen shows 4-stage progress (Preparing → Validating → Migrating → Finalizing)
- ✅ No errors during migration
- ✅ Activation screen appears (expected for fresh install)
- ✅ After activation, app loads normally
- ✅ `system_state.json` created in `storage/app/`
- ✅ `install_metadata.json` shows `"install_type":"clean_install"`

**Verification:**
```powershell
# Check system state file
Get-Content "$env:LOCALAPPDATA\Programs\spos\resources\storage\app\system_state.json" | ConvertFrom-Json

# Should show:
# - installation_type: "clean_install"
# - installed_version: "1.1.0"
# - activated: false (before activation)
```

---

### Scenario 2: Update from 1.0.6 to 1.1.0

**Setup:**
- Machine with SPOS 1.0.6 installed and activated
- Database with sample data
- License activated

**Steps:**
1. **BEFORE UPDATE:** Note the license key from Settings
2. **BEFORE UPDATE:** Backup database manually (optional safety check)
3. Run `SPOS-Setup-1.1.0.exe`
4. Choose same installation directory as 1.0.6
5. Wait for installation to complete
6. Launch SPOS

**Expected Behavior:**
- ✅ Installer detects existing installation (shows "Update mode" in details)
- ✅ Backup created automatically before installation
- ✅ License and database preserved
- ✅ Activation screen DOES NOT appear (license restored from state)
- ✅ App loads with existing data intact
- ✅ Dashboard shows same products, orders, customers
- ✅ `system_state.json` preserved with license info
- ✅ `install_metadata.json` shows `"install_type":"update"`

**Verification:**
```powershell
# Check system state
Get-Content "$env:LOCALAPPDATA\Programs\spos\resources\storage\app\system_state.json" | ConvertFrom-Json

# Should show:
# - installed_version: "1.1.0"
# - activated: true
# - license_key: (your original key)

# Check installation metadata
Get-Content "$env:LOCALAPPDATA\Programs\spos\resources\storage\app\install_metadata.json" | ConvertFrom-Json

# Should show:
# - install_type: "update"
# - from_version: "previous"
# - to_version: "1.1.0"
```

---

### Scenario 3: Migration Failure Recovery

**Setup:**
- Intentionally corrupt database or migration file to cause failure

**Steps:**
1. Install 1.1.0
2. Corrupt a migration file before launch
3. Launch SPOS

**Expected Behavior:**
- ✅ Migration fails and is detected
- ✅ Splash screen shows error state (red X on Migrating stage)
- ✅ Error message displayed with recovery options
- ✅ Three buttons appear: "Retry", "View Logs", "Restore Backup"
- ✅ Clicking "Restore Backup" rolls back to pre-migration state
- ✅ App functions with previous version's data

---

### Scenario 4: Failed Update Recovery

**Setup:**
- Kill app during update to simulate crash

**Steps:**
1. Start update from 1.0.6 to 1.1.0
2. Kill process during migration
3. Restart SPOS

**Expected Behavior:**
- ✅ App detects `update_in_progress` flag
- ✅ Shows "Recovering from failed update" message
- ✅ Attempts auto-recovery
- ✅ If recovery succeeds, continues normally
- ✅ If recovery fails, offers manual restore

---

### Scenario 5: Uninstall with Data Backup

**Steps:**
1. Uninstall SPOS from Windows Settings
2. When prompted, choose to backup data
3. Verify backup location

**Expected Behavior:**
- ✅ Prompt asks to backup before uninstall
- ✅ Backup saved to `Documents\SPOS_Backup`
- ✅ Contains: `database.sqlite`, `system_state.json`, `system.php`, `storage/`
- ✅ Firewall rules removed
- ✅ App files removed

**Verification:**
```powershell
# Check backup location
Get-ChildItem "$env:USERPROFILE\Documents\SPOS_Backup"

# Should contain:
# - database.sqlite
# - system_state.json
# - system.php
# - storage/ (folder)
```

---

## Auto-Update Testing (GitHub Releases)

### Setup
1. Create GitHub release for 1.1.0
2. Upload `SPOS-Setup-1.1.0.exe` and `latest.yml`
3. Ensure release is published (not draft)

### Testing Auto-Update
1. Open SPOS 1.0.6
2. Go to Settings → System → Check for Updates
3. Click "Check for Updates"

**Expected Behavior:**
- ✅ Shows "Update available: v1.1.0"
- ✅ "Download Update" button appears
- ✅ Progress bar shows during download
- ✅ "Install and Restart" button appears when ready
- ✅ Click to install → app closes → installer runs → app restarts
- ✅ After restart, license preserved (no re-activation)

---

## Troubleshooting

### Issue: "Installer requires administrator privileges"
**Solution:** Right-click installer → "Run as Administrator"

### Issue: Activation screen appears after update
**Possible Causes:**
1. `system_state.json` not backed up correctly
2. Installer didn't restore the file
3. License check failed

**Debug Steps:**
```powershell
# Check if state file exists
Test-Path "$env:LOCALAPPDATA\Programs\spos\resources\storage\app\system_state.json"

# If exists, check contents
Get-Content "$env:LOCALAPPDATA\Programs\spos\resources\storage\app\system_state.json"

# Check backup directory (if installer failed)
Get-ChildItem "$env:TEMP\SPOS_Backup_*"
```

### Issue: Migration fails on startup
**Debug Steps:**
1. Click "View Logs" in splash screen error
2. Check `app.log` for migration errors
3. Look for SQL errors or missing tables
4. Use "Restore Backup" to rollback
5. Try manual migration: `php artisan migrate --force`

### Issue: Database empty after update
**Possible Cause:** Database not backed up properly

**Recovery:**
```powershell
# Check if backup exists
Get-ChildItem "$env:TEMP\SPOS_Backup_*\database.sqlite"

# If exists, manually copy back
Copy-Item "$env:TEMP\SPOS_Backup_*\database.sqlite" `
    "$env:LOCALAPPDATA\Programs\spos\resources\database\database.sqlite"
```

---

## Success Criteria

### Clean Install ✅
- [ ] Installs without errors
- [ ] Firewall rules created
- [ ] Activation screen appears
- [ ] After activation, app loads
- [ ] Can create products, orders
- [ ] System state file created

### Update Install ✅
- [ ] Detects existing installation
- [ ] Creates backup automatically
- [ ] Preserves license (no re-activation)
- [ ] Preserves database
- [ ] All existing data accessible
- [ ] Migrations run successfully

### Error Recovery ✅
- [ ] Detects failed migrations
- [ ] Shows error with recovery options
- [ ] Restore backup works correctly
- [ ] Retry succeeds after fixing issue
- [ ] View logs opens correct file

### Uninstall ✅
- [ ] Offers to backup data
- [ ] Backup saved correctly
- [ ] Firewall rules removed
- [ ] All files removed

---

## Release Checklist

Before publishing to production:

- [ ] Tested clean install on 3+ machines
- [ ] Tested update from 1.0.6 on 3+ machines
- [ ] Verified license preservation works
- [ ] Tested migration failure recovery
- [ ] Tested backup/restore functionality
- [ ] Verified uninstall process
- [ ] Auto-update tested from GitHub release
- [ ] Generated SHA256 checksum for installer
- [ ] Updated CHANGELOG.md
- [ ] Created release notes
- [ ] Prepared user communication
- [ ] Set up staged rollout plan (10% → 100%)

---

## Post-Release Monitoring

### Week 1: Monitor
- Update success rate (target: >95%)
- Activation issues reports (target: <5%)
- Migration failures (target: <2%)
- User feedback and support tickets

### Actions if Issues Found
1. Pause rollout to remaining users
2. Investigate root cause
3. Prepare hotfix if needed
4. Test hotfix thoroughly
5. Resume rollout or release 1.1.1

---

## Support Information

### Log Locations
- **Application logs:** `%APPDATA%\spos\app.log`
- **Laravel logs:** `resources\storage\logs\laravel.log`
- **MySQL logs:** `resources\mysql\data\*.err`

### Diagnostic Commands
```powershell
# Get system state
Get-Content "$env:LOCALAPPDATA\Programs\spos\resources\storage\app\system_state.json" | ConvertFrom-Json

# Check migration status
php\php.exe artisan migrate:status

# List backups
Get-ChildItem "$env:LOCALAPPDATA\Programs\spos\resources\storage\app\backups"

# Export diagnostic bundle (via app)
# Settings → System → Export Diagnostic Bundle
```

### Common User Issues

| Symptom | Likely Cause | Solution |
|---------|--------------|----------|
| Activation screen after update | State file not restored | Manual license re-entry (one-time) |
| 500 error after login | Migration failed | Use "Restore Backup" from splash |
| Missing products/orders | Database not restored | Restore from backup directory |
| Can't open app | Failed migration blocking | View logs, then restore backup |

---

## Version Information

- **Version:** 1.1.0
- **Build Date:** 2026-02-05
- **Installer:** SPOS-Setup-1.1.0.exe
- **Electron:** 28.3.x
- **Laravel:** 10.x
- **PHP:** 8.1+
- **MySQL:** 8.0+

---

**Remember:** The professional update system is designed to handle errors gracefully. Always trust the recovery mechanisms built into the app!
