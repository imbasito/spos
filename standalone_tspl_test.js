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
    console.log(`=== TSPL (LABEL LANGUAGE) DIAGNOSIS ===`);
    console.log(`Target: ${PRINTER_NAME}\n`);

    const tempDir = os.tmpdir();
    
    // TSPL Command Block
    // SIZE 40mm x 30mm (Typical small label)
    let tspl = `SIZE 40 mm, 30 mm\r\n`;
    tspl += `GAP 2 mm, 0\r\n`;
    tspl += `DIRECTION 1\r\n`;
    tspl += `CLS\r\n`;
    tspl += `TEXT 10,10,"3",0,1,1,"TSPL TEST"\r\n`;
    tspl += `BARCODE 10,50,"128",50,1,0,2,2,"12345678"\r\n`;
    tspl += `PRINT 1,1\r\n`;

    const tempBin = path.join(tempDir, "tspl.bin");
    fs.writeFileSync(tempBin, Buffer.from(tspl));

    const psFile = path.join(tempDir, "run_tspl.ps1");
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
    
    public static bool Send(string pName, string fPath) {
        DOCINFOA di = new DOCINFOA();
        di.pDocName = "TSPL_DIAG";
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
    fs.writeFileSync(psFile, psContent);

    console.log(`Executing TSPL Test...`);
    const result = await runCommand(`powershell -ExecutionPolicy Bypass -File "${psFile}"`);
    console.log("Result:", result.stdout);

    console.log(`\n=== CHECK PRINTER ===`);
    console.log(`If the printer prints a barcode and "TSPL TEST", we found the right language!`);
}

start();
