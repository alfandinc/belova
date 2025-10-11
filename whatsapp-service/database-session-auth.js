const axios = require('axios');
const fs = require('fs');
const path = require('path');

class DatabaseSessionAuthState {
    constructor(baseUrl = 'http://localhost/belova/public/api/whatsapp/auth') {
        this.baseUrl = baseUrl;
        this.authDir = 'auth_info_baileys';
        this.sessionKeyPrefix = 'session-';
    }

    // Initialize auth state - completely database-based
    async initAuthState() {
        console.log('🗄️ Using DATABASE-BASED authentication with ZERO file creation...');
        
        try {
            // Ensure auth directory exists but clean it aggressively
            if (!fs.existsSync(this.authDir)) {
                fs.mkdirSync(this.authDir, { recursive: true });
            }
            
            // Nuclear cleanup - remove ALL files
            await this.nuclearCleanup();
            
            const authState = {
                state: {
                    creds: await this.loadCreds(),
                    keys: await this.loadKeys()
                },
                saveCreds: this.saveCreds.bind(this),
                saveState: this.saveState.bind(this)
            };

            return authState;
        } catch (error) {
            console.error('❌ Error initializing database auth state:', error.message);
            throw error;
        }
    }

    // Nuclear cleanup - remove ALL files
    async nuclearCleanup() {
        try {
            if (!fs.existsSync(this.authDir)) return;
            
            const files = fs.readdirSync(this.authDir);
            for (const file of files) {
                try {
                    fs.unlinkSync(path.join(this.authDir, file));
                    console.log(`☢️ Nuclear removed: ${file}`);
                } catch (err) {
                    console.log(`⚠️ Could not remove ${file}:`, err.message);
                }
            }
            console.log(`☢️ Nuclear cleanup completed. Directory is now EMPTY.`);
        } catch (error) {
            console.log('⚠️ Error during nuclear cleanup:', error.message);
        }
    }

    // Load credentials from database
    async loadCreds() {
        try {
            const response = await axios.get(`${this.baseUrl}/creds`, {
                timeout: 5000
            });
            
            if (response.data.success && response.data.data) {
                console.log('🔑 Loaded credentials from database');
                return response.data.data;
            } else {
                console.log('🆕 No credentials found in database, will create new');
                return null;
            }
        } catch (error) {
            console.log('⚠️ Error loading credentials from database:', error.message);
            return null;
        }
    }

    // Load keys (pre-keys, sender-keys, sessions) from database
    async loadKeys() {
        try {
            // Get all keys except creds
            const response = await axios.get(`${this.baseUrl}-summary`, {
                timeout: 5000
            });
            
            const keys = {
                'pre-keys': {},
                'sender-keys': {},
                'app-state-sync-keys': {},
                'app-state-sync-versions': {},
                'sessions': {}
            };

            if (response.data.success && response.data.keys) {
                for (const key of response.data.keys) {
                    if (key.key_name === 'creds') continue;
                    
                    // Categorize keys
                    if (key.key_name.startsWith('pre-key-')) {
                        const keyId = key.key_name.replace('pre-key-', '');
                        keys['pre-keys'][keyId] = key.data;
                    } else if (key.key_name.startsWith('sender-key-')) {
                        const keyId = key.key_name.replace('sender-key-', '');
                        keys['sender-keys'][keyId] = key.data;
                    } else if (key.key_name.startsWith('app-state-sync-key-')) {
                        const keyId = key.key_name.replace('app-state-sync-key-', '');
                        keys['app-state-sync-keys'][keyId] = key.data;
                    } else if (key.key_name.startsWith('app-state-sync-version-')) {
                        const keyId = key.key_name.replace('app-state-sync-version-', '');
                        keys['app-state-sync-versions'][keyId] = key.data;
                    } else if (key.key_name.startsWith('session-')) {
                        const sessionId = key.key_name.replace('session-', '');
                        keys['sessions'][sessionId] = key.data;
                    }
                }
                
                console.log(`🔑 Loaded ${response.data.keys.length} keys from database`);
            }

            return keys;
        } catch (error) {
            console.log('⚠️ Error loading keys from database:', error.message);
            return {
                'pre-keys': {},
                'sender-keys': {},
                'app-state-sync-keys': {},
                'app-state-sync-versions': {},
                'sessions': {}
            };
        }
    }

