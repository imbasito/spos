const fs = require('fs');
const path = require('path');
const os = require('os');

const docs = path.join(os.homedir(), 'Documents');
console.log("Documents Pattern:", docs);

const testFile = path.join(docs, 'TEST_WRITE_PERMISSION.txt');

try {
    fs.writeFileSync(testFile, 'This is a test file from the developer to confirm path visibility.');
    console.log("SUCCESS: Wrote to", testFile);
} catch (e) {
    console.error("FAILURE:", e.message);
}
