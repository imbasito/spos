const { app, BrowserWindow, dialog, ipcMain, session, powerSaveBlocker } = require('electron');
const fs = require('fs');


// Performance & Professionalism Optimizations (Apple-Style Native Feel)
app.commandLine.appendSwitch('disable-http-cache');
app.commandLine.appendSwitch('disable-spellcheck');
app.commandLine.appendSwitch('disable-voice-input');
app.commandLine.appendSwitch('disable-renderer-backgrounding');
app.commandLine.appendSwitch('disable-background-timer-throttling');
app.commandLine.appendSwitch('disable-breakpad'); // Disable crash reporting to save CPU
app.commandLine.appendSwitch('no-pings'); // Disable background telemetry

// Prevent System Sleep (High-Performance POS Mode)
powerSaveBlocker.start('prevent-app-suspension');

const { autoUpdater } = require('electron-updater');
const { spawn } = require('child_process');
const path = require('path');
const receiptline = require('receiptline');
const net = require('net');
const http = require('http');
const treeKill = require('tree-kill');
const os = require('os');
const PrinterTransport = require('./services/PrinterTransport.js');


let laravelServer, mysqlServer, splashWindow, mainWindow;
let drawerStatus = 'closed'; // 'closed' or 'open'
let drawerLastOpenTime = 0;
const MYSQL_PORT = 3307;

let laravelPort = 8000;

// Determine base path - handle both packaged and unpacked scenarios
let basePath;
if (app.isPackaged) {
    basePath = process.resourcesPath;
} else {
    // Check if we're running from dist_production (win-unpacked)
    if (__dirname.includes('dist_production')) {
        basePath = __dirname;
    } else {
        // Development mode
        basePath = __dirname;
    }
}

// Temporary debug - will be logged after Logger is initialized

// ============================================
// PROFESSIONAL LOG MANAGEMENT (Black Box)
// ============================================
class Logger {
    constructor() {
        this.logDir = path.join(basePath, 'storage', 'logs');
        this.logFile = path.join(this.logDir, 'desktop.log');
        if (!fs.existsSync(this.logDir)) {
            fs.mkdirSync(this.logDir, { recursive: true });
        }
    }

    log(message, level = 'INFO') {
        const timestamp = new Date().toISOString();
        const logEntry = `[${timestamp}] [${level}] ${message}\n`;
        try {
            console.log(logEntry.trim());
        } catch (e) {
            // Ignore EPIPE errors when console is closed
        }
        try {
            fs.appendFileSync(this.logFile, logEntry);
        } catch (err) {
            // Silently fail if log file write fails
        }
    }

    error(message) { this.log(message, 'ERROR'); }
    warn(message) { this.log(message, 'WARN'); }

    /**
     * Automatic Log Rotation (7-day retention)
     */
    rotateLogs() {
        this.log('Running log rotation...');
        try {
            const now = Date.now();
            const files = fs.readdirSync(this.logDir);
            files.forEach(file => {
                if (file.endsWith('.log')) {
                    const filePath = path.join(this.logDir, file);
                    const stats = fs.statSync(filePath);
                    const ageInDays = (now - stats.mtimeMs) / (1000 * 60 * 60 * 24);
                    if (ageInDays > 7) {
                        fs.unlinkSync(filePath);
                        this.log(`Deleted old log file: ${file}`);
                    }
                }
            });
        } catch (err) {
            this.error(`Rotation failed: ${err.message}`);
        }
    }

    /**
     * Startup Maintenance (Cleanup Temp & Junk)
     */
    cleanup() {
        this.log('Performing startup maintenance...');
        this.rotateLogs();
        // Additional cleanup like pruning old crash dumps or session cache can go here
    }
}
const logger = new Logger();

// Log basePath information for debugging
logger.log('=== BASEPATH DEBUG ===');
logger.log('app.isPackaged: ' + app.isPackaged);
logger.log('process.resourcesPath: ' + process.resourcesPath);
logger.log('__dirname: ' + __dirname);
logger.log('FINAL basePath: ' + basePath);
logger.log('======================');

// ============================================

// HEALTH CHECK UTILITIES
// ============================================
function checkDiskSpace(minMB = 500) {
    return new Promise((resolve, reject) => {
        const root = path.parse(basePath).root;
        const cmd = process.platform === 'win32' 
            ? `wmic logicaldisk where "Caption='${root.replace('\\', '')}'" get FreeSpace /value`
            : `df -k "${root}"`;

        const { exec } = require('child_process');
        exec(cmd, (error, stdout) => {
            if (error) {
                logger.error('Disk check failed: ' + error.message);
                return resolve(true);
            }

            
            let freeBytes = 0;
            if (process.platform === 'win32') {
                const match = stdout.match(/FreeSpace=(\d+)/);
                if (match) freeBytes = parseInt(match[1], 10);
            } else {
                const lines = stdout.trim().split('\n');
                const parts = lines[lines.length - 1].split(/\s+/);
                freeBytes = parseInt(parts[3], 10) * 1024;
            }

            const freeMB = freeBytes / 1024 / 1024;
            console.log(`Disk Free Space: ${Math.round(freeMB)} MB`);

            if (freeMB < minMB) {
                reject(new Error(`Low Disk Space! Only ${Math.round(freeMB)}MB available.\nMinimum ${minMB}MB required to run safely.`));
            } else {
                resolve(true);
            }
        });
    });
}

function waitForMySQL(port, timeout = 30000) {
    return new Promise((resolve, reject) => {
        const startTime = Date.now();
        console.log(`Waiting for MySQL on port ${port}...`);
        const check = () => {
            const socket = new net.Socket();
            socket.setTimeout(2000);
            socket.on('connect', () => {
                console.log('MySQL connection successful!');
                socket.destroy();
                resolve(true);
            });
            socket.on('error', () => {
                socket.destroy();
                if (Date.now() - startTime > timeout) reject(new Error(`MySQL connection timeout`));
                else setTimeout(check, 1000);
            });
            socket.connect(port, '127.0.0.1');
        };
        setTimeout(check, 2000);
    });
}

