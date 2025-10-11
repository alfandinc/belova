const fs = require('fs');
const path = require('path');

const authDir = 'auth_info_baileys';

console.log('🧹 SUPER CLEANUP SCRIPT STARTING...');

if (!fs.existsSync(authDir)) {
    console.log('❌ Auth directory does not exist');
    process.exit(1);
}

// Show current state
const files = fs.readdirSync(authDir);
console.log(`📊 Current files in auth directory: ${files.length}`);
files.forEach(file => console.log(`  - ${file}`));

// Nuclear cleanup - keep only creds.json
const essential = ['creds.json'];
let removed = 0;

for (const file of files) {
    if (!essential.includes(file)) {
        try {
            fs.unlinkSync(path.join(authDir, file));
            console.log(`🗑️ Removed: ${file}`);
            removed++;
        } catch (err) {
            console.log(`⚠️ Could not remove ${file}: ${err.message}`);
        }
    }
}

// Show final state
const remainingFiles = fs.readdirSync(authDir);
console.log(`\\n✅ CLEANUP COMPLETE:`);
console.log(`   Removed: ${removed} files`);
console.log(`   Remaining: ${remainingFiles.length} files`);
remainingFiles.forEach(file => console.log(`  - ${file}`));

console.log('\\n🎉 Super cleanup finished!');