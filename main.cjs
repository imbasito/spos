require('v8-compile-cache');
const { app, BrowserWindow, dialog, ipcMain } = require('electron');

// Performance Optimizations
app.commandLine.appendSwitch('disable-http-cache');
app.commandLine.appendSwitch('disable-spellcheck');
const { autoUpdater } = require('electron-updater');
const { spawn } = require('child_process');
const path = require('path');
const net = require('net');
const http = require('http');
const treeKill = require('tree-kill');

let laravelServer, mysqlServer, splashWindow, mainWindow;
const MYSQL_PORT = 3307;
let laravelPort = 8000;

const basePath = app.isPackaged ? process.resourcesPath : __dirname;

// ============================================
// HEALTH CHECK UTILITIES
// ============================================
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
                console.error('Disk check failed:', error);
                return resolve(true); // Fail open if check fails
            }
            
            let freeBytes = 0;
            if (process.platform === 'win32') {
                const match = stdout.match(/FreeSpace=(\d+)/);
                if (match) freeBytes = parseInt(match[1], 10);
            } else {
                // simple df parsing fallback
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
                if (Date.now() - startTime > timeout) {
                    reject(new Error(`MySQL connection timeout after ${timeout/1000}s`));
                } else {
                    setTimeout(check, 1000);
                }
            });
            
            socket.on('timeout', () => {
                socket.destroy();
                if (Date.now() - startTime > timeout) {
                    reject(new Error(`MySQL connection timeout after ${timeout/1000}s`));
                } else {
                    setTimeout(check, 1000);
                }
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
                if (Date.now() - startTime > timeout) {
                    reject(new Error(`Laravel connection timeout after ${timeout/1000}s`));
                } else {
                    setTimeout(check, 1000);
                }
            });
            
            req.setTimeout(3000, () => {
                req.destroy();
                if (Date.now() - startTime > timeout) {
                    reject(new Error(`Laravel connection timeout after ${timeout/1000}s`));
                } else {
                    setTimeout(check, 1000);
                }
            });
        };
        
        setTimeout(check, 2000);
    });
}

// ============================================
// SPLASH SCREEN
// ============================================
function createSplashWindow() {
    splashWindow = new BrowserWindow({
        width: 600,
        height: 450,
        frame: false,
        transparent: true,
        alwaysOnTop: true,
        resizable: false,
        skipTaskbar: false,
        icon: path.join(basePath, 'pos-icon.ico'),
        webPreferences: {
            nodeIntegration: false,
            contextIsolation: true
        }
    });

    const splashPath = app.isPackaged
        ? path.join(process.resourcesPath, 'splash.html')
        : path.join(__dirname, 'splash.html');

    splashWindow.loadFile(splashPath);
    splashWindow.center();
}

function updateSplashStatus(message) {
    if (splashWindow && !splashWindow.isDestroyed()) {
        splashWindow.webContents.executeJavaScript(
            `if(document.getElementById('status-text')) document.getElementById('status-text').innerText = '${message}';`
        ).catch(() => {});
    }
}

// ============================================
// SERVER STARTUP
// ============================================

// ============================================
// SERVER STARTUP
// ============================================

function startMySQL() {
    return new Promise((resolve) => {
        const mysqlPath = path.join(basePath, 'mysql', 'bin', 'mysqld.exe');
        const myIniPath = path.join(basePath, 'mysql', 'my.ini');

        console.log('Starting MySQL server...');

        // Revert to simple spawning using my.ini defaults (which has port=3307)
        // Or explicitly pass the known port if needed, but my.ini is safest source of truth
        mysqlServer = spawn(mysqlPath, [`--defaults-file=${myIniPath}`, '--console'], {
            cwd: basePath,
            windowsHide: true
        });

        mysqlServer.stdout.on('data', data => console.log(`[MySQL] ${data}`));
        mysqlServer.stderr.on('data', data => console.log(`[MySQL] ${data}`));

        // Silent Crash Recovery
        mysqlServer.on('exit', (code, signal) => {
            console.log(`[MySQL] Process exited with code ${code} signal ${signal}`);
            
            if (!app.isQuitting) {
                console.log('[MySQL] Crash detected! Restarting automatically...');
                setTimeout(() => {
                    startMySQL();
                }, 1000); 
            }
        });

        resolve();
    });
}