function waitForLaravel(port, timeout = 30000) {
    return new Promise((resolve, reject) => {
        const startTime = Date.now();
        console.log(`Waiting for Laravel on port ${port}...`);
        const check = () => {
            const req = http.get(`http://127.0.0.1:${port}`, () => {
                console.log('Laravel connection successful!');
                resolve(true);
            });
            req.on('error', () => {
                if (Date.now() - startTime > timeout) reject(new Error(`Laravel connection timeout`));
                else setTimeout(check, 1000);
            });
        };
        setTimeout(check, 2000);
    });
}

// ============================================
// SPLASH SCREEN
// ============================================
// ============================================
// SPLASH SCREEN
// ============================================
// ============================================
// SPLASH SCREEN
// ============================================
// ============================================
// SPLASH SCREEN
// ============================================
function createSplashWindow() {
    splashWindow = new BrowserWindow({
        width: 650, height: 600, // Increased to prevent clipping of bottom radius
        frame: false, 
        transparent: true, 
        alwaysOnTop: true, 
        resizable: false,
        center: true, 
        skipTaskbar: true, 
        show: false,
        backgroundColor: '#00000000', // Fully transparent for rounded corners
        icon: path.join(basePath, 'pos-icon.ico'),
        webPreferences: { nodeIntegration: false, contextIsolation: true }

    });
    
    splashWindow.once('ready-to-show', () => {
        splashWindow.show();
    });

    const splashPath = app.isPackaged ? path.join(process.resourcesPath, 'splash.html') : path.join(__dirname, 'splash.html');
    splashWindow.loadFile(splashPath);

    // Sync Version with package.json (Apple-style accuracy)
    splashWindow.webContents.on('did-finish-load', () => {
        const version = app.getVersion();
        splashWindow.webContents.executeJavaScript(`if(window.setVersion) window.setVersion('${version}');`).catch(() => {});
    });
}


function updateSplashStatus(message) {
    if (splashWindow && !splashWindow.isDestroyed()) {
        splashWindow.webContents.executeJavaScript(`if(document.getElementById('status-text')) document.getElementById('status-text').innerHTML = \`${message}\`;`).catch(() => {});
    }
}


// ============================================
// SERVER STARTUP
// ============================================


async function isPortOpen(port) {
    return new Promise((resolve) => {
        const s = new net.Socket();
        s.once('error', (err) => {
            s.destroy();
            if (err.code === 'ECONNREFUSED') resolve(false); // Valid: Port is free
            else resolve(false); // Other error, assume free/broken
        });
        s.once('connect', () => {
            s.destroy();
            resolve(true); // Port is taken
        });
        s.on('timeout', () => { s.destroy(); resolve(false); });
        s.connect(port, '127.0.0.1');
    });
}

/**
 * SINYX BRIDGE: REMOTE CONFIG
 * Fetches printer and system settings directly from Laravel.
 * Eliminates the need for local .env/json config for printer swaps.
 */
async function fetchRemoteConfig() {
    return new Promise((resolve) => {
        logger.log('Fetching Remote Configuration...');
        const url = `http://127.0.0.1:${laravelPort}/api/printer-settings`;
        
        http.get(url, { headers: { 'Accept': 'application/json' } }, (res) => {
            let body = '';
            res.on('data', d => body += d);
            res.on('end', () => {
                try {
                    const config = JSON.parse(body);
                    global.posConfig = config; // Store globally for renderers
                    logger.log(`Remote Config Loaded: ${config.receipt_printer || 'No Printer'}`);
                    resolve(config);
                } catch (e) {
                    logger.error(`Failed to parse Remote Config: ${e.message}`);
                    resolve(null);
                }
            });
        }).on('error', (e) => {
            logger.error(`Remote Config HTTP Error: ${e.message}`);
            resolve(null);
        });
    });
}

async function startMySQL() {
    // 1. Check if MySQL is already running
    const isOpen = await isPortOpen(MYSQL_PORT);
    if (isOpen) {
        logger.log(`MySQL port ${MYSQL_PORT} is already in use. Verifying connection...`);
        try {
            await waitForMySQL(MYSQL_PORT, 2000);
            logger.log("Existing MySQL instance found and responsive. Skipping spawn.");
            return;
        } catch (e) {
            logger.warn("Port is used but MySQL not responding. Killing orphan process...");
            try { treeKill(process.pid); /* Safe Attempt */ } catch(e){}
            // We'll proceed to spawn anyway, hoping the OS clears the port
        }
    }

    return new Promise((resolve) => {
        logger.log('Starting MySQL Server...');
        const mysqlPath = path.join(basePath, 'mysql', 'bin', 'mysqld.exe');
        const myIniPath = path.join(basePath, 'mysql', 'my.ini');
        
        // Spawn without 'shell: true' to keep PID tracking accurate
        mysqlServer = spawn(mysqlPath, [`--defaults-file=${myIniPath}`, '--console'], { cwd: basePath, windowsHide: true });
        
        mysqlServer.on('error', (err) => {
            logger.error(`Failed to start MySQL: ${err.message}`);
        });

        // Loop Prevention: Only restart if we actually own the process and it wasn't a clean exit
        mysqlServer.on('exit', (code) => { 
            if (!app.isQuitting) {
                logger.warn(`MySQL exited with code ${code}. Checking if we need to restart...`);
                // Check if port became free (meaning it actually died)
                isPortOpen(MYSQL_PORT).then(stillOpen => {
                    if (!stillOpen) {
                        logger.warn("MySQL died and port is free. Restarting in 1s...");
                        setTimeout(startMySQL, 1000); 
                    } else {
                        logger.warn("MySQL exited but port is still open. Likely conflict or concurrent instance. NOT restarting.");
                    }
                });
            }
        });
        resolve();
    });
}

