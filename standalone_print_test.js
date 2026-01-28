const receiptline = require('receiptline');
const fs = require('fs');
const path = require('path');
const { exec } = require('child_process');
const os = require('os');

const PRINTER_NAME = "POS80";

async function runCommand(cmd) {
    return new Promise((resolve) => {
        exec(cmd, (error, stdout, stderr) => {
            resolve({ error, stdout: stdout.trim(), stderr: stderr.trim() });
        });
    });
}

async function start() {
    console.log(`=== PRINTER ROBUST DIAGNOSIS ===`);
    console.log(`Target: ${PRINTER_NAME}`);

    const tempDir = os.tmpdir();
    const tempBin = path.join(tempDir, "diagnostic.bin");
    
    // Generate a simple raw buffer: Init + Text + 4 LF
    const rawBuffer = Buffer.from("\x1B\x40--- RAW START ---\nHello World\n--- RAW END ---\n\x0A\x0A\x0A\x0A");
    fs.writeFileSync(tempBin, rawBuffer);

    // Write PS1 file to avoid escaping issues
    const psFile = path.join(tempDir, "run_diag.ps1");
    const psContent = `
$printer = "${PRINTER_NAME}"
$binFile = "${tempBin.replace(/\\/g, '/')}"

$code = @"
using System;
using System.IO;
using System.Runtime.InteropServices;
public class RawPrinter {
    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Ansi)]
    public struct DOCINFOA {
        [MarshalAs(UnmanagedType.LPStr)] public string pDocName;
        [MarshalAs(UnmanagedType.LPStr)] public string pOutputFile;
        [MarshalAs(UnmanagedType.LPStr)] public string pDataType;
    }
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
    
    public static string Send(string pName, string fPath) {
        DOCINFOA di = new DOCINFOA();
        di.pDocName = "RAW_DIAG";
        di.pDataType = "RAW";
        IntPtr hPrinter = IntPtr.Zero;
        if (OpenPrinter(pName, out hPrinter, IntPtr.Zero)) {
            if (StartDocPrinter(hPrinter, 1, ref di)) {
                try {
                    byte[] bytes = File.ReadAllBytes(fPath);
                    IntPtr pAlloc = Marshal.AllocCoTaskMem(bytes.Length);
                    Marshal.Copy(bytes, 0, pAlloc, bytes.Length);
                    int written = 0;
                    bool ok = WritePrinter(hPrinter, pAlloc, bytes.Length, out written);
                    EndDocPrinter(hPrinter);
                    ClosePrinter(hPrinter);
                    Marshal.FreeCoTaskMem(pAlloc);
                    return "SENT_" + ok + " (Bytes: " + written + ")";
                } catch (Exception e) {
                    return "ERROR: " + e.Message;
                }
            }
            ClosePrinter(hPrinter);
            return "FAIL_START_DOC";
        }
        return "FAIL_OPEN";
    }
}
"@
Add-Type -TypeDefinition $code

Write-Host "--- SYSTEM INFO ---"
$p = Get-Printer -Name $printer -ErrorAction SilentlyContinue
if ($p) {
    Write-Host "Driver Name: " $p.DriverName
    Write-Host "Port Name:   " $p.PortName
    Write-Host "Status:      " $p.PrinterStatus
} else {
    Write-Host "ERROR: Printer $printer not found!"
}

Write-Host "\n--- SENDING RAW BYTES ---"
[RawPrinter]::Send($printer, $binFile)
`;
    fs.writeFileSync(psFile, psContent);

    console.log(`Executing Diagnostic Script...`);
    const result = await runCommand(`powershell -ExecutionPolicy Bypass -File "${psFile}"`);
    
    console.log("\nSTDOUT:\n" + result.stdout);
    if (result.stderr) console.error("\nSTDERR:\n" + result.stderr);

    console.log(`\n=== ACTION NEEDED ===`);
    console.log(`1. Paste the STDOUT above back to me.`);
    console.log(`2. Did the printer print "--- RAW START ---"?`);
}

start();