function startLaravel(port) {
    return new Promise((resolve) => {
        const phpPath = path.join(basePath, 'php', 'php.exe');

        console.log(`Starting Laravel server on port ${port}...`);

        laravelServer = spawn(phpPath, ['artisan', 'serve', `--host=127.0.0.1`, `--port=${port}`], {
            cwd: basePath,
            windowsHide: true
        });

        laravelServer.stdout.on('data', data => console.log(`[Laravel] ${data}`));
        laravelServer.stderr.on('data', data => console.log(`[Laravel] ${data}`));

        resolve();
    });
}

// ============================================
// MAIN WINDOW
// ============================================
function createMainWindow() {
    mainWindow = new BrowserWindow({
        width: 1280,
        height: 800,
        title: "SPOS - Point of Sale System",
        icon: path.join(basePath, 'pos-icon.ico'),
        autoHideMenuBar: true,
        show: false,
        backgroundColor: '#FFFDF9',
        webPreferences: {
            contextIsolation: true,
            preload: path.join(__dirname, 'preload.cjs'),
            zoomFactor: 0.85
        }
    });

    mainWindow.loadURL(`http://127.0.0.1:${laravelPort}`);

    mainWindow.webContents.on('did-finish-load', () => {
        if (splashWindow && !splashWindow.isDestroyed()) {
            splashWindow.close();
        }
        mainWindow.show();
        mainWindow.maximize();
        mainWindow.focus();
        mainWindow.setAlwaysOnTop(true);
        setTimeout(() => mainWindow.setAlwaysOnTop(false), 300);
    });

    mainWindow.on('closed', () => {
        killProcesses();
        app.quit();
    });
}

