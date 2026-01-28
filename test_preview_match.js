const fs = require('fs');
const path = require('path');
const { exec } = require('child_process');
const os = require('os');
const receiptline = require('receiptline'); // Ensure this is available, though we're doing manual largely

const PRINTER_NAME = "POS80";

// MOCK DATA - MATCHING THE PREVIEW
const testData = {
    label: "Super Cool T-Shirt (L)",
    barcodeValue: "SKU-99887766",
    price: "1250.00",
    showPrice: true,
    mfgDate: "2023-10-01",
    expDate: "2024-10-01", 
    labelSize: "large" // Changed to large to show dates
};

function generateExactPreviewMatch(data) {
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

    // --- SPACER TUNING ---
    // Reduced to 1 line feed (ESC d 1) based on "too much gap" feedback
    console.log("Adding 1-Line Spacer...");
    chunks.push(Buffer.from([0x1B, 0x64, 0x01])); 
    // ---------------------

    // 4. Barcode (NATIVE GS k)
    const h = isLarge ? 80 : 60;
    const w = isLarge ? 3 : 2;
    chunks.push(Buffer.from([
        0x1D, 0x68, h,       // Height
        0x1D, 0x77, w,       // Width
        0x1D, 0x48, 0x02,    // HRI Below
        0x1D, 0x6B, 0x49, barcodeValue.length
    ]));
    chunks.push(Buffer.from(barcodeValue));
    chunks.push(Buffer.from("\n"));

    // 5. Dates (Small)
    if (hasDates) {
        const mfg = mfgDate ? `M:${new Date(mfgDate).toLocaleDateString('en-GB')}` : '';
        const exp = expDate ? `E:${new Date(expDate).toLocaleDateString('en-GB')}` : '';
        chunks.push(Buffer.from(`${mfg}   ${exp}\n`));
    }

    // 6. Footer Flush (Increased to 6 lines to clear cutter)
    chunks.push(Buffer.from([0x1B, 0x64, 0x06])); // ESC d 6
    chunks.push(Buffer.from([0x1D, 0x56, 0x41, 0x00])); // AutoCut

    return Buffer.concat(chunks);
}

async function runCommand(cmd) {
    return new Promise((resolve) => {
        exec(cmd, (error, stdout, stderr) => {
            resolve({ error, stdout: stdout.trim(), stderr: stderr.trim() });
        });
    });
}

async function start() {
    console.log("Generating EXACT PREVIEW match...");
    const buffer = generateExactPreviewMatch(testData);
    
    const tempDir = os.tmpdir();
    const tempBin = path.join(tempDir, `preview_match.bin`);
    fs.writeFileSync(tempBin, buffer);

    const psContent = `
$printer = "${PRINTER_NAME}"
$binFile = "${tempBin.replace(/\\/g, '/')}"
$code = @"
using System;
using System.IO;
using System.Runtime.InteropServices;
public class RawPrinter {
    [DllImport("winspool.Drv", EntryPoint = "OpenPrinterA", SetLastError = true, CharSet = CharSet.Ansi)]
    public static extern bool OpenPrinter(string szPrinter, out IntPtr hPrinter, IntPtr pd);
    [DllImport("winspool.Drv", EntryPoint = "ClosePrinter", SetLastError = true)]
    public static extern bool ClosePrinter(IntPtr hPrinter);
    [DllImport("winspool.Drv", EntryPoint = "StartDocPrinterA", SetLastError = true, CharSet = CharSet.Ansi)]
    public static extern bool StartDocPrinter(IntPtr hPrinter, Int32 level, ref DOCINFOA di);
    [DllImport("winspool.Drv", EntryPoint = "EndDocPrinter", SetLastError = true)]
    public static extern bool EndDocPrinter(IntPtr hPrinter);
    [DllImport("winspool.Drv", EntryPoint = "WritePrinter", SetLastError = true)]
    public static extern bool WritePrinter(IntPtr hPrinter, IntPtr pBytes, Int32 dwCount, out Int32 dwWritten);
    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Ansi)]
    public struct DOCINFOA {
        [MarshalAs(UnmanagedType.LPStr)] public string pDocName;
        [MarshalAs(UnmanagedType.LPStr)] public string pOutputFile;
        [MarshalAs(UnmanagedType.LPStr)] public string pDataType;
    }
    public static bool Send(string pName, string fPath) {
        DOCINFOA di = new DOCINFOA();
        di.pDocName = "PREVIEW_TEST";
        di.pDataType = "RAW";
        IntPtr hPrinter = IntPtr.Zero;
        if (OpenPrinter(pName, out hPrinter, IntPtr.Zero)) {
            if (StartDocPrinter(hPrinter, 1, ref di)) {
                byte[] bytes = File.ReadAllBytes(fPath);
                IntPtr pAlloc = Marshal.AllocCoTaskMem(bytes.Length);
                Marshal.Copy(bytes, 0, pAlloc, bytes.Length);
                int written = 0;
                bool ok = WritePrinter(hPrinter, pAlloc, bytes.Length, out written);
                EndDocPrinter(hPrinter);
                ClosePrinter(hPrinter);
                Marshal.FreeCoTaskMem(pAlloc);
                return ok;
            }
        }
        return false;
    }
}
"@
Add-Type -TypeDefinition $code
[RawPrinter]::Send($printer, $binFile)
`;
    const psFile = path.join(tempDir, "print_preview.ps1");
    fs.writeFileSync(psFile, psContent);
    
    console.log("Sending to printer...");
    await runCommand(`powershell -ExecutionPolicy Bypass -File "${psFile}"`);
    console.log("Done. Please check the paper.");
}

start();
