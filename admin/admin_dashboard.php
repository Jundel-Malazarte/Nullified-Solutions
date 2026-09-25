<?php
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_login();
require_admin();

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

$users = $conn->query('SELECT id, full_name, email, phone, role, status, created_at FROM users ORDER BY created_at DESC');
$bookings = $conn->query(
  'SELECT b.*, u.full_name AS user_name, rs.service_name
     FROM bookings b
     LEFT JOIN users u ON u.id = b.user_id
     LEFT JOIN repair_services rs ON rs.id = b.service_id
     ORDER BY b.created_at DESC'
);
$pricing = $conn->query(
  'SELECT rp.*, rs.service_name
     FROM repair_pricing rp
     LEFT JOIN repair_services rs ON rs.id = rp.service_id
     ORDER BY rp.created_at DESC'
);
$premiumPlans = $conn->query('SELECT * FROM premium_plans ORDER BY monthly_price ASC');
$premiumAccounts = $conn->query(
  'SELECT pa.*, u.full_name, pp.plan_name
     FROM premium_accounts pa
     LEFT JOIN users u ON u.id = pa.user_id
     LEFT JOIN premium_plans pp ON pp.id = pa.plan_id
     ORDER BY pa.created_at DESC'
);
$softwareItems = $conn->query('SELECT * FROM software_store_items ORDER BY created_at DESC');
$services = $conn->query('SELECT id, service_name, category FROM repair_services ORDER BY category, service_name');
$userOptions = $conn->query('SELECT id, full_name, email FROM users ORDER BY full_name ASC');

$overview = [
  'users' => (int) $conn->query('SELECT COUNT(*) AS total FROM users')->fetch_assoc()['total'],
  'active_users' => (int) $conn->query("SELECT COUNT(*) AS total FROM users WHERE status = 'active'")->fetch_assoc()['total'],
  'bookings' => (int) $conn->query('SELECT COUNT(*) AS total FROM bookings')->fetch_assoc()['total'],
  'pending_bookings' => (int) $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status IN ('pending', 'confirmed', 'in_progress')")->fetch_assoc()['total'],
  'pricing_items' => (int) $conn->query('SELECT COUNT(*) AS total FROM repair_pricing')->fetch_assoc()['total'],
  'premium_accounts' => (int) $conn->query('SELECT COUNT(*) AS total FROM premium_accounts')->fetch_assoc()['total'],
];

$editId = (int) ($_GET['edit_id'] ?? 0);
$section = $_GET['section'] ?? 'users';

$editingUser = null;
if ($editId > 0 && $section === 'users') {
  $stmt = $conn->prepare('SELECT id, full_name, email, phone, role, status FROM users WHERE id = ? LIMIT 1');
  $stmt->bind_param('i', $editId);
  $stmt->execute();
  $editingUser = $stmt->get_result()->fetch_assoc();
  $stmt->close();
}

$editingBooking = null;
if ($editId > 0 && $section === 'bookings') {
  $stmt = $conn->prepare('SELECT * FROM bookings WHERE id = ? LIMIT 1');
  $stmt->bind_param('i', $editId);
  $stmt->execute();
  $editingBooking = $stmt->get_result()->fetch_assoc();
  $stmt->close();
}

$editingPricing = null;
if ($editId > 0 && $section === 'pricing') {
  $stmt = $conn->prepare('SELECT * FROM repair_pricing WHERE id = ? LIMIT 1');
  $stmt->bind_param('i', $editId);
  $stmt->execute();
  $editingPricing = $stmt->get_result()->fetch_assoc();
  $stmt->close();
}

$editingPlan = null;
if ($editId > 0 && $section === 'premium-plan') {
  $stmt = $conn->prepare('SELECT * FROM premium_plans WHERE id = ? LIMIT 1');
  $stmt->bind_param('i', $editId);
  $stmt->execute();
  $editingPlan = $stmt->get_result()->fetch_assoc();
  $stmt->close();
}

$editingPremiumAccount = null;
if ($editId > 0 && $section === 'premium-account') {
  $stmt = $conn->prepare('SELECT * FROM premium_accounts WHERE id = ? LIMIT 1');
  $stmt->bind_param('i', $editId);
  $stmt->execute();
  $editingPremiumAccount = $stmt->get_result()->fetch_assoc();
  $stmt->close();
}

