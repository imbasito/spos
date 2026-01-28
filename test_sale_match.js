const EscPosEncoder = require('esc-pos-encoder');
const fs = require('fs');
const { exec } = require('child_process');
const path = require('path');

// 1. Mock Data for a Sale (Standard POS Invoice)
const mockSaleData = {
    id: "99999",
    type: "sale", // Standard
    staff: "Administrator",
    customer: { name: "John Doe Test" },
    items: [
        { name: "Test Product A", qty: 2, price: "500.00", total: "1000.00" },
        { name: "Premium Widget", qty: 1, price: "150.50", total: "150.50" },
        { name: "Discount Item", qty: 5, price: "10.00", total: "50.00" }
    ],
    sub_total: "1200.50",
    discount: "50.50",
    total: "1150.00",
    paid: "1500.00",
    change: "350.00",
    due: "0.00",
    payment_method: "Cash",
    transaction_id: "TRX-123456789",
    
    // Config matching your verified setup
    config: {
        show_logo: true,
        show_site: true,
        site_name: "MITHAI POS",
        show_address: true,
        address: "123 Test Street, Lahore",
        phone: "0300-1234567",
        email: "test@mithai.com",
        show_note: true,
        footer_note: "Thank you for shopping!"
    }
};

// 2. Generate Receipt Command (Using Raw Commands verified in services/RawReceiptGenerator.js)
function generateReceipt() {
    const encoder = new EscPosEncoder();
    let receipt = encoder.initialize();

    const data = mockSaleData;
    const config = data.config;
    const width = 42; 
    
    // --- HEADER (Raw Layout) ---
    // Note: Removed Reset (0x1B, 0x40) to prevent artifact
    receipt.raw([0x1B, 0x61, 0x01]); // Align Center

    // NV Logo
    receipt.raw([0x1C, 0x70, 0x01, 0x00]); 
    receipt.newline(); // Spacer after logo

    // Site Info (Explicit Center)
    if (config.show_site) {
         receipt.raw([0x1B, 0x61, 0x01])
                .bold(true).size(2, 2).text((config.site_name).toUpperCase()).newline()
                .bold(false).size(1, 1);
    }
    // Explicit Center for Address Block
    receipt.raw([0x1B, 0x61, 0x01]).text(config.address).newline();
    receipt.raw([0x1B, 0x61, 0x01]).text(`Tel: ${config.phone}`).newline();
    receipt.raw([0x1B, 0x61, 0x01]).text("NTN: 1620237071939").newline();
    
    // SEPARATOR (Requested)
    receipt.text("-".repeat(width)).newline();

    // --- BODY (Align Center) ---
    receipt.raw([0x1B, 0x61, 0x01]); // Center
    
    // Layout: 
    // Inv: #...       Pay: ...
    // Date: ...       Trx: ...

    const invStr = `Inv: #${data.id}`;
    const methodStr = `Pay: ${data.payment_method}`;
    receipt.line(twoColumn(invStr, methodStr, width));
    
    const now = new Date();
    const timeStr = now.toLocaleString('en-GB');
    const trxStr = `Trx: ${data.transaction_id}`;
    receipt.line(twoColumn(timeStr, trxStr, width));
    
    receipt.line("-".repeat(width));

    const custStr = `Cust: ${data.customer.name}`;
    const staffStr = `STN: ${data.staff.substring(0,10)}`;
    receipt.line(twoColumn(custStr, staffStr, width));

    // Items
    receipt.line("-".repeat(width)).bold(true)
        .text(pad("ITEM", 18))
        .text(pad("QTY", 4, true))
        .text(pad("PRICE", 10, true))
        .text(pad("AMT", 10, true))
        .newline().bold(false);

    data.items.forEach(item => {
        let name = item.name.substring(0, 17);
        receipt
            .text(pad(name, 18))
            .text(pad("x" + item.qty, 4, true))
            .text(pad(item.price, 10, true))
            .bold(true).text(pad(item.total, 10, true)).bold(false)
            .newline();
    });

    receipt.line("-".repeat(width));

    // Totals
    receipt.line(twoColumn("Gross Total:", data.sub_total, width));
    receipt.line(twoColumn("Discount:", `(-${data.discount})`, width));
    
    receipt.line("-".repeat(width)).bold(true)
        .line(twoColumn("NET PAYABLE", data.total, width))
        .bold(false).line("-".repeat(width)).newline();

    receipt.line(twoColumn("Paid Amount:", data.paid, width));
    receipt.bold(true).line(twoColumn("CHANGE:", data.change, width)).bold(false);

    // --- FOOTER (Center) ---
    receipt.raw([0x1B, 0x61, 0x01]); // Center
    receipt.newline().line("-".repeat(width)).newline();
    
    if (config.show_note && config.footer_note) {
        receipt.raw([0x1B, 0x61, 0x01]); 
        receipt.text(config.footer_note).newline().newline();
    }
    
    receipt.raw([0x1B, 0x61, 0x01]); 
    receipt.bold(true).text("Software by SINYX").bold(false).newline()
           .text("Contact: +92 342 9031328");

    // Cut
    receipt.raw([0x1B, 0x64, 0x06]); // Feed 6 lines
    receipt.cut();

    return receipt.encode();
}

// Helpers
function twoColumn(left, right, width) {
    const leftStr = String(left || "");
    const rightStr = String(right || "");
    const spaces = width - leftStr.length - rightStr.length;
    return spaces > 0 ? leftStr + " ".repeat(spaces) + rightStr : leftStr + " " + rightStr;
}

function pad(str, width, alignRight = false) {
    let s = String(str || "");
    if (s.length > width) return s.substring(0, width);
    return alignRight ? s.padStart(width) : s.padEnd(width);
}

// 3. Execution (Print to POS80 using C# RawPrinter)
const payload = generateReceipt();
const tempDir = require('os').tmpdir();
const tempBin = path.join(tempDir, `sale_test_${Date.now()}.bin`);
fs.writeFileSync(tempBin, Buffer.from(payload));

console.log(`Payload Size: ${payload.length} bytes`);
console.log(`Saved to: ${tempBin}`);
console.log("Printing to 'POS80' using C# Raw Injection...");

// Robust C# PowerShell Script for Raw Printing
const psContent = `
$printer = "POS80"
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
        di.pDocName = "SALE_TEST";
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
$res = [RawPrinter]::Send($printer, $binFile)
Write-Host "Print Result: $res"
`;

const psFile = path.join(tempDir, "print_sale.ps1");
fs.writeFileSync(psFile, psContent);

exec(`powershell -ExecutionPolicy Bypass -File "${psFile}"`, (err, stdout, stderr) => {
    if (err) {
        console.error("Print Error:", err);
        return;
    }
    if (stderr) console.error("Stderr:", stderr);
    console.log("Output:", stdout.trim());
});
