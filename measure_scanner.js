const readline = require('readline');

readline.emitKeypressEvents(process.stdin);
if (process.stdin.isTTY) process.stdin.setRawMode(true);

let lastTime = Date.now();
let buffer = "";

console.log("================================================");
console.log("   SCANNER SPEED DIAGNOSTIC TOOL (Node.js)");
console.log("================================================");
console.log("Please SCAN a barcode now...");
console.log("Press 'Ctrl+C' to exit.");
console.log("------------------------------------------------");

process.stdin.on('keypress', (str, key) => {
    const now = Date.now();
    const diff = now - lastTime;
    lastTime = now;

    if (key.ctrl && key.name === 'c') {
        process.exit();
    }

    if (key.name === 'return') {
        console.log(`\n[ENTER DETECTED] Total Buffer: "${buffer}"`);
        console.log("------------------------------------------------");
        buffer = "";
    } else {
        buffer += str || key.name;
        // Log timing for every character
        console.log(`Char: '${str || key.name}' | Gap: ${diff}ms`);
    }
});