$editingSoftware = null;
if ($editId > 0 && $section === 'software') {
  $stmt = $conn->prepare('SELECT * FROM software_store_items WHERE id = ? LIMIT 1');
  $stmt->bind_param('i', $editId);
  $stmt->execute();
  $editingSoftware = $stmt->get_result()->fetch_assoc();
  $stmt->close();
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard | Nullified Solutions</title>
  <style>
    :root {
      --bg: #07111f;
      --card: #101c2d;
      --card-2: #15263d;
      --text: #edf3ff;
      --muted: #a9b7d1;
      --primary: #4f7cff;
      --danger: #ff5a5a;
      --success: #22c55e;
      --border: rgba(255, 255, 255, 0.08);
      --shadow: 0 18px 40px rgba(4, 9, 18, 0.42);
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: linear-gradient(180deg, #06111d 0%, #0d1b2a 100%);
      color: var(--text);
    }

    a {
      color: inherit;
      text-decoration: none;
    }

    table {
      border-collapse: collapse;
      width: 100%;
    }

    th,
    td {
      padding: 12px 10px;
      border-bottom: 1px solid var(--border);
      text-align: left;
      vertical-align: top;
    }

    th {
      color: var(--muted);
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    input,
    select,
    textarea,
    button {
      font: inherit;
    }

    .admin-shell {
      max-width: 1480px;
      margin: 0 auto;
      padding: 24px 16px 48px;
    }

    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
      background: rgba(13, 25, 40, 0.9);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 18px 22px;
      box-shadow: var(--shadow);
    }

    .brand-wrap {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .brand-icon {
      width: 42px;
      height: 42px;
      border-radius: 12px;
      background: linear-gradient(135deg, var(--primary), #8b5cf6);
      display: grid;
      place-items: center;
      font-weight: 700;
    }

    .brand-wrap h1 {
      margin: 0;
      font-size: 1.2rem;
    }

    .topbar-actions {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .tag {
      padding: 8px 12px;
      border-radius: 999px;
      background: rgba(79, 124, 255, 0.12);
      border: 1px solid rgba(79, 124, 255, 0.18);
      color: #dfe9ff;
      font-size: 12px;
    }

    .logout-btn {
      display: inline-block;
      padding: 10px 14px;
      border-radius: 10px;
      background: rgba(255, 90, 90, 0.12);
      color: #ffd7d7;
      border: 1px solid rgba(255, 90, 90, 0.25);
    }

    .nav {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin: 20px 0 26px;
    }

    .nav a {
      padding: 10px 15px;
      border-radius: 10px;
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid var(--border);
      color: var(--muted);
    }

    .nav a:hover {
      color: var(--text);
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }

    .stat-card {
      background: linear-gradient(180deg, var(--card) 0%, #122338 100%);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 18px;
      box-shadow: var(--shadow);
    }

    .stat-label {
      color: var(--muted);
      display: block;
      font-size: 12px;
      margin-bottom: 12px;
      text-transform: uppercase;
      letter-spacing: 0.06em;
    }

    .stat-value {
      font-size: 2rem;
      font-weight: 700;
    }

    .panel {
      background: rgba(16, 28, 45, 0.96);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 18px;
      margin-bottom: 22px;
      box-shadow: var(--shadow);
    }

    .panel-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 14px;
      margin-bottom: 16px;
    }

    .panel-header h2 {
      margin: 0;
      font-size: 1.35rem;
    }

    .panel-header p {
      margin: 6px 0 0;
      color: var(--muted);
    }

    .action-row {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .button,
    .button-secondary,
    .button-danger,
    .button-small {
      border: 0;
      border-radius: 10px;
      padding: 10px 14px;
      font-weight: 700;
      cursor: pointer;
      transition: 0.2s ease;
    }

    .button {
      background: linear-gradient(135deg, var(--primary), #6d5efc);
      color: white;
    }

    .button-secondary {
      background: rgba(255, 255, 255, 0.04);
      color: var(--text);
      border: 1px solid var(--border);
    }

    .button-danger {
      background: rgba(255, 90, 90, 0.12);
      color: #ffd9d9;
      border: 1px solid rgba(255, 90, 90, 0.32);
    }

    .button-small {
      padding: 7px 10px;
      font-size: 12px;
      border: 1px solid var(--border);
      background: rgba(255, 255, 255, 0.03);
      color: var(--text);
    }

    form.inline-form {
      display: inline;
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 14px;
      margin-bottom: 16px;
    }

    label {
      display: flex;
      flex-direction: column;
      gap: 8px;
      color: var(--muted);
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    input,
    select,
    textarea {
      width: 100%;
      background: rgba(255, 255, 255, 0.02);
      color: var(--text);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 10px 12px;
    }

    textarea {
      min-height: 90px;
      resize: vertical;
    }

    .checkbox-row {
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--text);
      font-size: 13px;
      text-transform: none;
      letter-spacing: normal;
    }

    .checkbox-row input {
      width: auto;
    }

    .status-badge {
      display: inline-block;
      padding: 6px 10px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 700;
    }

    .status-active {
      background: rgba(34, 197, 94, 0.16);
      color: #aaf0c6;
    }

    .status-inactive,
    .status-cancelled,
    .status-expired {
      background: rgba(255, 90, 90, 0.12);
      color: #ffc7c7;
    }

    .status-pending,
    .status-confirmed,
    .status-low {
      background: rgba(251, 191, 36, 0.16);
      color: #fce7a9;
    }

    .status-in_progress,
    .status-high {
      background: rgba(99, 102, 241, 0.16);
      color: #d7d8ff;
    }

    .status-completed {
      background: rgba(34, 197, 94, 0.18);
      color: #baf3d0;
    }

    .status-suspended {
      background: rgba(255, 145, 77, 0.16);
      color: #ffd7b5;
    }

    .table-wrap {
      overflow-x: auto;
    }

    .flash {
      margin-bottom: 18px;
      padding: 12px 14px;
      border-radius: 10px;
      border: 1px solid var(--border);
    }

    .flash.success {
      background: rgba(34, 197, 94, 0.12);
      color: #d9ffe7;
    }

    .flash.error {
      background: rgba(255, 90, 90, 0.12);
      color: #ffd7d7;
    }

    @media (max-width: 780px) {

      .topbar,
      .panel-header {
        flex-direction: column;
        align-items: flex-start;
      }
    }
  </style>
</head>

<body>
  <div class="admin-shell">
    <header class="topbar">
      <div class="brand-wrap">
        <div class="brand-icon">N</div>
        <div>
          <h1>Nullified Admin Dashboard</h1>
        </div>
      </div>
      <div class="topbar-actions">
        <span class="tag">Admin Mode</span>
        <a class="logout-btn" href="../dashboard.php?logout=1">Log out</a>
      </div>
    </header>

    <nav class="nav">
      <a href="#overview">Overview</a>
      <a href="#users">Users</a>
      <a href="#bookings">Bookings</a>
      <a href="#pricing">Repair Pricing</a>
      <a href="#premium">Premium</a>
      <a href="#software">Software Store</a>
    </nav>

    <?php if ($flash): ?>
      <div class="flash <?php echo $flash['success'] ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

    <section id="overview" class="stats-grid">
      <div class="stat-card"><span class="stat-label">Total users</span>
        <div class="stat-value"><?php echo (int) $overview['users']; ?></div>
      </div>
      <div class="stat-card"><span class="stat-label">Active users</span>
        <div class="stat-value"><?php echo (int) $overview['active_users']; ?></div>
      </div>
      <div class="stat-card"><span class="stat-label">Bookings</span>
        <div class="stat-value"><?php echo (int) $overview['bookings']; ?></div>
      </div>
      <div class="stat-card"><span class="stat-label">Pending work</span>
        <div class="stat-value"><?php echo (int) $overview['pending_bookings']; ?></div>
      </div>
      <div class="stat-card"><span class="stat-label">Pricing items</span>
        <div class="stat-value"><?php echo (int) $overview['pricing_items']; ?></div>
      </div>
      <div class="stat-card"><span class="stat-label">Premium accounts</span>
        <div class="stat-value"><?php echo (int) $overview['premium_accounts']; ?></div>
      </div>
    </section>

    <section id="users" class="panel">
      <div class="panel-header">
        <div>
          <h2>Users</h2>
          <p>Manage accounts, role, and active status.</p>
        </div>
      </div>

      <form method="post" action="admin_crud.php">
        <input type="hidden" name="action" value="user_save" />
        <input type="hidden" name="section" value="users" />
        <input type="hidden" name="id" value="<?php echo (int) ($editingUser['id'] ?? 0); ?>" />
        <div class="form-grid">
          <label>Full name<input type="text" name="full_name"
              value="<?php echo htmlspecialchars($editingUser['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              required /></label>
          <label>Email<input type="email" name="email"
              value="<?php echo htmlspecialchars($editingUser['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              required /></label>
          <label>Phone<input type="text" name="phone"
              value="<?php echo htmlspecialchars($editingUser['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" /></label>
          <label>Role
            <select name="role">
              <option value="customer" <?php echo (($editingUser['role'] ?? 'customer') === 'customer') ? 'selected' : ''; ?>>Customer</option>
              <option value="admin" <?php echo (($editingUser['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin
              </option>
              <option value="technician" <?php echo (($editingUser['role'] ?? '') === 'technician') ? 'selected' : ''; ?>>
                Technician</option>
            </select>
          </label>
          <label>Status
            <select name="status">
              <option value="active" <?php echo (($editingUser['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>
                Active</option>
              <option value="inactive" <?php echo (($editingUser['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>
                Inactive</option>
              <option value="suspended" <?php echo (($editingUser['status'] ?? '') === 'suspended') ? 'selected' : ''; ?>>
                Suspended</option>
            </select>
          </label>
          <label>Password <?php echo !empty($editingUser['id']) ? '(leave blank to keep current)' : ''; ?><input
              type="password" name="password" value="" <?php echo empty($editingUser['id']) ? 'required' : ''; ?> /></label>
        </div>
        <div class="action-row">
          <button class="button"
            type="submit"><?php echo !empty($editingUser['id']) ? 'Update user' : 'Add user'; ?></button>
          <?php if (!empty($editingUser['id'])): ?><a class="button-secondary"
              href="admin_dashboard.php#users">Cancel</a><?php endif; ?>
        </div>
      </form>

      <div class="table-wrap" style="margin-top:20px;">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Role</th>
              <th>Status</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($user = $users->fetch_assoc()): ?>
              <tr>
                <td>#<?php echo (int) $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($user['phone'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars(ucfirst($user['role']), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><span
                    class="status-badge status-<?php echo htmlspecialchars(str_replace(' ', '_', strtolower($user['status'])), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(strtoupper($user['status']), ENT_QUOTES, 'UTF-8'); ?></span>
                </td>
                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($user['created_at'])), ENT_QUOTES, 'UTF-8'); ?>
                </td>
                <td>
                  <div class="action-row">
                    <a class="button-small"
                      href="admin_dashboard.php?section=users&edit_id=<?php echo (int) $user['id']; ?>#users">Edit</a>
                    <form class="inline-form" method="post" action="admin_crud.php"
                      onsubmit="return confirm('Delete this user?');">
                      <input type="hidden" name="action" value="user_delete" />
                      <input type="hidden" name="section" value="users" />
                      <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>" />
                      <button class="button-danger button-small" type="submit">Delete</button>
                    </form>
                    <form class="inline-form" method="post" action="admin_crud.php">
                      <input type="hidden" name="action" value="user_status" />
                      <input type="hidden" name="section" value="users" />
                      <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>" />
                      <select name="status" onchange="this.form.submit()">
                        <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive
                        </option>
                        <option value="suspended" <?php echo $user['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended
                        </option>
                      </select>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section id="bookings" class="panel">
      <div class="panel-header">
        <div>
          <h2>Bookings</h2>
          <p>Track and update all repair appointments.</p>
        </div>
      </div>
      <form method="post" action="admin_crud.php">
        <input type="hidden" name="action" value="booking_save" />
        <input type="hidden" name="section" value="bookings" />
        <input type="hidden" name="id" value="<?php echo (int) ($editingBooking['id'] ?? 0); ?>" />
        <div class="form-grid">
          <label>User
            <select name="user_id" required>
              <option value="">Select user</option>
              <?php $userOptions->data_seek(0);
              while ($opt = $userOptions->fetch_assoc()): ?>
                <option value="<?php echo (int) $opt['id']; ?>" <?php echo (($editingBooking['user_id'] ?? '') == $opt['id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($opt['full_name'] . ' (' . $opt['email'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endwhile; ?>
            </select>
          </label>
          <label>Service
            <select name="service_id">
              <option value="0">General service</option>
              <?php $services->data_seek(0);
              while ($service = $services->fetch_assoc()): ?>
                <option value="<?php echo (int) $service['id']; ?>" <?php echo (($editingBooking['service_id'] ?? '') == $service['id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($service['service_name'], ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endwhile; ?>
            </select>
          </label>
          <label>Device type<input type="text" name="device_name"
              value="<?php echo htmlspecialchars($editingBooking['device_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              required /></label>
          <label>Brand<input type="text" name="device_brand"
              value="<?php echo htmlspecialchars($editingBooking['device_brand'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" /></label>
          <label>Model<input type="text" name="device_model"
              value="<?php echo htmlspecialchars($editingBooking['device_model'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" /></label>
          <label>Preferred date<input type="date" name="preferred_date"
              value="<?php echo htmlspecialchars($editingBooking['preferred_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" /></label>
          <label>Preferred time<input type="time" name="preferred_time"
              value="<?php echo htmlspecialchars($editingBooking['preferred_time'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" /></label>
          <label>Status
            <select name="status">
              <option value="pending" <?php echo (($editingBooking['status'] ?? 'pending') === 'pending') ? 'selected' : ''; ?>>Pending</option>
              <option value="confirmed" <?php echo (($editingBooking['status'] ?? '') === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
              <option value="in_progress" <?php echo (($editingBooking['status'] ?? '') === 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
              <option value="completed" <?php echo (($editingBooking['status'] ?? '') === 'completed') ? 'selected' : ''; ?>>Completed</option>
              <option value="cancelled" <?php echo (($editingBooking['status'] ?? '') === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
            </select>
          </label>
          <label>Priority
            <select name="priority">
              <option value="low" <?php echo (($editingBooking['priority'] ?? 'normal') === 'low') ? 'selected' : ''; ?>>
                Low</option>
              <option value="normal" <?php echo (($editingBooking['priority'] ?? 'normal') === 'normal') ? 'selected' : ''; ?>>Normal</option>
              <option value="high" <?php echo (($editingBooking['priority'] ?? '') === 'high') ? 'selected' : ''; ?>>High
              </option>
            </select>
          </label>
          <label style="grid-column: 1 / -1;">Issue description<textarea name="issue_description"
              required><?php echo htmlspecialchars($editingBooking['issue_description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea></label>
          <label style="grid-column: 1 / -1;">Admin notes<textarea
              name="admin_notes"><?php echo htmlspecialchars($editingBooking['admin_notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea></label>
        </div>
        <div class="action-row">
          <button class="button"
            type="submit"><?php echo !empty($editingBooking['id']) ? 'Update booking' : 'Add booking'; ?></button>
          <?php if (!empty($editingBooking['id'])): ?><a class="button-secondary"
              href="admin_dashboard.php#bookings">Cancel</a><?php endif; ?>
        </div>
      </form>

      <div class="table-wrap" style="margin-top:20px;">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Customer</th>
              <th>Service</th>
              <th>Device</th>
              <th>Date</th>
              <th>Status</th>
              <th>Priority</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($booking = $bookings->fetch_assoc()): ?>
              <tr>
                <td>#<?php echo (int) $booking['id']; ?></td>
                <td><?php echo htmlspecialchars($booking['user_name'] ?: 'Unknown', ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($booking['service_name'] ?: 'General', ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                  <?php echo htmlspecialchars(($booking['device_brand'] ? $booking['device_brand'] . ' ' : '') . ($booking['device_name'] ?: '') . ' ' . ($booking['device_model'] ?: ''), ENT_QUOTES, 'UTF-8'); ?>
                </td>
                <td><?php echo htmlspecialchars($booking['preferred_date'] ?: 'TBD', ENT_QUOTES, 'UTF-8'); ?></td>
                <td><span
                    class="status-badge status-<?php echo htmlspecialchars(str_replace(' ', '_', strtolower($booking['status'])), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $booking['status'])), ENT_QUOTES, 'UTF-8'); ?></span>
                </td>
                <td><?php echo htmlspecialchars(ucfirst($booking['priority']), ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                  <div class="action-row">
                    <a class="button-small"
                      href="admin_dashboard.php?section=bookings&edit_id=<?php echo (int) $booking['id']; ?>#bookings">Edit</a>
                    <form class="inline-form" method="post" action="admin_crud.php"
                      onsubmit="return confirm('Delete this booking?');">
                      <input type="hidden" name="action" value="booking_delete" />
                      <input type="hidden" name="section" value="bookings" />
                      <input type="hidden" name="id" value="<?php echo (int) $booking['id']; ?>" />
                      <button class="button-danger button-small" type="submit">Delete</button>
                    </form>
                    <form class="inline-form" method="post" action="admin_crud.php">
                      <input type="hidden" name="action" value="booking_status" />
                      <input type="hidden" name="section" value="bookings" />
                      <input type="hidden" name="id" value="<?php echo (int) $booking['id']; ?>" />
                      <select name="status" onchange="this.form.submit()">
                        <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Pending
                        </option>
                        <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>
                          Confirmed</option>
                        <option value="in_progress" <?php echo $booking['status'] === 'in_progress' ? 'selected' : ''; ?>>In
                          progress</option>
                        <option value="completed" <?php echo $booking['status'] === 'completed' ? 'selected' : ''; ?>>
                          Completed</option>
                        <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>
                          Cancelled</option>
                      </select>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section id="pricing" class="panel">
      <div class="panel-header">
        <div>
          <h2>Repair Pricing</h2>
          <p>Update the service prices customers see on the website.</p>
        </div>
      </div>
      <form method="post" action="admin_crud.php">
        <input type="hidden" name="action" value="pricing_save" />
        <input type="hidden" name="section" value="pricing" />
        <input type="hidden" name="id" value="<?php echo (int) ($editingPricing['id'] ?? 0); ?>" />
        <div class="form-grid">
          <label>Service
            <select name="service_id" required>
              <option value="">Select service</option>
              <?php $services->data_seek(0);
              while ($service = $services->fetch_assoc()): ?>
                <option value="<?php echo (int) $service['id']; ?>" <?php echo (($editingPricing['service_id'] ?? '') == $service['id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($service['service_name'], ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endwhile; ?>
            </select>
          </label>
          <label>Device type<input type="text" name="device_type"
              value="<?php echo htmlspecialchars($editingPricing['device_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              required /></label>
          <label>Price<input type="number" step="0.01" min="0" name="price"
              value="<?php echo htmlspecialchars($editingPricing['price'] ?? '0', ENT_QUOTES, 'UTF-8'); ?>"
              required /></label>
          <label>Price label<input type="text" name="price_label"
              value="<?php echo htmlspecialchars($editingPricing['price_label'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              required /></label>
          <label class="checkbox-row" style="display:flex; align-items:center; justify-content:flex-start;"><input
              type="checkbox" name="is_active" value="1" <?php echo (!empty($editingPricing['id']) ? (int) ($editingPricing['is_active'] ?? 0) : 1) ? 'checked' : ''; ?> /> Active listing</label>
          <label style="grid-column: 1 / -1;">Notes<textarea
              name="notes"><?php echo htmlspecialchars($editingPricing['notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea></label>
        </div>
        <div class="action-row">
          <button class="button"
            type="submit"><?php echo !empty($editingPricing['id']) ? 'Update pricing' : 'Add pricing'; ?></button>
          <?php if (!empty($editingPricing['id'])): ?><a class="button-secondary"
              href="admin_dashboard.php#pricing">Cancel</a><?php endif; ?>
        </div>
      </form>

      <div class="table-wrap" style="margin-top:20px;">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Service</th>
              <th>Device</th>
              <th>Price</th>
              <th>Label</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($item = $pricing->fetch_assoc()): ?>
              <tr>
                <td>#<?php echo (int) $item['id']; ?></td>
                <td><?php echo htmlspecialchars($item['service_name'] ?: 'Unknown', ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($item['device_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars(number_format((float) $item['price'], 2), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($item['price_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><span
                    class="status-badge <?php echo (int) $item['is_active'] ? 'status-active' : 'status-inactive'; ?>"><?php echo (int) $item['is_active'] ? 'Active' : 'Inactive'; ?></span>
                </td>
                <td>
                  <div class="action-row">
                    <a class="button-small"
                      href="admin_dashboard.php?section=pricing&edit_id=<?php echo (int) $item['id']; ?>#pricing">Edit</a>
                    <form class="inline-form" method="post" action="admin_crud.php"
                      onsubmit="return confirm('Delete this pricing item?');">
                      <input type="hidden" name="action" value="pricing_delete" />
                      <input type="hidden" name="section" value="pricing" />
                      <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>" />
                      <button class="button-danger button-small" type="submit">Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section id="premium" class="panel">
      <div class="panel-header">
        <div>
          <h2>Premium Plans & Accounts</h2>
          <p>Manage plans, membership status, and account holders.</p>
        </div>
      </div>

      <div style="margin-bottom:24px;">
        <h3 style="margin:0 0 12px;">Premium plans</h3>
        <form method="post" action="admin_crud.php">
          <input type="hidden" name="action" value="plan_save" />
          <input type="hidden" name="section" value="premium" />
          <input type="hidden" name="id" value="<?php echo (int) ($editingPlan['id'] ?? 0); ?>" />
          <div class="form-grid">
            <label>Plan name<input type="text" name="plan_name"
                value="<?php echo htmlspecialchars($editingPlan['plan_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                required /></label>
            <label>Monthly price<input type="number" step="0.01" name="monthly_price"
                value="<?php echo htmlspecialchars($editingPlan['monthly_price'] ?? '0', ENT_QUOTES, 'UTF-8'); ?>"
                required /></label>
            <label class="checkbox-row" style="display:flex; align-items:center; justify-content:flex-start;"><input
                type="checkbox" name="is_active" value="1" <?php echo (!empty($editingPlan['id']) ? (int) ($editingPlan['is_active'] ?? 0) : 1) ? 'checked' : ''; ?> /> Active plan</label>
            <label style="grid-column: 1 / -1;">Description<textarea
                name="description"><?php echo htmlspecialchars($editingPlan['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea></label>
            <label style="grid-column: 1 / -1;">Features<textarea
                name="features"><?php echo htmlspecialchars($editingPlan['features'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea></label>
          </div>
          <div class="action-row">
            <button class="button"
              type="submit"><?php echo !empty($editingPlan['id']) ? 'Update plan' : 'Add plan'; ?></button>
            <?php if (!empty($editingPlan['id'])): ?><a class="button-secondary"
                href="admin_dashboard.php#premium">Cancel</a><?php endif; ?>
          </div>
        </form>

        <div class="table-wrap" style="margin-top:18px;">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Plan</th>
                <th>Monthly price</th>
                <th>Features</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($plan = $premiumPlans->fetch_assoc()): ?>
                <tr>
                  <td>#<?php echo (int) $plan['id']; ?></td>
                  <td><?php echo htmlspecialchars($plan['plan_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td>
                    <?php echo htmlspecialchars(number_format((float) $plan['monthly_price'], 2), ENT_QUOTES, 'UTF-8'); ?>
                  </td>
                  <td><?php echo htmlspecialchars($plan['features'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><span
                      class="status-badge <?php echo (int) $plan['is_active'] ? 'status-active' : 'status-inactive'; ?>"><?php echo (int) $plan['is_active'] ? 'Active' : 'Inactive'; ?></span>
                  </td>
                  <td>
                    <div class="action-row">
                      <a class="button-small"
                        href="admin_dashboard.php?section=premium-plan&edit_id=<?php echo (int) $plan['id']; ?>#premium">Edit</a>
                      <form class="inline-form" method="post" action="admin_crud.php"
                        onsubmit="return confirm('Delete this premium plan?');">
                        <input type="hidden" name="action" value="plan_delete" />
                        <input type="hidden" name="section" value="premium" />
                        <input type="hidden" name="id" value="<?php echo (int) $plan['id']; ?>" />
                        <button class="button-danger button-small" type="submit">Delete</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div>
        <h3 style="margin:0 0 12px;">Premium account list</h3>
        <form method="post" action="admin_crud.php">
          <input type="hidden" name="action" value="account_save" />
          <input type="hidden" name="section" value="premium" />
          <input type="hidden" name="id" value="<?php echo (int) ($editingPremiumAccount['id'] ?? 0); ?>" />
          <div class="form-grid">
            <label>User
              <select name="user_id" required>
                <option value="">Select user</option>
                <?php $userOptions->data_seek(0);
                while ($opt = $userOptions->fetch_assoc()): ?>
                  <option value="<?php echo (int) $opt['id']; ?>" <?php echo (($editingPremiumAccount['user_id'] ?? '') == $opt['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($opt['full_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endwhile; ?>
              </select>
            </label>
            <label>Plan
              <select name="plan_id" required>
                <option value="">Select plan</option>
                <?php $premiumPlans->data_seek(0);
                while ($plan = $premiumPlans->fetch_assoc()): ?>
                  <option value="<?php echo (int) $plan['id']; ?>" <?php echo (($editingPremiumAccount['plan_id'] ?? '') == $plan['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($plan['plan_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endwhile; ?>
              </select>
            </label>
            <label>Status
              <select name="status">
                <option value="active" <?php echo (($editingPremiumAccount['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option>
                <option value="expired" <?php echo (($editingPremiumAccount['status'] ?? '') === 'expired') ? 'selected' : ''; ?>>Expired</option>
                <option value="cancelled" <?php echo (($editingPremiumAccount['status'] ?? '') === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                <option value="pending" <?php echo (($editingPremiumAccount['status'] ?? '') === 'pending') ? 'selected' : ''; ?>>Pending</option>
              </select>
            </label>
            <label>Started date<input type="date" name="started_at"
                value="<?php echo htmlspecialchars($editingPremiumAccount['started_at'] ? date('Y-m-d', strtotime($editingPremiumAccount['started_at'])) : '', ENT_QUOTES, 'UTF-8'); ?>" /></label>
            <label>Expires date<input type="date" name="expires_at"
                value="<?php echo htmlspecialchars($editingPremiumAccount['expires_at'] ? date('Y-m-d', strtotime($editingPremiumAccount['expires_at'])) : '', ENT_QUOTES, 'UTF-8'); ?>" /></label>
            <label>Payment ref<input type="text" name="payment_reference"
                value="<?php echo htmlspecialchars($editingPremiumAccount['payment_reference'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" /></label>
          </div>
          <div class="action-row">
            <button class="button"
              type="submit"><?php echo !empty($editingPremiumAccount['id']) ? 'Update account' : 'Add account'; ?></button>
            <?php if (!empty($editingPremiumAccount['id'])): ?><a class="button-secondary"
                href="admin_dashboard.php#premium">Cancel</a><?php endif; ?>
          </div>
        </form>

        <div class="table-wrap" style="margin-top:18px;">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>User</th>
                <th>Plan</th>
                <th>Status</th>
                <th>Started</th>
                <th>Expires</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($account = $premiumAccounts->fetch_assoc()): ?>
                <tr>
                  <td>#<?php echo (int) $account['id']; ?></td>
                  <td><?php echo htmlspecialchars($account['full_name'] ?: 'Unknown', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($account['plan_name'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><span
                      class="status-badge status-<?php echo htmlspecialchars(str_replace(' ', '_', strtolower($account['status'])), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(strtoupper($account['status']), ENT_QUOTES, 'UTF-8'); ?></span>
                  </td>
                  <td>
                    <?php echo htmlspecialchars($account['started_at'] ? date('M d, Y', strtotime($account['started_at'])) : '—', ENT_QUOTES, 'UTF-8'); ?>
                  </td>
                  <td>
                    <?php echo htmlspecialchars($account['expires_at'] ? date('M d, Y', strtotime($account['expires_at'])) : '—', ENT_QUOTES, 'UTF-8'); ?>
                  </td>
                  <td>
                    <div class="action-row">
                      <a class="button-small"
                        href="admin_dashboard.php?section=premium-account&edit_id=<?php echo (int) $account['id']; ?>#premium">Edit</a>
                      <form class="inline-form" method="post" action="admin_crud.php"
                        onsubmit="return confirm('Delete this premium account?');">
                        <input type="hidden" name="action" value="account_delete" />
                        <input type="hidden" name="section" value="premium" />
                        <input type="hidden" name="id" value="<?php echo (int) $account['id']; ?>" />
                        <button class="button-danger button-small" type="submit">Delete</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section id="software" class="panel">
      <div class="panel-header">
        <div>
          <h2>Software Store</h2>
          <p>Manage software items sold through the store.</p>
        </div>
      </div>
      <form method="post" action="admin_crud.php">
        <input type="hidden" name="action" value="item_save" />
        <input type="hidden" name="section" value="software" />
        <input type="hidden" name="id" value="<?php echo (int) ($editingSoftware['id'] ?? 0); ?>" />
        <div class="form-grid">
          <label>Item name<input type="text" name="item_name"
              value="<?php echo htmlspecialchars($editingSoftware['item_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              required /></label>
          <label>Category<input type="text" name="category"
              value="<?php echo htmlspecialchars($editingSoftware['category'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              required /></label>
          <label>Price<input type="number" step="0.01" min="0" name="price"
              value="<?php echo htmlspecialchars($editingSoftware['price'] ?? '0', ENT_QUOTES, 'UTF-8'); ?>"
              required /></label>
          <label>Image URL<input type="text" name="image_url"
              value="<?php echo htmlspecialchars($editingSoftware['image_url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" /></label>
          <label class="checkbox-row" style="display:flex; align-items:center; justify-content:flex-start;"><input
              type="checkbox" name="is_featured" value="1" <?php echo (!empty($editingSoftware['id']) ? (int) ($editingSoftware['is_featured'] ?? 0) : 0) ? 'checked' : ''; ?> /> Featured item</label>
          <label style="grid-column: 1 / -1;">Description<textarea
              name="description"><?php echo htmlspecialchars($editingSoftware['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea></label>
        </div>
        <div class="action-row">
          <button class="button"
            type="submit"><?php echo !empty($editingSoftware['id']) ? 'Update item' : 'Add item'; ?></button>
          <?php if (!empty($editingSoftware['id'])): ?><a class="button-secondary"
              href="admin_dashboard.php#software">Cancel</a><?php endif; ?>
        </div>
      </form>

      <div class="table-wrap" style="margin-top:20px;">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Item</th>
              <th>Category</th>
              <th>Price</th>
              <th>Featured</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($item = $softwareItems->fetch_assoc()): ?>
              <tr>
                <td>#<?php echo (int) $item['id']; ?></td>
                <td><?php echo htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($item['category'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars(number_format((float) $item['price'], 2), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo (int) $item['is_featured'] ? 'Yes' : 'No'; ?></td>
                <td>
                  <div class="action-row">
                    <a class="button-small"
                      href="admin_dashboard.php?section=software&edit_id=<?php echo (int) $item['id']; ?>#software">Edit</a>
                    <form class="inline-form" method="post" action="admin_crud.php"
                      onsubmit="return confirm('Delete this software item?');">
                      <input type="hidden" name="action" value="item_delete" />
                      <input type="hidden" name="section" value="software" />
                      <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>" />
                      <button class="button-danger button-small" type="submit">Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</body>

</html>