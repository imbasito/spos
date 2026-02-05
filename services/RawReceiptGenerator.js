const EscPosEncoder = require('esc-pos-encoder');

class RawReceiptGenerator {
    formatValue(val) {
        // Line Item Precision: Round to nearest cent
        const numeric = parseFloat(val || 0);
        return (Math.round(numeric * 100) / 100).toFixed(2);
    }

    constructor() {
        this.encoder = new EscPosEncoder();
    }

    async generate(data) {
        // Handle both POS and Refund data structures
        let { id, staff, customer, items, sub_total, discount, total, paid, due, change, payment_method, config } = data;
        
        // For refunds, extract from order_summary if not at root level
        if (data.type === 'refund' && data.order_summary) {
            // Don't override if already set, but populate from order_summary if missing
            sub_total = sub_total || data.order_summary.original_total;
            total = total || data.order_summary.adjusted_total;
            // Refunds don't have discount/paid/due at transaction level, use order values
        }
        
        const width = 42; 

        const now = new Date();
        const currentTime = now.toLocaleString('en-GB', { 
            hour: '2-digit', minute: '2-digit', second: '2-digit',
            day: '2-digit', month: '2-digit', year: 'numeric' 
        });

        // Initialize
        let encoder = this.encoder.initialize(); 

        // 1. Header (STRICT CENTER)
        encoder = encoder.raw([0x1b, 0x61, 0x01]); // Align Center
        
        if (config.show_logo) {
            try {
                // Command: FS p 1 0 (Print NV Logo)
                encoder = encoder.raw([0x1c, 0x70, 0x01, 0x00]);
                encoder = encoder.newline();
            } catch (e) { console.warn("Logo failed:", e.message); }
        }

        if (config.show_site) {
             encoder = encoder.raw([0x1B, 0x61, 0x01]) // FORCE CENTER
                              .bold(true).size(2, 2).text((config.site_name || "SPOS").toUpperCase()).newline()
                              .bold(false).size(1, 1);
        }

        // FORCE CENTER for Address Block
        if (config.show_address && config.address) encoder = encoder.raw([0x1B, 0x61, 0x01]).text(config.address).newline();
        if (config.show_phone && config.phone) encoder = encoder.raw([0x1B, 0x61, 0x01]).text(`Tel: ${config.phone}`).newline();
        if (config.show_email && config.email) encoder = encoder.raw([0x1B, 0x61, 0x01]).text(config.email).newline();
        
        // Dynamic NTN/STRN from config
        if (config.ntn) {
            encoder = encoder.raw([0x1B, 0x61, 0x01]).text(`NTN: ${config.ntn}`).newline();
        }
        if (config.strn) {
            encoder = encoder.raw([0x1B, 0x61, 0x01]).text(`STRN: ${config.strn}`).newline();
        }
        
        // 5. Header / Badge (RE-ALIGN)
        if (data.type === 'refund') {
            encoder = encoder.newline();
            encoder = encoder.raw([0x1B, 0x61, 0x01]) // FORCE CENTER
                             .text("=".repeat(width)).newline()
                             .bold(true).text("REVISED REFUND RECEIPT").bold(false).newline()
                             .text("=".repeat(width)).newline();
        }

        // SEPARATOR after Header
        if (data.type !== 'refund') {
            encoder = encoder.text("-".repeat(width)).newline();
        }

        // Return to Center for Content (User Request: "make them centered too")
        encoder = encoder.raw([0x1B, 0x61, 0x01]); // Center

        if (data.type === 'refund') {
            // Refund Layout (Matches test_refund_match.js)
            // Line 1: Refund ID + Date
            const dateStr = "Date: " + now.toLocaleDateString('en-GB'); // DD/MM/YYYY
            encoder = encoder.line(this.twoColumn(`Refund: #${id}`, dateStr, width));
            
            // Line 2: Ref Order + Payment Method
            const refStr = `Ref Order: #${data.order_id}`;
            const payStr = `Pay: ${payment_method || 'Cash'}`;
            encoder = encoder.line(this.twoColumn(refStr, payStr, width));

        } else {
            // Sale Layout
            // Line 1: Invoice + Payment
            const invStr = `Inv: #${id}`;
            const methodStr = `Pay: ${payment_method || 'Cash'}`;
            encoder = encoder.line(this.twoColumn(invStr, methodStr, width));

            // Line 2: Date + Trx
            // Use full formatted time for Sales
            const trxStr = `Trx: ${data.transaction_id || '---'}`;
            encoder = encoder.line(this.twoColumn(currentTime, trxStr, width));
            
            // Line 3: Customer + Staff (Sales Only or Shared?)
            const custStr = (config.show_customer !== false) ? `Cust: ${customer ? (customer.name || customer) : 'Walk-in'}` : "";
            const staffStr = `STN: ${staff ? staff.substring(0, 10) : 'N/A'}`;
            encoder = encoder.line(this.twoColumn(custStr, staffStr, width));
        }

        // 3. Items Table
        encoder = encoder.line("-".repeat(width)).bold(true)
            .text(this.pad(data.type === 'refund' ? "RETURNED ITEM" : "ITEM", 18))
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

        // 4. Totals (Center aligned block)
        if (data.type === 'refund') {
            // Calculate refund total for THIS transaction
            const refundTotal = items.reduce((sum, item) => {
                const total = parseFloat(item.total.replace(/,/g, '')) || 0;
                return sum + total;
            }, 0);
            
            encoder = encoder.line(this.twoColumn("Refund Total:", this.formatValue(refundTotal), width))
                             .newline()
                             .line("-".repeat(width))
                             .bold(true).line("TRANSACTION SUMMARY").bold(false)
                             .line(this.twoColumn("Original Order Total:", data.order_summary.original_total, width))
                             .bold(true).line(this.twoColumn("TOTAL REFUND", "-" + data.order_summary.total_refunded, width)).bold(false)
                             .line("-".repeat(width))
                             .bold(true).line(this.twoColumn("ADJUSTED TOTAL", data.order_summary.adjusted_total, width)).bold(false);
            
            // Show customer due if exists
            if (data.order_summary.customer_due && parseFloat(data.order_summary.customer_due.replace(/,/g, '')) > 0) {
                encoder = encoder.line(this.twoColumn("Customer Due:", data.order_summary.customer_due, width));
            }
            
            // Cash Back Logic (matches Blade template)
            encoder = encoder.newline().line("-".repeat(width)).newline();
            const currentDue = parseFloat(data.order_summary.customer_due.replace(/,/g, '')) || 0;
            const totalRefunded = parseFloat(data.order_summary.total_refunded.replace(/,/g, '')) || 0;
            
            if (currentDue <= 0 && totalRefunded > 0) {
                // No debt remaining = cash was returned
                encoder = encoder.raw([0x1B, 0x61, 0x01]) // Center
                                 .bold(true).size(1, 1)
                                 .text("CASH RETURNED: " + this.formatValue(totalRefunded))
                                 .size(1, 1).bold(false)
                                 .newline();
            }
        } else {
            encoder = encoder.line(this.twoColumn("Gross Total:", sub_total.toString(), width));
            const discVal = discount ? parseFloat(discount) : 0;
            encoder = encoder.line(this.twoColumn("Discount:", `(-${discVal})`, width));

            // GST Calculation (if enabled)
            if (config.gst_enabled && config.show_tax) {
                const gstRate = config.gst_rate || 17;
                const grossNum = parseFloat(String(sub_total).replace(/,/g, '')) || 0;
                const discNum = discVal;
                const taxableAmount = grossNum - discNum;
                const gstAmount = (taxableAmount * gstRate) / 100;
                encoder = encoder.line(this.twoColumn(`GST (${gstRate}%):`, this.formatValue(gstAmount), width));
            }

            encoder = encoder.line("-".repeat(width)).bold(true)
                .line(this.twoColumn("NET PAYABLE", total.toString(), width))
                .bold(false).line("-".repeat(width)).newline();
            
            encoder = encoder.line(this.twoColumn("Paid Amount:", paid.toString(), width));

            if (parseFloat(change) > 0) {
                encoder = encoder.bold(true).line(this.twoColumn("CHANGE:", change.toString(), width)).bold(false);
            }
            if (parseFloat(due) > 0) {
                encoder = encoder.bold(true).line(this.twoColumn("DUE BALANCE:", due.toString(), width)).bold(false);
            }
        }

        // 5. Footer (Aggressive Centering)
        encoder = encoder.newline().line("-".repeat(width)).newline();
        
        if (config.show_note && config.footer_note) {
            encoder = encoder.raw([0x1B, 0x61, 0x01]); // Center
            encoder = encoder.text(config.footer_note).newline().newline();
        }

        // SINYX Branding centered
        encoder = encoder.raw([0x1B, 0x61, 0x01]); // Center
        encoder = encoder.bold(true).text("Software by SINYX").bold(false).newline()
                         .text("Contact: +92 311 6514288");
        
        encoder = encoder.newline().newline().newline().newline().pulse().cut();

        return encoder.encode();
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
}

module.exports = new RawReceiptGenerator();