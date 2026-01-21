# Belova WA Bot

Minimal WhatsApp Web JS service for the Belova project.

Setup

1. Open a terminal in the project root and change to the `wa-bot` folder:

```bash
cd wa-bot
```

2. Install dependencies and start the bot:

```bash
npm install
npm start
```

3. On first run the bot will print a QR to the terminal. Scan it with WhatsApp mobile to authenticate. Session is persisted by `LocalAuth` in `.wwebjs_auth`.

Files

- `index.js`: bot entrypoint
- `package.json`: dependencies and start script

Next steps

- Integrate with Laravel via HTTP or message queue to send commands to the bot.
