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
    console.log(`=== DIRECT BINARY BARCODE DIAGNOSIS ===`);
    console.log(`Target: ${PRINTER_NAME}\n`);

    const tempDir = os.tmpdir();
    
    // 1. NATIVE ESC/POS BARCODE BYTES (CODE 128)
    // GS k m n d1...dn (Command Type B)
    // GS = 1D, k = 6B, m = 49 (73), n = length, d = data
    const barcodeData = "12345678";
    const buffer = Buffer.concat([
        Buffer.from([0x1B, 0x40]), // Init Printer
        Buffer.from("\n--- DIRECT BYTES BARCODE ---\n"),
        Buffer.from([0x1D, 0x68, 0x50]), // GS h 80 (Height = 80 dots)
        Buffer.from([0x1D, 0x77, 0x02]), // GS w 2 (Width = 2)
        Buffer.from([0x1D, 0x48, 0x02]), // GS H 2 (HRI Position = Below)
        Buffer.from([0x1D, 0x6B, 0x49, barcodeData.length]), // GS k 73 len
        Buffer.from(barcodeData),
        Buffer.from("\n--- END OF BARCODE ---\n\n\n\n\n")
    ]);

    const tempBin = path.join(tempDir, "direct_barcode.bin");
    fs.writeFileSync(tempBin, buffer);

    // 2. WIDTH TEST (RAW 32 CPL)
    const ruler32 = "12345678901234567890123456789012\n"; // Exactly 32 chars
    const ruler42 = "123456789012345678901234567890123456789012\n"; // 42 chars
    const widthTest = Buffer.from("\x1B\x40RAW WIDTH TEST:\n32: " + ruler32 + "42: " + ruler42 + "\n\n\n\n\n");
    const tempWidth = path.join(tempDir, "width_test.bin");
    fs.writeFileSync(tempWidth, widthTest);

    const psFile = path.join(tempDir, "run_direct.ps1");
    const psContent = `
$printer = "${PRINTER_NAME}"
$binBarcode = "${tempBin.replace(/\\/g, '/')}"
$binWidth = "${tempWidth.replace(/\\/g, '/')}"

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
        di.pDocName = "DIRECT_TEST";
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

Write-Host "Sending Direct Barcode..."
[RawPrinter]::Send($printer, $binBarcode)
Write-Host "Sending Width Test..."
[RawPrinter]::Send($printer, $binWidth)
`;
    fs.writeFileSync(psFile, psContent);

    console.log(`Executing Direct Binary Test...`);
    const result = await runCommand(`powershell -ExecutionPolicy Bypass -File "${psFile}"`);
    console.log(result.stdout);

    console.log(`\n=== CHECK PRINTER NOW ===`);
    console.log(`1. Do you see a Barcode now?`);
    console.log(`2. Does the "32" ruler wrap, or is it on one line?`);
}

start();
