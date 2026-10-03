#!/usr/bin/env node

const { spawn } = require('child_process');
const os = require('os');
const net = require('net');

const DEFAULT_PORT = process.env.PORT ? parseInt(process.env.PORT, 10) : 8000;
const HOST = '0.0.0.0';

// Function to find local network IPv4 address
function getLocalNetworkIPs() {
  const interfaces = os.networkInterfaces();
  const addresses = [];

  for (const name of Object.keys(interfaces)) {
    for (const iface of interfaces[name]) {
      // Skip internal (i.e. 127.0.0.1) and non-ipv4 addresses
      if (iface.family === 'IPv4' && !iface.internal) {
        addresses.push({
          name,
          address: iface.address
        });
      }
    }
  }

  return addresses;
}

// Function to check if a port is available
function checkPort(port) {
  return new Promise((resolve) => {
    const server = net.createServer();
    server.once('error', (err) => {
      if (err.code === 'EADDRINUSE') {
        resolve(false);
      } else {
        resolve(false);
      }
    });
    server.once('listening', () => {
      server.close();
      resolve(true);
    });
    server.listen(port, '0.0.0.0');
  });
}

// Function to find an available port starting from startPort
async function findAvailablePort(startPort) {
  let port = startPort;
  while (!(await checkPort(port))) {
    port++;
    if (port > startPort + 50) {
      throw new Error(`Could not find an available port between ${startPort} and ${port}`);
    }
  }
  return port;
}

async function startServer() {
  const port = await findAvailablePort(DEFAULT_PORT);
  const networkIPs = getLocalNetworkIPs();

  console.clear();
  console.log('\x1b[36m%s\x1b[0m', '=======================================================');
  console.log('\x1b[1m\x1b[32m%s\x1b[0m', '  ⚡ NULLIFIED SOLUTIONS - PHP DEVELOPMENT SERVER');
  console.log('\x1b[36m%s\x1b[0m', '=======================================================');
  console.log('');
  console.log('  \x1b[1mLocal URL:\x1b[0m       \x1b[34mhttp://localhost:' + port + '\x1b[0m');
  console.log('  \x1b[1mLoopback IP:\x1b[0m     \x1b[34mhttp://127.0.0.1:' + port + '\x1b[0m');

  if (networkIPs.length > 0) {
    console.log('');
    console.log('  \x1b[1m\x1b[33mShare with other programmers on your Wi-Fi / Network:\x1b[0m');
    networkIPs.forEach((item) => {
      console.log(`  ➜ \x1b[32mhttp://${item.address}:${port}\x1b[0m \x1b[90m(${item.name})\x1b[0m`);
    });
  } else {
    console.log('');
    console.log('  \x1b[90mNo active Wi-Fi / LAN IP detected for local sharing.\x1b[0m');
  }

  console.log('');
  console.log('\x1b[90m-------------------------------------------------------\x1b[0m');
  console.log('  \x1b[1mWant to share over the Internet?\x1b[0m');
  console.log('  In another terminal, run: \x1b[36mnpm run share\x1b[0m');
  console.log('  Or use ngrok / cloudflared: \x1b[36mngrok http ' + port + '\x1b[0m');
  console.log('\x1b[90m-------------------------------------------------------\x1b[0m');
  console.log('  Press \x1b[1mCtrl+C\x1b[0m to stop the server.');
  console.log('\x1b[36m%s\x1b[0m', '=======================================================');
  console.log('');

  // Spawn PHP built-in server
  const phpProcess = spawn('php', ['-S', `${HOST}:${port}`], {
    stdio: 'inherit',
    cwd: process.cwd()
  });

  phpProcess.on('error', (err) => {
    console.error('\x1b[31mFailed to start PHP built-in server:\x1b[0m', err.message);
    console.error('Make sure PHP is installed and accessible in your PATH.');
    process.exit(1);
  });

  phpProcess.on('close', (code) => {
    if (code !== 0 && code !== null) {
      console.log(`\n\x1b[33mPHP server exited with code ${code}\x1b[0m`);
    }
  });

  const cleanup = () => {
    console.log('\n\x1b[33mStopping PHP server...\x1b[0m');
    phpProcess.kill('SIGINT');
    process.exit(0);
  };

  process.on('SIGINT', cleanup);
  process.on('SIGTERM', cleanup);
}

startServer().catch((err) => {
  console.error('\x1b[31mError starting server:\x1b[0m', err);
  process.exit(1);
});
