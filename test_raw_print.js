const rawGenerator = require('./services/RawReceiptGenerator');
const fs = require('fs');

const mockData = {
    id: 1001,
    date: '2026-01-27 12:30:00',
    staff: 'Admin',
    customer: { name: 'John Doe' },
    items: [
        { name: 'Gulab Jamun', qty: 1, price: 1500, total: 1500 },
        { name: 'Barfi (Pista)', qty: 2, price: 2000, total: 4000 }
    ],
    sub_total: '5,500',
    discount: '500',
    total: '5,000',
    paid: '5,000',
    due: '0',
    change: '0',
    payment_method: 'Cash',
    config: {
        site_name: 'MITHAI SHOP',
        address: '123 Sweet St, Lahore',
        phone: '0300-1234567',
        footer_note: 'Thank you for visiting!',
        show_logo: false
    }
};

try {
    console.log("Generating Receipt Buffer...");
    const buffer = rawGenerator.generate(mockData);
    console.log(`Buffer generated! Length: ${buffer.length} bytes.`);
    
    // Save to file for visual inspection of hex if needed
    fs.writeFileSync('test_receipt.bin', Buffer.from(buffer));
    console.log("Saved to test_receipt.bin");
    
    // Hex Dump Preview (First 50 bytes)
    console.log("Hex Preview:", buffer.slice(0, 50));
    
    console.log("TEST PASSED: Generator works.");
} catch (e) {
    console.error("TEST FAILED:", e);
}
