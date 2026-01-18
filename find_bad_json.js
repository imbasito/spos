const fs = require('fs');
const path = require('path');

function walk(dir) {
    let files = fs.readdirSync(dir);
    files.forEach(file => {
        let fullPath = path.join(dir, file);
        if (fs.statSync(fullPath).isDirectory()) {
            if (file !== 'node_modules' || dir === '.') { // Only descend into node_modules from root
                try {
                    walk(fullPath);
                } catch (e) {}
            }
        } else if (file.endsWith('.json')) {
            try {
                const content = fs.readFileSync(fullPath, 'utf8');
                if (content.trim().length === 0) {
                    console.log(`EMPTY: ${fullPath}`);
                } else {
                    JSON.parse(content);
                }
            } catch (e) {
                console.log(`INVALID: ${fullPath} - ${e.message}`);
            }
        }
    });
}

console.log('Scanning for corrupted JSON files...');
walk('.');
console.log('Scan complete.');