function startLaravel(port) {
    return new Promise((resolve) => {
        logger.log(`Starting Laravel Server on port ${port}...`);
        const phpPath = path.join(basePath, 'php', 'php.exe');
        laravelServer = spawn(phpPath, ['artisan', 'serve', `--host=127.0.0.1`, `--port=${port}`], { cwd: basePath, windowsHide: true });
        resolve();
    });
}

/**
 * Creates the spos database if it doesn't exist
 */
function createDatabase() {
    return new Promise((resolve, reject) => {
        logger.log('Ensuring spos database exists...');
        const mysqlPath = path.join(basePath, 'mysql', 'bin', 'mysql.exe');
        
        // Check if database exists first - DON'T drop existing data!
        const checkDbProcess = spawn(mysqlPath, [
            '-u', 'root',
            '-P', MYSQL_PORT,
            '--protocol=TCP',
            '-e', 'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = "spos";'
        ], { 
            cwd: basePath, 
            windowsHide: true
        });

        let checkOutput = '';
        checkDbProcess.stdout.on('data', (data) => {
            checkOutput += data.toString();
        });

        checkDbProcess.on('close', (code) => {
            // If database exists (output contains 'spos'), skip creation
            if (checkOutput.includes('spos')) {
                logger.log('Database already exists - keeping existing data');
                resolve();
                return;
            }

            // Database doesn't exist - create it
            logger.log('Creating new database...');
            const createDbProcess = spawn(mysqlPath, [
                '-u', 'root',
                '-P', MYSQL_PORT,
                '--protocol=TCP',
                '-e', 'CREATE DATABASE spos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
            ], { 
                cwd: basePath, 
                windowsHide: true
            });

            let dbOutput = '';
            let dbErrors = '';

            createDbProcess.stdout.on('data', (data) => {
                dbOutput += data.toString();
                logger.log(`[DB CREATE]: ${data}`);
            });
            
            createDbProcess.stderr.on('data', (data) => {
                const msg = data.toString();
                // Ignore warnings, only log actual errors
                if (!msg.includes('Warning:')) {
                    dbErrors += msg;
                    logger.error(`[DB CREATE ERROR]: ${msg}`);
                }
            });

            createDbProcess.on('close', (code) => {
                if (code !== 0 && dbErrors) {
                    logger.error('Failed to create database');
                    reject(new Error('Database creation failed'));
                    return;
                }
                logger.log('Database ready');
                resolve();
            });
        });
    });
}

/**
 * AUTO-MIGRATION ENGINE
 * Ensures database schema is ALWAYS in sync with the current code.
 * Runs 'php artisan migrate --force' silently on startup.
 */
function runMigrations() {
    return new Promise((resolve, reject) => {
        logger.log('Checking for database migrations...');
        const phpPath = path.join(basePath, 'php', 'php.exe');
        const migrateProcess = spawn(phpPath, ['artisan', 'migrate', '--force'], { 
            cwd: basePath, 
            windowsHide: true,
            env: { ...process.env, DB_PORT: MYSQL_PORT } // Ensure it uses the right port
        });

        let migrationOutput = '';
        let migrationErrors = '';

        migrateProcess.stdout.on('data', (data) => {
            migrationOutput += data.toString();
            logger.log(`[MIGRATION]: ${data}`);
        });
        
        migrateProcess.stderr.on('data', (data) => {
            migrationErrors += data.toString();
            logger.error(`[MIGRATION ERROR]: ${data}`);
        });

        migrateProcess.on('close', (code) => {
            logger.log(`Migration process exited with code ${code}`);
            
            // Check for critical migration errors
            if (code !== 0 && migrationErrors && !migrationErrors.includes('Nothing to migrate')) {
                logger.error('Critical migration error detected. Cannot proceed.');
                reject(new Error('Migration failed'));
                return;
            }
            
            // --- Clear Laravel caches BEFORE seeding to prevent permission errors ---
            logger.log('Clearing Laravel caches...');
            const cachePath = path.join(basePath, 'bootstrap', 'cache');
            const storageCachePath = path.join(basePath, 'storage', 'framework', 'cache', 'data');
            const viewsPath = path.join(basePath, 'storage', 'framework', 'views');
            
            try {
                const fs = require('fs');
                // Clear bootstrap cache
                if (fs.existsSync(cachePath)) {
                    const files = fs.readdirSync(cachePath).filter(f => f.endsWith('.php'));
                    files.forEach(file => {
                        try { fs.unlinkSync(path.join(cachePath, file)); } catch (e) {}
                    });
                }
                // Clear storage cache
                if (fs.existsSync(storageCachePath)) {
                    const clearDir = (dir) => {
                        if (fs.existsSync(dir)) {
                            fs.readdirSync(dir).forEach(file => {
                                const fullPath = path.join(dir, file);
                                if (fs.lstatSync(fullPath).isDirectory()) {
                                    clearDir(fullPath);
                                    try { fs.rmdirSync(fullPath); } catch (e) {}
                                } else {
                                    try { fs.unlinkSync(fullPath); } catch (e) {}
                                }
                            });
                        }
                    };
                    clearDir(storageCachePath);
                }
                // Clear views cache
                if (fs.existsSync(viewsPath)) {
                    fs.readdirSync(viewsPath).forEach(file => {
                        try { fs.unlinkSync(path.join(viewsPath, file)); } catch (e) {}
                    });
                }
                logger.log('Laravel caches cleared.');
            } catch (e) {
                logger.warn('Cache clearing had minor issues, continuing...');
            }
            
            // --- NEW: Run Database Seeder to ensure core data exists ---
            logger.log('Seeding core data...');
            const seedProcess = spawn(phpPath, ['artisan', 'db:seed', '--class=StartUpSeeder', '--force'], {
                cwd: basePath, 
                windowsHide: true,
                env: { ...process.env, DB_PORT: MYSQL_PORT }
            });
            
            let seederErrors = '';
            
            seedProcess.stdout.on('data', (data) => logger.log(`[SEEDER]: ${data}`));
            seedProcess.stderr.on('data', (data) => {
                // Ignore PHP deprecation warnings during seeding
                const msg = data.toString();
                if (!msg.includes('Deprecated') && !msg.includes('deprecated')) {
                    seederErrors += msg;
                    logger.error(`[SEEDER ERROR]: ${data}`);
                }
            });
            
            seedProcess.on('close', (seedCode) => {
                logger.log(`Seeder process exited with code ${seedCode}`);
                
                // Seeder errors are non-critical - just log them
                if (seedCode !== 0 && seederErrors) {
                    logger.warn('Seeder reported issues, but continuing startup...');
                }
                
                // --- Clear Permission Cache (Spatie) ---
                logger.log('Clearing permission cache...');
                const permClearProcess = spawn(phpPath, ['artisan', 'permission:cache-reset'], {
                    cwd: basePath, 
                    windowsHide: true,
                    env: { ...process.env, DB_PORT: MYSQL_PORT } 
                });
                
                permClearProcess.on('close', () => {
                    logger.log('Permission cache cleared.');
                    
                    // --- Clear View Cache to ensure UI updates apply ---
                    logger.log('Clearing view cache...');
                    const clearProcess = spawn(phpPath, ['artisan', 'view:clear'], {
                        cwd: basePath, 
                        windowsHide: true,
                        env: { ...process.env } 
                    });
                    
                    clearProcess.on('close', () => {
                        logger.log('View cache cleared.');
                        resolve();
                    });
                });
            });
        });
    });
}


