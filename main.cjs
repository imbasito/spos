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
        const { url, printerName } = options;
        console.log(`Print requested: ${url}`);
        
        // 1. Create a hidden window
        let printWindow = new BrowserWindow({
            show: false,
            webPreferences: { contextIsolation: true }
        });
        
        try {
            await printWindow.loadURL(url);
            
            // 2. Wait for render (barcodes etc)
            await new Promise(r => setTimeout(r, 1500));

            // 3. User requested "PDF Testing" mode (or physical print)
            // For this specific user request: "pdf main reciept... documents main save kare"
            
            const pdfPath = path.join(app.getPath('documents'), `Receipt_${Date.now()}.pdf`);
            const pdfData = await printWindow.webContents.printToPDF({
                printBackground: true,
                marginsType: 0,
                pageSize: { width: 80000, height: 297000 }, // 80mm width (Microns)
                printSelectionOnly: false,
                landscape: false
            });

            const fs = require('fs');
            fs.writeFileSync(pdfPath, pdfData);
            
            console.log(`PDF Saved to: ${pdfPath}`);
            printWindow.close();
            
            // Return success with the path
            return { success: true, message: `Saved to Documents: ${path.basename(pdfPath)}` };

        } catch (error) {
            console.error('Print/PDF Error:', error);
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
