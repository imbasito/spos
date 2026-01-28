const fs = require('fs');
const path = require('path');
const { exec } = require('child_process');
const os = require('os');
const EscPosEncoder = require('esc-pos-encoder');

const PRINTER_NAME = "POS80";

// MOCK DATA - REFUND
const refundData = {
    id: "REF-001",
    order_id: "ORD-998877",
    type: 'refund',
    staff: "Administrator",
    customer: { name: "John Doe" },
    payment_method: "Cash",
    items: [
        { name: "Faulty Keyboard", qty: 1, price: "1500.00", total: "1500.00" },
        { name: "USB Cable", qty: 2, price: "250.00", total: "500.00" }
    ],
    total: "2000.00",
    order_summary: {
        original_total: "5000.00",
        total_refunded: "2000.00",
        adjusted_total: "3000.00"
    },
    config: {
        site_name: "MY SHOP",
        address: "123 Market St, City",
        phone: "0300-1234567",
        show_logo: true,
        show_site: true,
        show_address: true,
        show_phone: true,
        show_note: true,
        footer_note: "Thank you for shopping!"
    }
};

class RefundTester {
    constructor() {
        this.encoder = new EscPosEncoder();
    }

    twoColumn(left, right, width) {
        const leftStr = String(left || "");
        const rightStr = String(right || "");
        const spaces = width - leftStr.length - rightStr.length;
        return spaces > 0 ? leftStr + " ".repeat(spaces) + rightStr : leftStr + " " + rightStr;
    }

    pad(str, width, alignRight = false) {
        let s = String(str || "");
        if (s.length > width) return s.substring(0, width);
        return alignRight ? s.padStart(width) : s.padEnd(width);
    }

    generate(data) {
        const { id, items, total, config } = data;
        const width = 42; 

        let encoder = this.encoder.initialize(); 

        // 1. Header (STRICT SETUP)
        encoder = encoder.initialize();
        // RAW: Reset + Center + Print Logo
        encoder = encoder.raw([
            0x1B, 0x40,       // ESC @ (Init)
            0x1B, 0x61, 0x01, // ESC a 1 (Center Alignment)
            0x1C, 0x70, 0x01, 0x00 // FS p 1 0 (Print NV Logo stored in slot 1)
        ]);

        // Force Library Sync (Visual spacer for logo)
        encoder = encoder.newline();

        // Site Name & Address
        if (config.show_site) {
            encoder = encoder.raw([0x1B, 0x61, 0x01]) // FORCE CENTER
                             .bold(true).size(2, 2).text((config.site_name).toUpperCase()).newline()
                             .bold(false).size(1, 1);
        }
        encoder = encoder.raw([0x1B, 0x61, 0x01]).text(config.address).newline();
        encoder = encoder.raw([0x1B, 0x61, 0x01]).text(`Tel: ${config.phone}`).newline();
        encoder = encoder.raw([0x1B, 0x61, 0x01]).text("NTN: 1620237071939").newline();
        
        // 2. Refund Badge
        encoder = encoder.newline();
        encoder = encoder.raw([0x1B, 0x61, 0x01]) // FORCE CENTER
                         .text("=".repeat(width)).newline()
                         .bold(true).text("REVISED REFUND RECEIPT").bold(false).newline()
                         .text("=".repeat(width)).newline();

        // 3. Info
        encoder = encoder.raw([0x1B, 0x61, 0x00]); // FORCE LEFT
        encoder = encoder.line(this.twoColumn(`Refund: #${id}`, "Date: " + new Date().toLocaleDateString('en-GB'), width));
        encoder = encoder.line(this.twoColumn(`Ref Order: #${data.order_id}`, `Pay: ${data.payment_method}`, width));
        encoder = encoder.line("-".repeat(width));
        
        // 4. Items
        encoder = encoder.bold(true)
            .text(this.pad("RETURNED ITEM", 18))
            .text(this.pad("QTY", 4, true))
            .text(this.pad("PRICE", 10, true))
            .text(this.pad("AMT", 10, true))
            .newline().bold(false);
        
        items.forEach(item => {
            let name = (item.name || "").substring(0, 17);
            encoder = encoder
                .text(this.pad(name, 18))
                .text(this.pad("x" + item.qty, 4, true))
                .text(this.pad(item.price.toString(), 10, true))
                .bold(true).text(this.pad(item.total.toString(), 10, true)).bold(false)
                .newline();
        });

        encoder = encoder.line("-".repeat(width));

        // 5. Totals
        encoder = encoder.line(this.twoColumn("Refund Total:", total.toString(), width)).newline()
                         .line("-".repeat(width))
                         .bold(true).line("ORDER SUMMARY (ADJUSTED)")
                         .bold(false).line(this.twoColumn("Original Total:", data.order_summary.original_total, width))
                         .line(this.twoColumn("Total Refunded:", data.order_summary.total_refunded, width))
                         .bold(true).line(this.twoColumn("Final Balance:", data.order_summary.adjusted_total, width)).bold(false);

        // 6. Footer
        encoder = encoder.newline().line("-".repeat(width)).newline();
        
        encoder = encoder.raw([0x1B, 0x61, 0x01]); // FORCE CENTER
        encoder = encoder.text(config.footer_note).newline().newline();
        
        encoder = encoder.bold(true).text("Software by SINYX").bold(false).newline()
                         .text("Contact: +92 342 9031328");
        
        // Feed & Cut
        encoder = encoder.newline().newline().newline().newline().newline();
        encoder = encoder.raw([0x1D, 0x56, 0x41, 0x00]); // Cut

        return encoder.encode();
    }
}

async function runCommand(cmd) {
    return new Promise((resolve) => {
        exec(cmd, (error, stdout, stderr) => {
            resolve({ error, stdout: stdout.trim(), stderr: stderr.trim() });
        });
    });
}

const tester = new RefundTester();
const buffer = tester.generate(refundData);

const tempDir = os.tmpdir();
const tempBin = path.join(tempDir, `refund_test.bin`);
fs.writeFileSync(tempBin, Buffer.from(buffer));

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
        di.pDocName = "REFUND_TEST";
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
const psFile = path.join(tempDir, "print_refund.ps1");
fs.writeFileSync(psFile, psContent);

console.log("Printing TEST REFUND RECEIPT...");
runCommand(`powershell -ExecutionPolicy Bypass -File "${psFile}"`).then(() => {
    console.log("Done.");
});