// ============================================
// MAIN WINDOW
// ============================================
function createMainWindow() {
    const preloadPath = path.join(__dirname, 'preload.cjs');
    logger.log(`[INIT]: Loading Preload from ${preloadPath}`);
    
    mainWindow = new BrowserWindow({

        width: 1280, height: 800, title: "SPOS", icon: path.join(basePath, 'pos-icon.ico'), 
        autoHideMenuBar: true, show: false,
        backgroundColor: '#f4f6f9', 
        webPreferences: { 
            contextIsolation: true, 
            preload: preloadPath, 
            sandbox: false,
            zoomFactor: 0.85 
        }
    });
    mainWindow.loadURL(`http://127.0.0.1:${laravelPort}`);
    
    // Check license after page loads and redirect if not activated
    mainWindow.webContents.on('did-finish-load', () => {
        // Inject JavaScript to check license status from the page itself
        mainWindow.webContents.executeJavaScript(`
            fetch('/api/license-check')
                .then(r => r.json())
                .then(data => {
                    console.log('License check result:', data);
                    if (!data.activated) {
                        window.location.href = '/activate';
                    }
                })
                .catch(err => console.error('License check failed:', err));
        `).catch(err => logger.error('Failed to inject license check: ' + err.message));
    });
    
    mainWindow.once('ready-to-show', async () => {
        if (splashWindow && !splashWindow.isDestroyed()) {
            // Pre-transition: Release focus priority
            splashWindow.setAlwaysOnTop(false);
            
            // Trigger fade-out animation in splash screen
            splashWindow.webContents.executeJavaScript('if(window.fadeOut) window.fadeOut();').catch(() => {});
            
            // Wait for animation to finish (Apple-style smoothness)
            await new Promise(r => setTimeout(r, 600));
            splashWindow.hide();
            splashWindow.close();
        }
        
        // Final hand-off: Snappy yet smooth
        mainWindow.maximize();
        mainWindow.show();
        mainWindow.focus();
    });




    // POS Lockdown: Disable Right-Click (Professional Appliance Feel)
    mainWindow.webContents.on('context-menu', (e) => {
        e.preventDefault();
    });
}

// ============================================
// SHARED CASH DRAWER KICKER
// ============================================
async function triggerCashDrawer(printerName) {
    const pName = printerName || "POS80 Printer"; // Fallback to user's hardware name
    console.log(`[DRAWER KICK]: Sending Raw Signal to ${pName}`);
    
    try {
        // Standard ESC/POS Drawer Kick Sequence (ESC p 0 25 250)
        // This is sent as RAW BYTES to bypass driver parsing
        const kickBytes = Buffer.from([0x1b, 0x70, 0x00, 0x19, 0xfa]);
        await PrinterTransport.printBuffer(pName, kickBytes);
        
        // Double kick for extra reliability on some hardware
        const kickBytes2 = Buffer.from([0x1b, 0x70, 0x01, 0x19, 0xfa]);
        await PrinterTransport.printBuffer(pName, kickBytes2);
        
        console.log("[DRAWER KICK]: Raw Signal Sent Successfully");
    } catch (e) {
        console.error("Drawer Kick Failed:", e.message);
    }
}