// ============================================
// AUTO-UPDATER
// ============================================
function setupAutoUpdater() {
    autoUpdater.autoDownload = false; // Important for our manual flow

    // Initial silent check
    if (app.isPackaged) {
        autoUpdater.checkForUpdatesAndNotify().catch(err => console.error('Silent update check failed:', err));
    }

    // IPC Listeners
    ipcMain.on('updater:check', () => {
        console.log('Manual update check requested...');
        
        // If not packaged, we can't really check GitHub easily without dev-app-update.yml
        if (!app.isPackaged) {
            console.log('Update check skipped (Not Packaged)');
            setTimeout(() => {
                mainWindow.webContents.send('updater:status', 'latest');
            }, 1000);
            return;
        }

        autoUpdater.checkForUpdates()
            .then(result => {
                console.log('Update check result:', result ? 'Update found' : 'No update');
                // result might be null if no update
                if (!result || !result.updateInfo || result.updateInfo.version === app.getVersion()) {
                    mainWindow.webContents.send('updater:status', 'latest');
                }
            })
            .catch(err => {
                console.error('Check for updates error:', err);
                mainWindow.webContents.send('updater:status', 'error', err.message);
            });
    });

    ipcMain.on('updater:download', () => {
        console.log('Starting update download...');
        autoUpdater.downloadUpdate();
    });

    ipcMain.on('updater:install', () => {
        console.log('Restarting to install update...');
        killProcesses();
        autoUpdater.quitAndInstall();
    });

    // Event Handlers
    autoUpdater.on('update-available', (info) => {
        console.log('Update-available event:', info.version);
        mainWindow.webContents.send('updater:status', 'available', info);
    });

    autoUpdater.on('update-not-available', () => {
        console.log('Update-not-available event');
        mainWindow.webContents.send('updater:status', 'latest');
    });

    autoUpdater.on('download-progress', (progressObj) => {
        mainWindow.webContents.send('updater:progress', progressObj);
    });

    autoUpdater.on('update-downloaded', (info) => {
        console.log('Update-downloaded event:', info.version);
        mainWindow.webContents.send('updater:ready', info);
    });

    autoUpdater.on('error', (err) => {
        console.error('Auto-updater error event:', err);
        mainWindow.webContents.send('updater:status', 'error', err.message);
    });

    // Silent Printing / PDF IPC
    ipcMain.handle('print:silent', async (event, options) => {
        const { url, printerName, htmlContent, jsonData } = options; // jsonData added for Headless
        console.log(`Print requested. URL: ${url || 'Headless'}, Mode: ${jsonData ? 'Headless' : (htmlContent ? 'HTML' : 'URL')}`);
        
        // 1. Create a hidden window
        const printWindow = new BrowserWindow({
            show: false,
            width: 302, // Exactly 80mm at 96 DPI
            height: 600, // Initial height, will be adjusted
            parent: mainWindow, 
            webPreferences: { 
                contextIsolation: false,
                nodeIntegration: true
            }
        });
        
        try {
            if (jsonData) {
                 // STRATEGY C: MAIN-PROCESS RENDERING (Best Reliability)
                 // We generate the HTML string HERE, so we don't rely on hidden window JS execution.
                 const htmlString = generateReceiptHtml(jsonData);
                 
                 const tempPath = path.join(app.getPath('temp'), `receipt_${Date.now()}.html`);
                 const fs = require('fs');
                 fs.writeFileSync(tempPath, htmlString);
                 
                 console.log(`Printing Generated Receipt: ${tempPath}`);
                 await printWindow.loadFile(tempPath);
                 
                 // WAIT FOR READY SIGNAL (Fixes White Screen Race Condition)
                 let pageHeightPx = 600; // default
                 
                 await new Promise((resolve, reject) => {
                     const timeout = setTimeout(() => {
                         console.warn("Print Render Timeout - Proceeding anyway");
                         resolve(); 
                     }, 8000); // Increased timeout for CDN

                     ipcMain.once('ready-to-print', (event, dims) => {
                         console.log("Renderer Signal: Ready to Print", dims);
                         if(dims && dims.height) pageHeightPx = dims.height;
                         clearTimeout(timeout);
                         resolve();
                     });
                 });
                 
                 // 2. Extra Paint Delay & Focus (Critical for PDF content)
                 printWindow.focus(); 
                 await new Promise(r => setTimeout(r, 200));

                 // 3. Print
                 const cleanPrinterName = printerName ? printerName.toLowerCase() : "";
                 const isPdfPrinter = cleanPrinterName.includes("pdf") || cleanPrinterName.includes("microsoft") || !printerName;

                 console.log("Printing Mode:", isPdfPrinter ? "PDF Generator" : "Hardware Printer", "Name:", printerName);

                 if (isPdfPrinter) {
                     // PDF GENERATION START
                     try {
                        const documentsPath = app.getPath('documents');
                        const pdfPath = path.join(documentsPath, `Receipt_${Date.now()}.pdf`);
                        
                        console.log("Generating PDF at:", pdfPath);
                        
                        // Dynamic Height Calculation (Microns)
                        // 1 px = 264.58 microns approx
                        const heightMicrons = Math.ceil(pageHeightPx * 264.58) + 1000; 
                        
                         // Force width 80mm in microns = 80000
                         const pdfData = await printWindow.webContents.printToPDF({
                             printBackground: true,
                             marginsType: 0, // Hardcode 0 margins
                             pageSize: { width: 80000, height: heightMicrons }, 
                             preferCSSPageSize: false, 
                             printSelectionOnly: false
                         });
            
                        const fs = require('fs');
                        fs.writeFileSync(pdfPath, pdfData);
                        console.log("PDF Saved Successfully");
                        
                        dialog.showMessageBox({
                            type: 'info',
                            title: 'PDF Saved',
                            message: `Receipt saved to Documents.\n\n${pdfPath}`
                        });
                     } catch (pdfErr) {
                         console.error("PDF Generation Failed:", pdfErr);
                         dialog.showErrorBox("PDF Error", "Failed to clear save PDF: " + pdfErr.message);
                     }
                 } else {
                     // PHYSICAL
                     await new Promise((resolve, reject) => {
                         printWindow.webContents.print({
                             silent: true,
                             deviceName: printerName,
                             printBackground: false, 
                             margins: { marginType: 'none' },
                             copies: 1
                         }, (success, err) => success ? resolve() : reject(new Error(err)));
                     });
                 }

                 // Cleanup
                 try {
                     printWindow.close();
                     setTimeout(() => { try { fs.unlinkSync(tempPath); } catch(e){} }, 5000);
                 } catch(cleanupErr) { console.error("Cleanup Error", cleanupErr); }
                 
                 return { success: true, message: "Print Job Completed" };

            } else if (htmlContent) {
                 // STRATEGY B: TEMP FILE (Legacy Fix)
                 const tempPath = path.join(app.getPath('temp'), `print_job_${Date.now()}.html`);
                 const fs = require('fs');
                 fs.writeFileSync(tempPath, htmlContent);
                 await printWindow.loadFile(tempPath);
                 setTimeout(() => { try { fs.unlinkSync(tempPath); } catch(e){} }, 10000);

            } else {
                 // STRATEGY A: URL (Legacy Auth Injection)
                 const cookies = await session.defaultSession.cookies.get({});
                 const cookieString = cookies.map(c => `${c.name}=${c.value}`).join('; ');
                 await printWindow.loadURL(url, {
                     userAgent: mainWindow.webContents.getUserAgent(),
                     extraHeaders: `Cookie: ${cookieString}\n`
                 });
            }
            
            // 2. Wait for paint
            await new Promise(r => setTimeout(r, 500));

            // 3. Print
            const cleanPrinterName = printerName ? printerName.toLowerCase() : "";
            const isPdfPrinter = cleanPrinterName.includes("pdf") || cleanPrinterName.includes("microsoft") || !printerName;

            if (!isPdfPrinter) {
                 // --- PHYSICAL ---
                 await new Promise((resolve, reject) => {
                     printWindow.webContents.print({
                         silent: true,
                         deviceName: printerName,
                         printBackground: false, // Thermal usually BW
                         margins: { marginType: 'none' }, // Critical for 80mm
                         copies: 1
                     }, (success, errorType) => {
                         if (!success) reject(new Error(errorType));
                         else resolve();
                     });
                 });
                 printWindow.close();
                 return { success: true, message: `Sent to ${printerName}` };

            } else {
                 // --- PDF ---
                 const pdfPath = path.join(app.getPath('documents'), `Receipt_${Date.now()}.pdf`);
                 const pdfData = await printWindow.webContents.printToPDF({
                    printBackground: true,
                    marginsType: 1, // No margins
                    pageSize: { width: 80000, height: 297000 }, // 80mm
                    printSelectionOnly: false
                });
    
                const fs = require('fs');
                fs.writeFileSync(pdfPath, pdfData);
                printWindow.close();

                dialog.showMessageBox({
                    type: 'info',
                    title: 'PDF Saved',
                    message: `Receipt saved.\n\n${pdfPath}`
                });
                
                return { success: true, message: `Saved: ${path.basename(pdfPath)}` };
            }

        } catch (error) {
            console.error('Print Error:', error);
            if (!printWindow.isDestroyed()) printWindow.close();
            return { success: false, error: error.message };
        }
    });

    ipcMain.handle('print:get-printers', async () => {
        return await mainWindow.webContents.getPrintersAsync();
    });
}

