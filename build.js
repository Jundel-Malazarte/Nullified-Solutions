#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

const LINT_ONLY = process.argv.includes('--lint-only');

// Color codes for terminal output
const colors = {
  reset: '\x1b[0m',
  bright: '\x1b[1m',
  red: '\x1b[31m',
  green: '\x1b[32m',
  yellow: '\x1b[33m',
  blue: '\x1b[34m',
  cyan: '\x1b[36m',
  gray: '\x1b[90m'
};

function log(message, color = 'reset') {
  console.log(`${colors[color]}${message}${colors.reset}`);
}

function header(message) {
  console.log('');
  log('='.repeat(60), 'cyan');
  log(`  ${message}`, 'bright');
  log('='.repeat(60), 'cyan');
  console.log('');
}

// Check if PHP is installed
function checkPHP() {
  const { execSync } = require('child_process');
  try {
    const phpVersion = execSync('php -v', { encoding: 'utf8' });
    const versionMatch = phpVersion.match(/PHP (\d+\.\d+\.\d+)/);
    if (versionMatch) {
      log(`✓ PHP ${versionMatch[1]} detected`, 'green');
      return true;
    }
  } catch (err) {
    log('✗ PHP not found in PATH', 'red');
    log('  Install PHP or ensure it is in your system PATH', 'yellow');
    return false;
  }
}

// Lint PHP files for syntax errors
function lintPHPFiles() {
  const { execSync } = require('child_process');
  const phpFiles = [];

  function findPHPFiles(dir) {
    const entries = fs.readdirSync(dir, { withFileTypes: true });
    for (const entry of entries) {
      const fullPath = path.join(dir, entry.name);
      if (entry.isDirectory()) {
        // Skip vendor, node_modules, and hidden directories
        if (!entry.name.startsWith('.') && entry.name !== 'vendor' && entry.name !== 'node_modules') {
          findPHPFiles(fullPath);
        }
      } else if (entry.isFile() && entry.name.endsWith('.php')) {
        phpFiles.push(fullPath);
      }
    }
  }

  findPHPFiles(process.cwd());

  log(`Found ${phpFiles.length} PHP files`, 'blue');

  let errorCount = 0;
  let checkedCount = 0;

  for (const file of phpFiles) {
    try {
      execSync(`php -l "${file}"`, { encoding: 'utf8', stdio: 'pipe' });
      checkedCount++;
    } catch (err) {
      errorCount++;
      log(`✗ Syntax error in ${path.relative(process.cwd(), file)}`, 'red');
      log(`  ${err.stdout || err.message}`, 'gray');
    }
  }

  console.log('');
  if (errorCount === 0) {
    log(`✓ All ${checkedCount} PHP files passed syntax check`, 'green');
  } else {
    log(`✗ ${errorCount} file(s) with syntax errors`, 'red');
    log(`✓ ${checkedCount - errorCount} file(s) passed`, 'green');
  }

  return errorCount === 0;
}

// Check database configuration
function checkDatabaseConfig() {
  const connectionPath = path.join(process.cwd(), 'connection.php');

  if (!fs.existsSync(connectionPath)) {
    log('✗ Database connection file not found: connection.php', 'red');
    return false;
  }

  const configContent = fs.readFileSync(connectionPath, 'utf8');

  // Check for database credentials (basic check)
  const hasDBHost = /\$dbHost/i.test(configContent);
  const hasDBName = /\$dbName/i.test(configContent);
  const hasDBUser = /\$dbUser/i.test(configContent);

  if (hasDBHost && hasDBName && hasDBUser) {
    log('✓ Database configuration found (connection.php)', 'green');
    return true;
  } else {
    log('⚠ Database configuration may be incomplete', 'yellow');
    return false;
  }
}

// Check required directories
function checkDirectories() {
  const requiredDirs = ['includes', 'admin', 'css', 'js', 'images'];
  let allExist = true;

  for (const dir of requiredDirs) {
    const dirPath = path.join(process.cwd(), dir);
    if (fs.existsSync(dirPath)) {
      log(`✓ Directory exists: ${dir}`, 'green');
    } else {
      log(`✗ Missing directory: ${dir}`, 'red');
      allExist = false;
    }
  }

  return allExist;
}

// Generate project info
function generateProjectInfo() {
  const info = {
    projectName: 'Nullified Solutions Tech Repair',
    buildDate: new Date().toISOString(),
    nodeVersion: process.version,
    platform: process.platform,
    phpVersion: null
  };

  try {
    const { execSync } = require('child_process');
    const phpVersion = execSync('php -v', { encoding: 'utf8' });
    const versionMatch = phpVersion.match(/PHP (\d+\.\d+\.\d+)/);
    if (versionMatch) {
      info.phpVersion = versionMatch[1];
    }
  } catch (err) {
    // PHP not available
  }

  const buildInfoPath = path.join(process.cwd(), '.build-info.json');
  fs.writeFileSync(buildInfoPath, JSON.stringify(info, null, 2));
  log(`✓ Build info written to .build-info.json`, 'green');
}

// Main build process
async function build() {
  header(LINT_ONLY ? 'LINT CHECK' : 'BUILD PROCESS');

  log('Checking environment...', 'blue');
  console.log('');

  const phpOk = checkPHP();

  if (!phpOk) {
    log('Build cannot proceed without PHP', 'red');
    process.exit(1);
  }

  console.log('');
  log('Checking project structure...', 'blue');
  console.log('');

  checkDirectories();
  checkDatabaseConfig();

  console.log('');
  log('Linting PHP files...', 'blue');
  console.log('');

  const lintOk = lintPHPFiles();

  if (LINT_ONLY) {
    console.log('');
    log('='.repeat(60), 'cyan');
    if (lintOk) {
      log('  ✓ LINT CHECK PASSED', 'green');
    } else {
      log('  ✗ LINT CHECK FAILED', 'red');
    }
    log('='.repeat(60), 'cyan');
    console.log('');
    process.exit(lintOk ? 0 : 1);
  }

  console.log('');
  log('Generating build artifacts...', 'blue');
  console.log('');

  generateProjectInfo();

  console.log('');
  log('='.repeat(60), 'cyan');
  log('  ✓ BUILD COMPLETED', 'green');
  log('='.repeat(60), 'cyan');
  console.log('');
  log('Next steps:', 'bright');
  log('  • Run: npm run dev', 'cyan');
  log('  • Open: http://localhost:8000', 'cyan');
  log('  • Share with team using the network URL shown', 'cyan');
  console.log('');
}

build().catch((err) => {
  console.error(colors.red + 'Build failed:' + colors.reset, err);
  process.exit(1);
});
