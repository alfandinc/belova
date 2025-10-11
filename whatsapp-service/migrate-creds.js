const fs = require('fs');
const path = require('path');
const axios = require('axios');

async function migrateCredsToDatabase() {
    try {
        console.log('🔄 Migrating existing credentials to database...');
        
        const credsPath = path.join(__dirname, 'auth_info_baileys', 'creds.json');
        
        if (!fs.existsSync(credsPath)) {
            console.log('❌ No creds.json file found');
            return;
        }
        
        const credsData = JSON.parse(fs.readFileSync(credsPath, 'utf8'));
        console.log('📄 Loaded credentials from file');
        
        // Save to database
        const response = await axios.post('http://localhost/belova/public/api/whatsapp/auth/creds', {
            data: credsData
        }, {
            timeout: 10000,
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        if (response.data.success) {
            console.log('✅ Credentials successfully saved to database');
            console.log('📊 Response:', response.data);
        } else {
            console.log('❌ Failed to save credentials:', response.data);
        }
        
    } catch (error) {
        console.error('❌ Error migrating credentials:', error.message);
        if (error.response) {
            console.error('Response status:', error.response.status);
            console.error('Response data:', error.response.data);
        }
    }
}

migrateCredsToDatabase();