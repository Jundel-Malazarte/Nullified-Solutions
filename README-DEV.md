# Nullified Solutions - Development Environment Setup

## Quick Start

This PHP project now includes npm scripts for easy development and sharing with your team.

### Prerequisites

- PHP 8.x (detected: PHP 8.5.11)
- Node.js (detected: v22.23.2)
- npm (detected: 10.9.8)
- MySQL/MariaDB (XAMPP)

### Installation

1. Clone or navigate to the project directory:
   ```bash
   cd /Applications/XAMPP/xamppfiles/htdocs/Nullified
   ```

2. No npm packages to install - the scripts use Node.js built-in modules only.

### Available Commands

#### `npm run dev`
Start the development server with automatic network IP detection.

```bash
npm run dev
```

This will:
- Start PHP's built-in server on port 8000 (or next available port)
- Display local URL: `http://localhost:8000`
- Show your network IP addresses for sharing with teammates on the same Wi-Fi/LAN
- Handle Ctrl+C gracefully to stop the server

**Example output:**
```
⚡ NULLIFIED SOLUTIONS - PHP DEVELOPMENT SERVER
=======================================================

Local URL:       http://localhost:8000
Loopback IP:     http://127.0.0.1:8000

Share with other programmers on your Wi-Fi / Network:
➜ http://192.168.1.100:8000 (en0)

Want to share over the Internet?
In another terminal, run: npm run share
```

#### `npm run build`
Run a full build check before deployment or sharing.

```bash
npm run build
```

This will:
- Check that PHP is installed and accessible
- Verify project structure (directories: includes, admin, css, js, images)
- Validate database configuration (connection.php)
- Lint all 15 PHP files for syntax errors
- Generate `.build-info.json` with environment details

**Example output:**
```
✓ PHP 8.5.11 detected
✓ Directory exists: includes
✓ Database configuration found (connection.php)
✓ All 15 PHP files passed syntax check
✓ BUILD COMPLETED
```

#### `npm run lint`
Run only the PHP syntax check without full build.

```bash
npm run lint
```

Useful for quick validation during development.

#### `npm run share`
Share your local server over the Internet using localtunnel.

```bash
# In terminal 1
npm run dev

# In terminal 2 (keep dev server running)
npm run share
```

This generates a public URL (e.g., `https://random-name.loca.lt`) that anyone can access, even outside your network.

**Alternative sharing methods:**
- **ngrok**: `ngrok http 8000`
- **cloudflared**: `cloudflared tunnel --url http://localhost:8000`

### Database Configuration

The project uses `connection.php` for database connectivity:

```php
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = ''; // default XAMPP MySQL password
$dbName = 'nullified_db';
```

Make sure your MySQL/MariaDB service is running in XAMPP before starting the development server.

### Sharing with Your Team

#### On the Same Network (Wi-Fi/LAN)
When you run `npm run dev`, share the network IP shown in the terminal:
```
http://192.168.1.100:8000
```

Your teammates can open this URL on their devices (laptop, phone, tablet) as long as they're on the same network.

#### Over the Internet
Use `npm run share` for a temporary public URL to share with remote teammates. The URL expires when you stop the localtunnel session.

### Project Structure

```
Nullified/
├── index.php              # Homepage
├── dashboard.php          # Customer dashboard
├── login.php              # User authentication
├── signup.php             # User registration
├── services.php           # Services listing
├── pricing.php            # Pricing page
├── contact.php            # Contact form
├── connection.php         # Database connection
├── admin/                 # Admin panel files
│   ├── admin_dashboard.php
│   ├── admin_login.php
│   └── ...
├── includes/              # PHP includes/utilities
├── css/                   # Stylesheets
├── js/                    # JavaScript files
├── images/                # Image assets
├── package.json           # npm scripts configuration
├── server.js              # Development server
└── build.js               # Build and lint tool
```

### Development Workflow

1. **Start development**:
   ```bash
   npm run dev
   ```

2. **Make changes** to PHP, CSS, or JS files

3. **Test locally**: Open `http://localhost:8000`

4. **Share with team**: Use the network IP or `npm run share`

5. **Before committing**:
   ```bash
   npm run build
   ```

### Troubleshooting

**Port 8000 already in use**
- The dev server automatically finds the next available port (8001, 8002, etc.)

**PHP not found**
- Ensure PHP is in your system PATH
- On macOS with XAMPP: `/Applications/XAMPP/xamppfiles/bin/php`

**Database connection failed**
- Start MySQL in XAMPP Control Panel
- Verify `connection.php` credentials match your setup
- Ensure `nullified_db` database exists

**Teammates can't access the network URL**
- Check firewall settings (allow port 8000)
- Ensure you're all on the same network
- Try `npm run share` for Internet access instead

### Files Generated

- `.build-info.json` - Build metadata (excluded from git)
- `.claudeignore` - Files to ignore in Claude Code sessions

### Related Projects

This is the original PHP codebase. The Laravel 11 migration is located at:
```
/Applications/XAMPP/xamppfiles/htdocs/nullified-laravel/
```

Migration progress: 60% complete (Phases 1-4 done)
See `migration-dashboard.html` in the Laravel directory for detailed status.

---

**Last updated**: 2026-10-03
