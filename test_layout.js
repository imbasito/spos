const fs = require('fs');
const path = require('path');
const { exec } = require('child_process');
const os = require('os');

const PRINTER_NAME = "POS80";

function generateTestLabel(spacerType) {
    const chunks = [];
    
    // 1. Initialize & Center
    chunks.push(Buffer.from([0x1B, 0x40])); // ESC @ (Init)
    chunks.push(Buffer.from([0x1B, 0x61, 0x01])); // ESC a 1 (Center)

    // 2. Label (Double Width/Height)
    chunks.push(Buffer.from([0x1D, 0x21, 0x11])); 
    chunks.push(Buffer.from(`TEST LABEL\n`));
    chunks.push(Buffer.from([0x1D, 0x21, 0x00])); 

    // 3. Price (Bold)
    chunks.push(Buffer.from([0x1B, 0x45, 0x01])); 
    chunks.push(Buffer.from(`Rs. 1,250.00\n`));
    chunks.push(Buffer.from([0x1B, 0x45, 0x00])); 

    // --- SPACER EXPERIMENT ---
    if (spacerType === 'LF') {
        console.log("Using Spacer: 3x Line Feeds (0x0A)");
        chunks.push(Buffer.from([0x0A, 0x0A, 0x0A])); 
    } else if (spacerType === 'ESC_d') {
        console.log("Using Spacer: ESC d 4 (Feed 4 Lines)");
        chunks.push(Buffer.from([0x1B, 0x64, 0x04])); 
    } else if (spacerType === 'ESC_J') {
        console.log("Using Spacer: ESC J 100 (Feed 100 dots ~ 1cm)");
        chunks.push(Buffer.from([0x1B, 0x4A, 100])); 
    }
    // -------------------------

    // 4. Barcode
    const barcodeValue = "12345678";
    chunks.push(Buffer.from([
        0x1D, 0x68, 60,       // Height
        0x1D, 0x77, 2,        // Width
        0x1D, 0x48, 0x02,     // HRI Below
        0x1D, 0x6B, 0x49, barcodeValue.length
    ]));
    chunks.push(Buffer.from(barcodeValue));
    chunks.push(Buffer.from("\n"));

    // 5. Flux
    chunks.push(Buffer.from([0x0A, 0x0A, 0x0A, 0x0A])); 
    chunks.push(Buffer.from([0x1D, 0x56, 0x41, 0x00])); 

    return Buffer.concat(chunks);
}

async function runCommand(cmd) {
    return new Promise((resolve) => {
        exec(cmd, (error, stdout, stderr) => {
            resolve({ error, stdout: stdout.trim(), stderr: stderr.trim() });
        });
    });
}

async function sendToPrinter(buffer, label) {
    const tempDir = os.tmpdir();
    const tempBin = path.join(tempDir, `test_${label}.bin`);
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
        di.pDocName = "${label}";
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
    const psFile = path.join(tempDir, "print_job.ps1");
    fs.writeFileSync(psFile, psContent);
    await runCommand(`powershell -ExecutionPolicy Bypass -File "${psFile}"`);
}

async function start() {
    console.log("Printing TEST 1: Using 'ESC J' (Dot Feed)...");
    await sendToPrinter(generateTestLabel('ESC_J'), 'TEST_ESC_J');
    
    console.log("\nPrinting TEST 2: Using 'LF' (Line Feed)...");
    await sendToPrinter(generateTestLabel('LF'), 'TEST_LF');

    console.log("\nDone! Check which one has the GAP.");
}

start();
