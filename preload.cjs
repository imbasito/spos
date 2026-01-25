const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('updater', {
    check: () => ipcRenderer.send('updater:check'),
    download: () => ipcRenderer.send('updater:download'),
    install: () => ipcRenderer.send('updater:install'),
    onStatus: (callback) => ipcRenderer.on('updater:status', (event, ...args) => callback(...args)),
    onProgress: (callback) => ipcRenderer.on('updater:progress', (event, ...args) => callback(...args)),
    onReady: (callback) => ipcRenderer.on('updater:ready', (event, ...args) => callback(...args)),
});

contextBridge.exposeInMainWorld('electron', {
    printSilent: (url, printerName = null, htmlContent = null, jsonData = null) => ipcRenderer.invoke('print:silent', { url, printerName, htmlContent, jsonData }),
    getPrinters: () => ipcRenderer.invoke('print:get-printers'),
    openDirectory: () => ipcRenderer.invoke('dialog:openDirectory'),
});
