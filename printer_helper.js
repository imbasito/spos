
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