// ============================================
// AUTO-UPDATER & IPC
// ============================================
function setupAutoUpdater() {
    autoUpdater.autoDownload = false;
    ipcMain.on('updater:check', () => autoUpdater.checkForUpdates());
    ipcMain.on('updater:download', () => autoUpdater.downloadUpdate());
    ipcMain.on('updater:install', () => { killProcesses(); autoUpdater.quitAndInstall(); });
    
    // Hardened Updater Events
    autoUpdater.on('update-available', (info) => mainWindow.webContents.send('updater:status', 'available', info.version));
    autoUpdater.on('update-not-available', () => mainWindow.webContents.send('updater:status', 'latest'));
    autoUpdater.on('error', (err) => mainWindow.webContents.send('updater:status', 'error', err.message));
    autoUpdater.on('download-progress', (progress) => mainWindow.webContents.send('updater:progress', progress.percent));
    autoUpdater.on('update-downloaded', () => mainWindow.webContents.send('updater:ready'));

    // Config IPC
    ipcMain.handle('config:get-remote', () => global.posConfig || null);

    // Diagnostic Log from Render
    ipcMain.on('log-from-render', (event, msg) => { console.log(`[RENDER LOG]: ${msg}`); });

    // Silent Printing / PDF IPC
    ipcMain.handle('print:silent', async (event, options) => {
        const { url, printerName, htmlContent, jsonData } = options;
        console.log(`[PRINT REQUEST]: ${jsonData ? 'Headless JSON (ESC/POS)' : 'Legacy HTML'}`);
        
        // DEBUG: Log the actual data structure
        if (jsonData && jsonData.type !== 'barcode') {
            console.log('[DEBUG] Print Data:', JSON.stringify(jsonData, null, 2));
        }
        
        // --- AUTO-RESOLVE PRINTER (DRIVER MISMATCH FIX) ---
        // Prevents silent failure if 'POS80' is missing but 'Thermal Printer' is default.
        const targetPrinter = await resolvePrinter(printerName); 
        console.log(`[PRINTER RESOLVED]: Requested "${printerName}" -> Using "${targetPrinter}"`);

        try {
            if (jsonData && jsonData.type === 'barcode') {
                // PROFESSIONAL BARCODE PRINTING (RAW ESC/POS)
                try {
                    const buffer = generateBarcodeESC(jsonData);
                    if (buffer) {
                        console.log(`[RAW BARCODE]: Starting RAW print for ${jsonData.barcodeValue} on "${targetPrinter}". Buffer length: ${buffer.length} bytes`);
                        
                        // ESC/POS Initialization (ESC @) + Existing Buffer + Line Feeds
                        const flushBuffer = Buffer.concat([
                            Buffer.from([0x1B, 0x40]), // Initialize Printer
                            Buffer.from(buffer),
                            Buffer.from([0x0A, 0x0A, 0x0A, 0x0A]) // 4 Line Feeds
                        ]);

                        await PrinterTransport.printBuffer(targetPrinter, flushBuffer);
                        return { success: true };
                    } else {
                        throw new Error("Failed to generate ESC/POS buffer");
                    }
                } catch (rawError) {
                    console.error("[RAW BARCODE FAILED]:", rawError);
                    // Graphical Fallback if RAW fails
                    const isLarge = jsonData.labelSize === 'large';
                    const windowWidth = isLarge ? 190 : 145;
                    const windowHeight = isLarge ? 115 : 95;
                    const printWindow = new BrowserWindow({
                        show: false, width: windowWidth, height: windowHeight,
                        webPreferences: { contextIsolation: false, nodeIntegration: true }
                    });
                    const barcodeHtml = generateBarcodeHtmlFallback(jsonData);
                    const tempPath = path.join(app.getPath('temp'), `barcode_fb_${Date.now()}.html`);
                    fs.writeFileSync(tempPath, barcodeHtml);
                    await printWindow.loadFile(tempPath);
                    await new Promise(r => setTimeout(r, 800)); 
                    await new Promise((resolve, reject) => {
                        printWindow.webContents.print({ 
                            silent: true, printBackground: true, deviceName: printerName,
                            margins: { marginType: 'none' }
                        }, (s, e) => s ? resolve() : reject(e));
                    });
                    printWindow.close();
                    return { success: true };
                }

            } else if (jsonData) {
                // PROFESSIONAL RAW RECEIPT PRINTING (ESC/POS)
                try {
                    const rawGenerator = require('./services/RawReceiptGenerator');
                    const buffer = await rawGenerator.generate(jsonData);
                    await PrinterTransport.printBuffer(targetPrinter, buffer);
                    return { success: true };
                } catch (rawError) {
                    console.error("[RAW PRINT FAILED]:", rawError);
                    return { success: false, error: rawError.message };
                }

            } else {
                // LEGACY URL PRINTING
                const printWindow = new BrowserWindow({
                    show: false, width: 302, height: 1000,
                    webPreferences: { contextIsolation: false, nodeIntegration: true }
                });

                if (htmlContent) {
                    const tempPath = path.join(app.getPath('temp'), `legacy_${Date.now()}.html`);
                    fs.writeFileSync(tempPath, htmlContent);
                    await printWindow.loadFile(tempPath);
                } else {
                    await printWindow.loadURL(url);
                }
                
                await new Promise(r => setTimeout(r, 1000));
                await new Promise((resolve, reject) => {
                    printWindow.webContents.print({ 
                        silent: true, 
                        printBackground: true, 
                        deviceName: targetPrinter,
                        margins: { marginType: 'none' }
                    }, (s, e) => s ? resolve() : reject(e));
                });
                printWindow.close();
                return { success: true };
            }
        } catch (error) {
            console.error('[PRINT ERROR]:', error);
            return { success: false, error: error.message };
        }
    });

    // Cash Drawer Kick IPC
    ipcMain.handle('printer:open-drawer', async (event, { printerName }) => {
        drawerStatus = 'open';
        drawerLastOpenTime = Date.now();
        await triggerCashDrawer(printerName);
        return { success: true };
    });

    // Handle Get Printers (Restore if missing)
    ipcMain.handle('print:get-printers', async () => {
        if (!mainWindow) return [];
        return await mainWindow.webContents.getPrintersAsync();
    });

    // Hardware Health Polling - DEPRECATED/REMOVED per User Feedback
    ipcMain.handle('hardware:poll-status', async (event, { printerName }) => {
        return {
            printer: 'online', // Fallback to "online" to avoid red bulbs
            drawer: 'closed',
            message: 'Status Monitoring Disabled'
        };
    });

    // Manual Drawer Close (User Confirmation)
    ipcMain.handle('printer:close-drawer-manually', () => {
        drawerStatus = 'closed';
        return { success: true };
    });
}


