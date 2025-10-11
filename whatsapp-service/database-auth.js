const axios = require('axios');

/**
 * Database-based auth state for Baileys WhatsApp
 * This replaces file-based authentication with database storage
 */
class DatabaseAuthState {
    constructor(baseUrl = 'http://localhost/belova/public') {
        this.baseUrl = baseUrl;
        this.apiUrl = `${baseUrl}/api/whatsapp/auth`;
    }

    /**
     * Read auth data from database
     */
    async readData(key) {
        try {
            const response = await axios.get(`${this.apiUrl}/${key}`, {
                timeout: 5000,
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            if (response.data.success && response.data.data) {
                return response.data.data;
            }
            return null;
        } catch (error) {
            if (error.response?.status !== 404) {
                console.warn(`⚠️ Failed to read auth data for ${key}:`, error.message);
            }
            return null;
        }
    }

    /**
     * Write auth data to database
     */
    async writeData(key, data) {
        try {
            await axios.post(`${this.apiUrl}/${key}`, {
                data: data
            }, {
                timeout: 5000,
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            console.log(`💾 Saved auth data: ${key}`);
        } catch (error) {
            console.error(`❌ Failed to save auth data for ${key}:`, error.message);
            throw error;
        }
    }

    /**
     * Remove auth data from database
     */
    async removeData(key) {
        try {
            await axios.delete(`${this.apiUrl}/${key}`, {
                timeout: 5000
            });
            console.log(`🗑️ Removed auth data: ${key}`);
        } catch (error) {
            if (error.response?.status !== 404) {
                console.error(`❌ Failed to remove auth data for ${key}:`, error.message);
            }
        }
    }
}

/**
 * Create database-based auth state for Baileys
 */
async function useDatabaseAuthState(baseUrl) {
    const db = new DatabaseAuthState(baseUrl);
    
    // Read existing creds from database
    let creds = await db.readData('creds');
    
    // Initialize with proper structure if no creds exist
    if (!creds) {
        creds = {
            noiseKey: null,
            pairingEphemeralKeyPair: null,
            pairingIdentityKeyPair: null,
            registrationId: null,
            advSecretKey: null,
            me: null,
            account: null,
            signalIdentities: [],
            myAppStateKeyId: null,
            firstUnuploadedPreKeyId: 1,
            nextPreKeyId: 1,
            serverHasPreKeys: false
        };
    }

    const authState = {
        state: {
            creds: creds,
            keys: {}
        },
        saveCreds: async () => {
            if (authState.state.creds) {
                await db.writeData('creds', authState.state.creds);
            }
        }
    };

    // Key management functions with proper Baileys structure
    const keyTypes = {
        'pre-key': {
            get: async (ids) => {
                const data = {};
                for (const id of ids) {
                    const key = `pre-key-${id}`;
                    const value = await db.readData(key);
                    if (value) {
                        data[id] = value;
                    }
                }
                return data;
            },
            set: async (data) => {
                for (const [id, value] of Object.entries(data)) {
                    const key = `pre-key-${id}`;
                    if (value) {
                        await db.writeData(key, value);
                    } else {
                        await db.removeData(key);
                    }
                }
            }
        },
        'session': {
            get: async (ids) => {
                const data = {};
                for (const id of ids) {
                    const key = `session-${id}`;
                    const value = await db.readData(key);
                    if (value) {
                        data[id] = value;
                    }
                }
                return data;
            },
            set: async (data) => {
                for (const [id, value] of Object.entries(data)) {
                    const key = `session-${id}`;
                    if (value) {
                        await db.writeData(key, value);
                    } else {
                        await db.removeData(key);
                    }
                }
            }
        },
        'sender-key': {
            get: async (ids) => {
                const data = {};
                for (const id of ids) {
                    const key = `sender-key-${id}`;
                    const value = await db.readData(key);
                    if (value) {
                        data[id] = value;
                    }
                }
                return data;
            },
            set: async (data) => {
                for (const [id, value] of Object.entries(data)) {
                    const key = `sender-key-${id}`;
                    if (value) {
                        await db.writeData(key, value);
                    } else {
                        await db.removeData(key);
                    }
                }
            }
        },
        'app-state-sync-key': {
            get: async (ids) => {
                const data = {};
                for (const id of ids) {
                    const key = `app-state-sync-key-${id}`;
                    const value = await db.readData(key);
                    if (value) {
                        data[id] = value;
                    }
                }
                return data;
            },
            set: async (data) => {
                for (const [id, value] of Object.entries(data)) {
                    const key = `app-state-sync-key-${id}`;
                    if (value) {
                        await db.writeData(key, value);
                    } else {
                        await db.removeData(key);
                    }
                }
            }
        },
        'app-state-sync-version': {
            get: async (ids) => {
                const data = {};
                for (const id of ids) {
                    const key = `app-state-sync-version-${id}`;
                    const value = await db.readData(key);
                    if (value) {
                        data[id] = value;
                    }
                }
                return data;
            },
            set: async (data) => {
                for (const [id, value] of Object.entries(data)) {
                    const key = `app-state-sync-version-${id}`;
                    if (value) {
                        await db.writeData(key, value);
                    } else {
                        await db.removeData(key);
                    }
                }
            }
        }
    };

    // Assign key handlers to auth state
    authState.state.keys = keyTypes;

    return authState;
}

module.exports = { useDatabaseAuthState, DatabaseAuthState };