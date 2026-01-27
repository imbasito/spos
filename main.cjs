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
const net = require('net');
const http = require('http');
const treeKill = require('tree-kill');
const os = require('os');


let laravelServer, mysqlServer, splashWindow, mainWindow;
let drawerStatus = 'closed'; // 'closed' or 'open'
let drawerLastOpenTime = 0;
const MYSQL_PORT = 3307;

let laravelPort = 8000;

const basePath = app.isPackaged ? process.resourcesPath : __dirname;

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
        console.log(logEntry.trim());
        try {
            fs.appendFileSync(this.logFile, logEntry);
        } catch (err) {
            console.error('Failed to write to log file:', err);
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
function startMySQL() {
    return new Promise((resolve) => {
        logger.log('Starting MySQL Server...');
        const mysqlPath = path.join(basePath, 'mysql', 'bin', 'mysqld.exe');
        const myIniPath = path.join(basePath, 'mysql', 'my.ini');
        mysqlServer = spawn(mysqlPath, [`--defaults-file=${myIniPath}`, '--console'], { cwd: basePath, windowsHide: true });
        mysqlServer.on('exit', (code) => { 
            if (!app.isQuitting) {
                logger.warn('MySQL exited unexpectedly. Restarting...');
                setTimeout(startMySQL, 1000); 
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
    const pName = printerName || "Default";
    console.log(`[DRAWER KICK]: Triggering on ${pName}`);
    
    // Tiny hidden window to send raw kick command through the driver
    const kickWindow = new BrowserWindow({ show: false, webPreferences: { nodeIntegration: true, contextIsolation: false } });
    const kickHtml = `<html><body><script>window.onload=()=>{ window.print(); window.close(); }</script>\x1b\x70\x00\x19\xfa</body></html>`;
    const tempPath = path.join(app.getPath('temp'), `kick_${Date.now()}.html`);
    const fs = require('fs');
    fs.writeFileSync(tempPath, kickHtml);
    
    try {
        await kickWindow.loadFile(tempPath);
        await kickWindow.webContents.print({ silent: true, deviceName: pName });
        if(!kickWindow.isDestroyed()) kickWindow.destroy();
        try { fs.unlinkSync(tempPath); } catch(e){}
    } catch (e) {
        if(!kickWindow.isDestroyed()) kickWindow.destroy();
        console.error("Drawer Kick Failed:", e.message);
    }
}

// ============================================
// AUTO-UPDATER & IPC
// ============================================
function setupAutoUpdater() {
    autoUpdater.autoDownload = false;
    ipcMain.on('updater:check', () => {
        if (!app.isPackaged) return mainWindow.webContents.send('updater:status', 'latest');
        autoUpdater.checkForUpdates().catch(err => mainWindow.webContents.send('updater:status', 'error', err.message));
    });
    ipcMain.on('updater:download', () => autoUpdater.downloadUpdate());
    ipcMain.on('updater:install', () => { killProcesses(); autoUpdater.quitAndInstall(); });
    
    // Diagnostic Log from Render
    ipcMain.on('log-from-render', (event, msg) => { console.log(`[RENDER LOG]: ${msg}`); });

    // Silent Printing / PDF IPC
    ipcMain.handle('print:silent', async (event, options) => {
        const { url, printerName, htmlContent, jsonData } = options;
        console.log(`[PRINT REQUEST]: ${jsonData ? 'Headless JSON' : 'Legacy'}`);
        
        const printWindow = new BrowserWindow({
            show: false, // Hidden as requested (PNG Capture)
            width: 302, 
            height: 1000,
            parent: mainWindow, 
            webPreferences: { 
                contextIsolation: false,
                nodeIntegration: true,
                paintWhenInitiallyHidden: true,
                backgroundThrottling: false // Ensure painting happens in background
            }
        });
        
        try {
            if (jsonData) {
                let htmlString = "";
                if (jsonData.type === 'barcode') {
                    htmlString = generateBarcodeHtml(jsonData);
                } else {
                    htmlString = generateReceiptHtml(jsonData);
                }
                
                const tempPath = path.join(app.getPath('temp'), `${jsonData.type || 'receipt'}_${Date.now()}.html`);
                const fs = require('fs');
                fs.writeFileSync(tempPath, htmlString);
                
                await printWindow.loadFile(tempPath);
                
                // 3. Reliable Height Detection & Capture (Maturity Plan)
                try {
                    // Wait for fonts & rendering
                    await printWindow.webContents.executeJavaScript('document.fonts.ready');
                    await new Promise(r => setTimeout(r, 600)); // Buffer for paint

                    const contentHeight = await printWindow.webContents.executeJavaScript('document.body.scrollHeight');
                    printWindow.setContentSize(302, Math.ceil(contentHeight) + 50);
                    
                    // Capture Page as PNG
                    const image = await printWindow.webContents.capturePage();
                    const receiptsDir = path.join(app.getPath('documents'), 'Receipts');
                    if (!require('fs').existsSync(receiptsDir)) require('fs').mkdirSync(receiptsDir);
                    
                    const filename = `${jsonData.type === 'barcode' ? 'Barcode' : 'Receipt'}_${jsonData.id || Date.now()}.png`;
                    const imagePath = path.join(receiptsDir, filename);
                    require('fs').writeFileSync(imagePath, image.toPNG());

                    // 4. Physical Print (Silent)
                    printWindow.webContents.print({ 
                        silent: true, 
                        deviceName: printerName, 
                        margins: { marginType: 'none' } 
                    }, (success, err) => {
                        if(!success) console.error("Physical Print Failed:", err);
                        else if (jsonData.type !== 'barcode') {
                            triggerCashDrawer(printerName);
                        }
                        if(!printWindow.isDestroyed()) printWindow.close();
                    });

                } catch (captureErr) {
                    console.error("Reliable capture failed:", captureErr);
                    if(!printWindow.isDestroyed()) printWindow.close();
                }

                return { success: true };



            } else {
                // Legacy URL/HTML support (Simplified)
                if (htmlContent) {
                    const tempPath = path.join(app.getPath('temp'), `legacy_${Date.now()}.html`);
                    require('fs').writeFileSync(tempPath, htmlContent);
                    await printWindow.loadFile(tempPath);
                } else {
                    await printWindow.loadURL(url);
                }
                await new Promise(r => setTimeout(r, 1000));
                await new Promise((resolve, reject) => {
                    printWindow.webContents.print({ silent: true, deviceName: printerName }, (s, e) => s ? resolve() : reject(e));
                });
                printWindow.close();
                return { success: true };
            }
        } catch (error) {
            console.error('[PRINT ERROR]:', error);
            if (!printWindow.isDestroyed()) printWindow.close();
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
        return mainWindow.webContents.getPrinters();
    });

    // Hardware Health Polling (Printer + Drawer)
    ipcMain.handle('hardware:poll-status', async (event, { printerName }) => {
        // 1. Auto-close drawer state after 10s as a "soft-sensor" fallback 
        // if we can't physically read the DK-port pin.
        if (drawerStatus === 'open' && (Date.now() - drawerLastOpenTime > 10000)) {
            drawerStatus = 'closed';
        }

        const res = {
            printer: 'offline',
            drawer: drawerStatus,
            message: 'Scanning...'
        };

        try {
            const printers = mainWindow.webContents.getPrinters();
            const target = printers.find(p => p.name === printerName) || printers.find(p => p.isDefault);
            
            if (target) {
                // Windows Status 0 = Ready
                res.printer = (target.status === 0) ? 'online' : 'offline';
                res.message = (target.status === 0) ? `Ready: ${target.name}` : `Offline: ${target.name}`;
            } else {
                res.message = 'Printer Not Linked';
            }
        } catch (e) {
            res.message = 'Poll Failed';
        }

        return res;
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
        updateSplashStatus('Starting...');

        await new Promise(r => setTimeout(r, 700));



        await new Promise(r => setTimeout(r, 700));



        await startMySQL();

        updateSplashStatus('Loading database...');


        await new Promise(r => setTimeout(r, 700));



        await startLaravel(laravelPort);

        updateSplashStatus('Initializing application...');


        await waitForMySQL(MYSQL_PORT, 45000);
        await waitForLaravel(laravelPort, 60000);
        
        updateSplashStatus('Establishing connection...');


        await new Promise(r => setTimeout(r, 700));



        
        updateSplashStatus('Finalizing...');

        await new Promise(r => setTimeout(r, 700));



        
        // Register IPC BEFORE window creation
        setupAutoUpdater();
        createMainWindow();
    } catch (error) {
        logger.error('Startup error: ' + error.message);
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

// ============================================
// GENERATE BARCODE HTML (Professional Label)
// ============================================
function generateBarcodeHtml(data) {
    const { label, barcodeValue, mfgDate, expDate, labelSize, price, showPrice } = data;
    const fs = require('fs');
    let barcodeLib = '';
    try { barcodeLib = fs.readFileSync('d:\\Projects\\POS System\\public\\plugins\\JsBarcode.all.min.js', 'utf8'); } catch(e) {}

    // Scaled for high-density 50mm label
    return `
<!DOCTYPE html>
<html>
<head>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { 
            width: 50mm; height: 30mm; 
            display: flex; justify-content: center; align-items: center;
            font-family: Arial, sans-serif;
            overflow: hidden;
            background: #fff;
        }
        .container { 
            width: 100%; text-align: center; padding: 2px;
            border: 1px solid transparent; /* Professional layout */
        }
        .product-name { font-size: 14px; font-weight: bold; margin-bottom: 2px; overflow: hidden; white-space: nowrap; }
        .price { font-size: 16px; font-weight: bold; margin-bottom: 2px; }
        .barcode-area { margin: 2px 0; }
        .dates { display: flex; justify-content: space-between; font-size: 10px; font-weight: bold; margin-top: 2px; border-top: 1px dashed #000; padding-top: 2px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="product-name">${label || 'PRODUCT'}</div>
        ${showPrice ? `<div class="price">Rs. ${parseFloat(price).toFixed(2)}</div>` : ''}
        <div class="barcode-area"><svg id="barcode"></svg></div>
        ${labelSize === 'large' && (mfgDate || expDate) ? `
        <div class="dates">
            <span>MFG: ${mfgDate}</span>
            <span>EXP: ${expDate}</span>
        </div>` : ''}
    </div>

    <script>${barcodeLib}</script>
    <script>
        window.onload = () => {
            if(typeof JsBarcode !== 'undefined') {
                JsBarcode("#barcode", "${barcodeValue}", {
                    format: "CODE128",
                    width: 1.8,
                    height: 50,
                    displayValue: true,
                    fontSize: 14
                });
            }
        }
    </script>

</body>
</html>`;
}