function killProcesses() {
    if (laravelServer && !laravelServer.killed) treeKill(laravelServer.pid, 'SIGKILL');
    if (mysqlServer && !mysqlServer.killed) treeKill(mysqlServer.pid, 'SIGKILL');
}

async function startApp() {
    try {
        createSplashWindow();
        
        // Boost Process Priority (Apple Power-Mode Optimization)
        os.setPriority(os.constants.priority.PRIORITY_HIGH);
        
        await checkDiskSpace(200);
        logger.cleanup(); // Self-healing: Cleanup old logs and temp data

        // --- NEW: Clear Session Cache (Ensures Asset Freshness) ---
        // Only clear HTTP cache, NOT storage (cookies/localstorage) to preserve Login Session
        await session.defaultSession.clearCache();
        // await session.defaultSession.clearStorageData(); // DISABLE THIS to fix "Login Every Time"

        updateSplashStatus('Starting...');

        await new Promise(r => setTimeout(r, 700));



        await new Promise(r => setTimeout(r, 700));



        await startMySQL();

        updateSplashStatus('Loading database...');


        await new Promise(r => setTimeout(r, 700));



        await startLaravel(laravelPort);

        updateSplashStatus('Initializing application...');


        await waitForMySQL(MYSQL_PORT, 45000);
        logger.log('MySQL connection confirmed - proceeding with database setup');
        
        // --- Create database if it doesn't exist ---
        updateSplashStatus('Preparing database...');
        await createDatabase();
        
        // --- NEW: Perform Auto-Migrations ---
        updateSplashStatus('Checking database integrity...');
        await runMigrations();

        await waitForLaravel(laravelPort, 60000);
        
        updateSplashStatus('Establishing connection...');


        await new Promise(r => setTimeout(r, 700));

        // --- NEW: Load Remote Configuration ---
        updateSplashStatus('Syncing hardware profiles...');
        await fetchRemoteConfig();
        
        updateSplashStatus('Finalizing...');

        await new Promise(r => setTimeout(r, 700));



        
        // Register IPC BEFORE window creation
        setupAutoUpdater();
        createMainWindow();
    } catch (error) {
        logger.error('CRITICAL STARTUP ERROR: ' + error.message);
        logger.error('Stack trace: ' + error.stack);
        
        // Show error dialog to user
        const { dialog } = require('electron');
        if (splashWindow) {
            dialog.showErrorBox(
                'Startup Failed',
                `The application encountered a critical error during startup:\n\n${error.message}\n\nPlease check the logs at: ${path.join(app.getPath('userData'), 'app.log')}`
            );
        }
        
        killProcesses();
        app.quit();
    }
}


// --- SINGLE INSTANCE LOCK (Professional Startup) ---
const gotTheLock = app.requestSingleInstanceLock();
if (!gotTheLock) {
    app.quit();
} else {
    app.on('second-instance', (event, commandLine, workingDirectory) => {
        // Someone tried to run a second instance, we should focus our window.
        if (mainWindow) {
            if (mainWindow.isMinimized()) mainWindow.restore();
            mainWindow.maximize();
            mainWindow.focus();
        }
    });

    app.whenReady().then(startApp);
}
app.on('window-all-closed', () => { killProcesses(); app.quit(); });