    // Save credentials to database (never to file)
    async saveCreds(creds) {
        try {
            await axios.post(`${this.baseUrl}/creds`, {
                data: creds
            }, {
                timeout: 5000,
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            console.log('💾 Credentials saved to database (NO FILE CREATED)');
            
            // Nuclear cleanup after saving to ensure no files remain
            setTimeout(() => this.nuclearCleanup(), 1000);
            
        } catch (error) {
            console.error('❌ Error saving credentials to database:', error.message);
        }
    }

    // Save any auth state to database (never to file)
    async saveState(key, data) {
        try {
            // Determine key type and save to database
            let keyName = '';
            
            if (typeof key === 'object' && key.type) {
                // Handle different key types
                switch (key.type) {
                    case 'pre-key':
                        keyName = `pre-key-${key.keyId}`;
                        break;
                    case 'sender-key':
                        keyName = `sender-key-${key.keyId}`;
                        break;
                    case 'app-state-sync-key':
                        keyName = `app-state-sync-key-${key.keyId}`;
                        break;
                    case 'app-state-sync-version':
                        keyName = `app-state-sync-version-${key.name}`;
                        break;
                    case 'session':
                        keyName = `session-${key.sessionId}`;
                        break;
                    default:
                        keyName = key.toString();
                }
            } else {
                keyName = key.toString();
            }

            await axios.post(`${this.baseUrl}/${keyName}`, {
                data: data
            }, {
                timeout: 5000,
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            console.log(`💾 Saved ${keyName} to database (NO FILE CREATED)`);
            
            // Nuclear cleanup after saving to ensure no files remain
            setTimeout(() => this.nuclearCleanup(), 1000);
            
        } catch (error) {
            console.error(`❌ Error saving ${key} to database:`, error.message);
        }
    }

    // Override session file creation - redirect to database
    async writeSessionData(sessionId, data) {
        try {
            const keyName = `${this.sessionKeyPrefix}${sessionId}`;
            
            await axios.post(`${this.baseUrl}/${keyName}`, {
                data: data
            }, {
                timeout: 5000,
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            console.log(`💾 Session ${sessionId} saved to database (NO FILE CREATED)`);
            
            // Nuclear cleanup to remove any session files that might have been created
            setTimeout(() => this.nuclearCleanup(), 500);
            
        } catch (error) {
            console.error(`❌ Error saving session ${sessionId} to database:`, error.message);
        }
    }

    // Read session data from database
    async readSessionData(sessionId) {
        try {
            const keyName = `${this.sessionKeyPrefix}${sessionId}`;
            const response = await axios.get(`${this.baseUrl}/get/${keyName}`, {
                timeout: 5000
            });
            
            if (response.data.success && response.data.data) {
                console.log(`🔑 Session ${sessionId} loaded from database`);
                return response.data.data;
            } else {
                console.log(`🆕 No session ${sessionId} found in database`);
                return null;
            }
        } catch (error) {
            console.log(`⚠️ Error loading session ${sessionId} from database:`, error.message);
            return null;
        }
    }

    // Aggressive cleanup to prevent ANY file creation
    async aggressiveCleanup() {
        try {
            if (!fs.existsSync(this.authDir)) return;
            
            const files = fs.readdirSync(this.authDir);
            if (files.length === 0) return;
            
            console.log(`🧹 AGGRESSIVE CLEANUP: Found ${files.length} files, removing all...`);
            
            for (const file of files) {
                try {
                    const filePath = path.join(this.authDir, file);
                    fs.unlinkSync(filePath);
                    console.log(`🗑️ Removed file: ${file}`);
                } catch (err) {
                    console.log(`⚠️ Could not remove ${file}:`, err.message);
                }
            }
            
            console.log('✅ Aggressive cleanup completed - Directory is EMPTY');
        } catch (error) {
            console.log('⚠️ Error during aggressive cleanup:', error.message);
        }
    }

    // Monitor and prevent file creation
    startFileMonitor() {
        // Check every 30 seconds for any files and remove them
        setInterval(async () => {
            await this.aggressiveCleanup();
        }, 30000);
        
        console.log('👁️ File monitor started - will remove ANY files every 30 seconds');
    }
}

module.exports = DatabaseSessionAuthState;