// ============================================
// ERROR HANDLING
// ============================================
function showErrorDialog(title, message) {
    return dialog.showMessageBox({
        type: 'error',
        title: title,
        message: message,
        buttons: ['Retry', 'Exit'],
        defaultId: 0
    });
}

// ============================================
// CLEANUP
// ============================================
function killProcesses() {
    console.log('Killing processes...');
    
    if (laravelServer && !laravelServer.killed) {
        treeKill(laravelServer.pid, 'SIGKILL', err => {
            if (err) console.error('Failed to kill Laravel:', err);
            else console.log('Laravel server killed.');
        });
    }

    if (mysqlServer && !mysqlServer.killed) {
        treeKill(mysqlServer.pid, 'SIGKILL', err => {
            if (err) console.error('Failed to kill MySQL:', err);
            else console.log('MySQL server killed.');
        });
    }
}

// ============================================
// MAIN STARTUP SEQUENCE
// ============================================
async function startApp() {
    try {
        createSplashWindow();
        
        // Health Checks
        await checkDiskSpace(200);

        updateSplashStatus('Starting database...');
        await startMySQL();

        updateSplashStatus('Starting server...');
        await startLaravel(laravelPort);

        updateSplashStatus('Connecting to database...');
        await waitForMySQL(MYSQL_PORT, 45000);

        updateSplashStatus('Loading application...');
        await waitForLaravel(laravelPort, 60000);

        updateSplashStatus('Opening POS...');
        createMainWindow();

        // Initialize auto-updater
        setupAutoUpdater();

    } catch (error) {
        console.error('Startup error:', error);
        
        if (splashWindow && !splashWindow.isDestroyed()) {
            splashWindow.hide();
        }

        const result = await showErrorDialog(
            'Startup Failed',
            `${error.message}\n\nWould you like to retry?`
        );

        if (result.response === 0) {
            killProcesses();
            setTimeout(startApp, 2000);
        } else {
            killProcesses();
            app.quit();
        }
    }
}

// ============================================
// APP LIFECYCLE
// ============================================
app.whenReady().then(startApp);

app.on('window-all-closed', () => {
    killProcesses();
    if (process.platform !== 'darwin') app.quit();
});

app.on('before-quit', () => {
    killProcesses();
});

