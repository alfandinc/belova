# Node.js v22 Compatibility Issue - Solution Guide

## Problem
Node.js v22.20.0 has a critical crypto assertion failure on Windows:
`Assertion failed: ncrypto::CSPRNG(nullptr, 0)`

This affects whatsapp-web.js and many other packages.

## RECOMMENDED SOLUTION: Downgrade to Node.js v18 LTS

### Step 1: Uninstall Node.js v22
1. Go to Windows Settings > Apps
2. Search for "Node.js"
3. Uninstall the current version

### Step 2: Download Node.js v18 LTS
1. Go to: https://nodejs.org/
2. Click "Download Node.js (LTS)" - should be v18.x.x
3. Or direct link: https://nodejs.org/dist/v18.19.0/node-v18.19.0-x64.msi

### Step 3: Install Node.js v18
1. Run the downloaded .msi file
2. Follow the installation wizard
3. Make sure "Add to PATH" is checked

### Step 4: Verify Installation
Open a new PowerShell window and run:
```
node --version
npm --version
```
Should show v18.x.x

### Step 5: Reinstall Dependencies
```
cd C:\wamp64\www\belova\whatsapp-service
npm install
```

### Step 6: Test
```
npm start
```

## Alternative: Use NVM for Windows (Advanced)
If you need multiple Node.js versions:

1. Download nvm-windows from: https://github.com/coreybutler/nvm-windows
2. Install nvm-windows
3. Open new PowerShell as Administrator:
```
nvm install 18.19.0
nvm use 18.19.0
```

## Why v18 LTS?
- Most stable and widely supported
- Recommended for production
- Full compatibility with whatsapp-web.js
- Long-term support until 2025