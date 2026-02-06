const { contextBridge, ipcRenderer } = require('electron');
console.log("[PRELOAD]: Bridge Initializing...");
try {
contextBridge.exposeInMainWorld('updater', {
    check: () => ipcRenderer.send('updater:check'),
    download: () => ipcRenderer.send('updater:download'),
    install: () => ipcRenderer.send('updater:install'),
    onStatus: (callback) => ipcRenderer.on('updater:status', (event, ...args) => callback(...args)),
    onProgress: (callback) => ipcRenderer.on('updater:progress', (event, ...args) => callback(...args)),
    onReady: (callback) => ipcRenderer.on('updater:ready', (event, ...args) => callback(...args)),
});

contextBridge.exposeInMainWorld('electron', {
    isElectron: true,
    printSilent: (url, printerName = null, htmlContent = null, jsonData = null) => ipcRenderer.invoke('print:silent', { url, printerName, htmlContent, jsonData }),
    getPrinters: () => ipcRenderer.invoke('print:get-printers'),
    pollStatus: (printerName) => ipcRenderer.invoke('hardware:poll-status', { printerName }),
    closeDrawerManually: () => ipcRenderer.invoke('printer:close-drawer-manually'),
    openDirectory: () => ipcRenderer.invoke('dialog:openDirectory'),
    openDrawer: (printerName = null) => ipcRenderer.invoke('printer:open-drawer', { printerName }),
    getRemoteConfig: () => ipcRenderer.invoke('config:get-remote'),
    // Initialization recovery handlers
    retryInit: () => ipcRenderer.invoke('init:retry'),
    restoreBackup: () => ipcRenderer.invoke('init:restore-backup'),
    showLogs: () => ipcRenderer.invoke('init:show-logs'),
});

console.log("[PRELOAD]: Bridge Exposure Complete");
} catch(e) { console.error("[PRELOAD ERROR]:", e); }