// ============================================
// HELPER: Generate HTML String from JSON (1:1 Blade Replica)
// ============================================
function generateReceiptHtml(data) {
    const { id, date, staff, customer, items, sub_total, discount, total, paid, due, change, payment_method, config } = data;
    
    // Items Row Logic
    let itemsHtml = '';
    items.forEach(item => {
        itemsHtml += `
        <tr>
            <td>
                <div style="line-height: 1.2;">${item.name}</div>
            </td>
            <td class="text-center">x${item.qty}</td>
            <td class="text-right">${item.price}</td>
            <td class="text-right font-bold">${item.total}</td>
        </tr>`;
    });


    // Read Local Barcode Lib
    const fs = require('fs');
    let barcodeLib = '';
    try {
        barcodeLib = fs.readFileSync('d:\\Projects\\POS System\\public\\plugins\\JsBarcode.all.min.js', 'utf8');
    } catch(e) { console.error("Could not read local barcode lib", e); }

    return `
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #${id}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;500;700&display=swap');

        /* Strict Zero Margins */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        @page { 
            margin: 0; 
            size: 80mm auto; 
        }
        
        html, body {
            margin: 0;
            padding: 0;
            width: 76mm; /* Adjusted for better fit on 80mm rolls */
            background-color: white;
            overflow: hidden;
        }

        body { 
            width: 76mm; 
            padding-bottom: 5px;
            font-family: 'Roboto Mono', monospace; 
            font-size: 11px; 
            color: #000;
            -webkit-print-color-adjust: exact;
        }

        /* Typography */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: 700; }
        .text-uppercase { text-transform: uppercase; }
        .text-sm { font-size: 11px; }
        .text-xs { font-size: 10px; }

        /* Layout */
        .logo-area img { max-width: 60%; height: auto; margin-bottom: 8px; }
        .header-info { margin-bottom: 15px; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        .double-divider { border-top: 2px dashed #000; margin: 10px 0; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 11px; text-transform: uppercase; padding-bottom: 4px; border-bottom: 1px solid #000; }
        td { padding: 4px 0; vertical-align: top; }
        
        .totals-table td { padding: 2px 0; }
        .grand-total { font-size: 16px; font-weight: 700; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 8px 0; margin-top: 5px; }
        
        .footer { margin-top: 20px; text-align: center; }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Header -->
        <div class="text-center header-info">
            ${config.show_logo && config.logo_url ? `<div class="logo-area"><img src="${config.logo_url}" alt="Store Logo"></div>` : ''}
            
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
                NTN: <strong>${config.ntn || '00000000'}</strong><br>
                Type: <strong>${payment_method || 'Cash'}</strong>
            </div>
        </div>

        <!-- Customer / Station -->
        <div class="divider"></div>
        <div style="display: flex; justify-content: space-between;">
            <div class="text-left text-sm">
                <strong>Cust:</strong> ${customer ? customer.name : 'Walk-in'}
            </div>
            <div class="text-right text-sm">
                <strong>Station:</strong> ${staff}
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
        <div class="barcode-container" style="text-align: center;">
            <svg id="barcode"></svg>
        </div>

        <!-- Note -->
        ${config.show_note && config.footer_note ? `
        <div class="text-center text-sm" style="margin-top: 5px; margin-bottom: 5px;">
            ${config.footer_note}
        </div>` : ''}

        <div class="divider"></div>

        <!-- Software Credit -->
        <div class="text-center text-xs" style="margin-top: 5px; color: #666; margin-bottom: 0px; padding-bottom: 5px;">
            Software by <strong>SINYX</strong><br>
            Contact: +92 342 9031328
        </div>
    </div>

    <!-- Inline Barcode Lib -->
    <script>${barcodeLib}</script>
    
    <script>
        const { ipcRenderer } = require('electron');
        
        async function signalReady() {
            // 1. Wait for Google Fonts to be ready (Prevents blank text)
            try {
                await document.fonts.ready;
            } catch(e) {}

            // 2. Generate Barcode
            try {
                if(typeof JsBarcode !== 'undefined') {
                    const orderId = "${id}";
                    const paddedId = orderId.toString().padStart(8, '0');
                    JsBarcode("#barcode", "ORD" + paddedId, {
                        format: "CODE128",
                        width: 1.5,
                        height: 40,
                        displayValue: true,
                        fontSize: 12,
                        margin: 0,
                        textMargin: 0
                    });
                }
            } catch (e) { console.error("Barcode Error", e); }

            // 3. Final Paint Delay (Ensures SVG is rendered)
            setTimeout(() => {
                 const bodyHeight = document.body.scrollHeight;
                 ipcRenderer.send('ready-to-print', { height: bodyHeight });
            }, 500); // 500ms is enough once fonts are ready
        }

        window.onload = signalReady;
    </script>
</body>
</html>`;
}