// ============================================
// GNERATE RECEIPT HTML
// ============================================
function generateReceiptHtml(data) {
    const { id, date, staff, customer, items, sub_total, discount, total, paid, due, change, payment_method, config } = data;
    let itemsHtml = '';
    items.forEach(item => {
        itemsHtml += `<tr><td><div style="line-height:1.2;">${item.name}</div></td><td class="text-center">x${item.qty}</td><td class="text-right">${item.price}</td><td class="text-right font-bold">${item.total}</td></tr>`;
    });
    const fs = require('fs');
    let barcodeLib = '';
    try { barcodeLib = fs.readFileSync('d:\\Projects\\POS System\\public\\plugins\\JsBarcode.all.min.js', 'utf8'); } catch(e) {}

    return `
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;500;700&display=swap');
        
        /* Reset & Base */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: #fff;
            font-family: 'Roboto Mono', monospace;
            font-size: 11px;
            color: #000;
            width: 100%; /* Occupy full window (80mm) */
            padding-top: 5px;
            padding-bottom: 5px;
            overflow: hidden;
            -webkit-print-color-adjust: exact;
            display: flex;
            justify-content: center;
        }

        /* 
           THERMAL PRINTER SAFE AREA
           Standard 80mm paper has ~72mm printable width.
           We set a fixed container to match this exactly.
        */
        .receipt-container {
            width: 72mm; /* ~272px */
            /* No extra margin here, body centers it */
        }

        /* Typography helpers */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: 700; }
        .text-uppercase { text-transform: uppercase; }
        .text-sm { font-size: 11px; }
        .text-xs { font-size: 10px; }

        /* Layout Elements */
        .header-info { margin-bottom: 10px; }
        .logo-area img { max-width: 60%; height: auto; margin-bottom: 5px; }
        
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        .double-divider { border-top: 2px dashed #000; margin: 8px 0; }

        /* Tables - Fixed Layout */
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th { text-align: left; font-size: 10px; text-transform: uppercase; padding-bottom: 4px; border-bottom: 1px solid #000; white-space: nowrap; }
        td { padding: 4px 0; vertical-align: top; word-wrap: break-word; }
        
        .totals-table td { padding: 2px 0; }
        .grand-total { font-size: 16px; font-weight: 700; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 6px 0; margin-top: 5px; }

        .footer { margin-top: 15px; text-align: center; }
        .barcode-container { margin: 10px 0; display: flex; justify-content: center; }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Header -->
        <div class="text-center header-info">
            ${config.show_logo && config.logo_url ? `<div class="logo-area"><img src="${config.logo_url}" alt="Logo"></div>` : ''}
            ${config.show_site ? `<div class="font-bold text-uppercase" style="font-size: 16px; margin-bottom: 4px;">${config.site_name}</div>` : ''}
            
            <div class="text-xs">
                ${config.show_address && config.address ? `${config.address}<br>` : ''}
                ${config.show_phone && config.phone ? `Tel: ${config.phone}<br>` : ''}
                ${config.show_email && config.email ? `${config.email}` : ''}
            </div>
        </div>

        <div class="divider"></div>

        <!-- Order Metadata -->
        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
            <div class="text-left">
                Inv: <strong>#${id}</strong><br>
                ${date}
            </div>
            <div class="text-right">
                NTN: <strong>1620237071939</strong><br>
                Type: <strong>${payment_method || 'Cash'}</strong>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Customer / Station -->
         <div style="display: flex; justify-content: space-between;">
            <div class="text-left text-sm">
                <strong>Cust:</strong> ${customer ? customer.name : 'Walk-in'}
            </div>
            <div class="text-right text-sm">
                <strong>Station:</strong> ${staff || ''}
            </div>
        </div>

        <div class="double-divider"></div>

        <!-- Items -->
        <table>
            <thead>
                <tr>
                    <th width="45%">Item</th>
                    <th width="15%" class="text-center">Qty</th>
                    <th width="20%" class="text-right">Price</th>
                    <th width="20%" class="text-right">Amt</th>
                </tr>
            </thead>
            <tbody>
                ${itemsHtml}
            </tbody>
        </table>

        <div class="divider"></div>

        <!-- Totals -->
        <table class="totals-table">
            <tr>
                <td class="text-right" width="60%">Gross Total:</td>
                <td class="text-right font-bold" width="40%">${sub_total}</td>
            </tr>
            <tr>
                <td class="text-right">Total Discount:</td>
                <td class="text-right">(-${discount})</td>
            </tr>
            
            <tr><td colspan="2" style="height: 5px;"></td></tr>

            <tr class="grand-total">
                <td class="text-left" style="font-size: 14px;">NET PAYABLE</td>
                <td class="text-right" style="font-size: 18px;">${total}</td>
            </tr>
            
            <tr><td colspan="2" style="height: 5px;"></td></tr>

            ${parseFloat(due) > 0 ? `
            <tr>
                <td class="text-right text-uppercase" style="color: #000; font-size: 13px;">DUE BALANCE:</td>
                <td class="text-right font-bold" style="font-size: 14px;">${due}</td>
            </tr>` : ''}

            ${parseFloat(paid) > parseFloat(total.replace(/,/g,'')) ? `
            <tr>
                <td class="text-right text-uppercase" style="font-size: 13px;">CHANGE:</td>
                <td class="text-right font-bold" style="font-size: 14px;">${change}</td>
            </tr>` : ''}
        </table>

        <div class="divider"></div>

        <!-- Barcode -->
        <div class="barcode-container">
            <svg id="barcode"></svg>
        </div>

        <!-- Note -->
        ${config.show_note && config.footer_note ? `
        <div class="text-center text-sm" style="margin-top: 5px; margin-bottom: 5px;">
            ${config.footer_note}
        </div>` : ''}

        <div class="divider"></div>

        <!-- Software Credit -->
        <div class="text-center text-xs" style="margin-top: 5px; color: #666;">
            Software by <strong>SINYX</strong><br>
            Contact: +92 342 9031328
        </div>
    </div>

    <!-- Inline Barcode Lib -->
    <script>${barcodeLib}</script>
    
    <script>
        async function renderBarcode() {
            // 1. Generate Barcode
            try {
                if(typeof JsBarcode !== 'undefined') {
                    const orderId = "${id}";
                    JsBarcode("#barcode", "ORD" + orderId.padStart(8, '0'), {
                        format: "CODE128",
                        width: 1.5,
                        height: 40,
                        displayValue: true,
                        fontSize: 12,
                        margin: 0
                    });
                }
            } catch (e) { console.error("Barcode Failed: ", e); }
        }
        window.onload = renderBarcode;
    </script>

</body>
</html>`;
}

/**
 * GENERATE BARCODE ESC/POS (Manual Binary Path)
 * Bypasses libraries to prevent gibberish and ensure scannability.
 */
function generateBarcodeESC(data) {
    const { label, barcodeValue, mfgDate, expDate, labelSize, price, showPrice } = data;
    const isLarge = labelSize === 'large';
    const hasDates = isLarge && (mfgDate || expDate);
    const hasPrice = showPrice && price;

    const chunks = [];
    
    // 1. Initialize & Center
    chunks.push(Buffer.from([0x1B, 0x40])); // ESC @ (Init)
    chunks.push(Buffer.from([0x1B, 0x61, 0x01])); // ESC a 1 (Center)

    // 2. Label (Double Width/Height)
    chunks.push(Buffer.from([0x1D, 0x21, 0x11])); // GS ! 17 (Double width/height)
    chunks.push(Buffer.from(`${label}\n`));
    chunks.push(Buffer.from([0x1D, 0x21, 0x00])); // Reset size

    // 3. Price (Bold)
    if (hasPrice) {
        const pStr = (Math.round(parseFloat(price) * 100) / 100).toFixed(2);
        chunks.push(Buffer.from([0x1B, 0x45, 0x01])); // ESC E 1 (Bold)
        chunks.push(Buffer.from(`Rs. ${pStr}\n`));
        chunks.push(Buffer.from([0x1B, 0x45, 0x00])); // Reset Bold
    }

    // SPACER LINE (Explicit Feed 1 Line - User Verified)
    chunks.push(Buffer.from([0x1B, 0x64, 0x01]));

    // 4. Barcode (NATIVE GS k)
    const h = isLarge ? 80 : 60;
    const w = isLarge ? 3 : 2;

    // CODE 128 (GS k 73) requires a Start Code.
    // We use Code Set B ({B) for standard alphanumeric/digits.
    // { = 0x7B, B = 0x42.
    const codeSetB = "{B";
    const payload = codeSetB + barcodeValue;

    chunks.push(Buffer.from([
        0x1D, 0x68, h,       // Height
        0x1D, 0x77, w,       // Width
        0x1D, 0x48, 0x00,    // Disable HRI (We print manually)
        0x1D, 0x6B, 0x49, payload.length // GS k 73 (CODE128) + len
    ]));
    chunks.push(Buffer.from(payload));
    
    // 5. HUMAN READABLE TEXT (Manual Print for 100% Reliability)
    chunks.push(Buffer.from([0x0A])); // LF
    chunks.push(Buffer.from([0x1B, 0x61, 0x01])); // Center Align
    // Select Font B (Smaller) or Standard
    // chunks.push(Buffer.from([0x1B, 0x4D, 0x01])); 
    chunks.push(Buffer.from(barcodeValue));
    chunks.push(Buffer.from([0x0A])); // LF

    // 6. Dates (Small)
    if (hasDates) {
        // Updated prefixes per user request (M -> MFG, E -> EXP)
        const mfg = mfgDate ? `MFG:${new Date(mfgDate).toLocaleDateString('en-GB')}` : '';
        const exp = expDate ? `EXP:${new Date(expDate).toLocaleDateString('en-GB')}` : '';
        chunks.push(Buffer.from(`${mfg}   ${exp}\n`));
    }

    // 7. Flush (Increased to 6 lines to clear cutter - User Verified)
    chunks.push(Buffer.from([0x1B, 0x64, 0x06])); 
    chunks.push(Buffer.from([0x1D, 0x56, 0x41, 0x00])); // GS V 65 0 (AutoCut if supported)

    return Buffer.concat(chunks);
}

// ============================================
// FALLBACK BARCODE HTML (Graphical)
// ============================================
function generateBarcodeHtmlFallback(data) {
    const { label, barcodeValue, mfgDate, expDate, labelSize, price, showPrice } = data;
    const isLarge = labelSize === 'large';
    const hasDates = isLarge && (mfgDate || expDate);
    const hasPrice = showPrice && price;

    let receiptText = `{c:}\n`;
    receiptText += `{b:900}${label}\n`;
    if (hasPrice) {
        receiptText += `{b:900}Rs. ${(Math.round(parseFloat(price) * 100) / 100).toFixed(2)}\n`;
    }
    
    const w = isLarge ? 3 : 2;
    const h = isLarge ? 100 : 80;
    receiptText += `{code: ${barcodeValue}; type: code128; width: ${w}; height: ${h}}\n`;
    
    if (hasDates) {
        const mfg = mfgDate ? `M:${new Date(mfgDate).toLocaleDateString('en-GB')}` : '';
        const exp = expDate ? `E:${new Date(expDate).toLocaleDateString('en-GB')}` : '';
        receiptText += `--- \n`;
        receiptText += `{s:0.8}${mfg}   ${exp}\n`;
    }

    const svg = receiptline.transform(receiptText, {
        cpl: isLarge ? 42 : 32,
        encoding: 'cp437',
        command: 'svg'
    });

    return `
<!DOCTYPE html>
<html>
<head>
    <style>
        @page { margin: 0; size: auto; }
        body { margin: 0; padding: 0; width: ${isLarge ? '50mm' : '38mm'}; height: ${isLarge ? '30mm' : '25mm'}; display: flex; box-sizing: border-box; justify-content: center; align-items: center; background: #fff; overflow: hidden; }
        .container { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; transform: scale(0.9); }
        svg { width: 100%; height: auto; image-rendering: auto; }
    </style>
</head>
<body><div class="container">${svg}</div></body>
</html>`;
}

/**
 * Resolves the printer name to a valid OS printer.
 * If the requested name exists, it returns it.
 * If not, it finds the System Default printer and returns that.
 * This prevents silent failures when the Backend config doesn't match the Windows driver name.
 */
async function resolvePrinter(requestedName) {
    try {
        const printers = await mainWindow.webContents.getPrintersAsync();
        
        // 1. Exact Match?
        const exactMatch = printers.find(p => p.name === requestedName);
        if (exactMatch) return truncatePrinterName(exactMatch.name); // Using sanitized name

        // 2. Fallback to Default
        const defaultPrinter = printers.find(p => p.isDefault);
        if (defaultPrinter) {
            console.log(`[PRINTER FAILOVER]: Requested "${requestedName}" not found. Using Default "${defaultPrinter.name}"`);
            return truncatePrinterName(defaultPrinter.name);
        }

        // 3. Last Resort: Return original (will likely fail, but we tried)
        return requestedName;
    } catch (e) {
        console.error("Failed to resolve printers:", e);
        return requestedName;
    }
}

// Safety wrapper to handle any weird chars if needed (optional)
function truncatePrinterName(name) {
    return name; // Pass-through for now
}
