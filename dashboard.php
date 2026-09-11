<?php
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login();

if (isset($_GET['logout'])) {
    logout_user();
    redirect_to('login.php');
}

$userId = (int) $_SESSION['user_id'];
$user = get_user_by_id($conn, $userId);

if (!$user) {
    logout_user();
    redirect_to('login.php');
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_submit'])) {
    $deviceType = trim($_POST['device_type'] ?? '');
    $deviceBrand = trim($_POST['device_brand'] ?? '');
    $deviceModel = trim($_POST['device_model'] ?? '');
    $issueType = trim($_POST['issue_type'] ?? '');
    $preferredDate = trim($_POST['preferred_date'] ?? '');
    $preferredTime = trim($_POST['preferred_time'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $serviceType = trim($_POST['service_type'] ?? '');
    $homeLocation = trim($_POST['home_location'] ?? '');

    if ($deviceType === '' || $deviceBrand === '' || $deviceModel === '' || $issueType === '' || $preferredDate === '' || $description === '') {
        $message = 'Please complete all required booking details.';
    } else {
        $service = $conn->prepare('SELECT id FROM repair_services WHERE service_name = ? LIMIT 1');
        $service->bind_param('s', $issueType);
        $service->execute();
        $serviceData = $service->get_result()->fetch_assoc();
        $service->close();

        $serviceId = $serviceData['id'] ?? null;
        $status = 'pending';
        $priority = 'normal';

        $stmt = $conn->prepare(
            'INSERT INTO bookings (user_id, service_id, device_name, device_brand, device_model, issue_description, preferred_date, preferred_time, status, priority, admin_notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $adminNotes = $serviceType === 'Home Service' ? 'Home service location: ' . $homeLocation : 'Schedule type: ' . $serviceType;
        $stmt->bind_param(
            'iisssssssss',
            $userId,
            $serviceId,
            $deviceType,
            $deviceBrand,
            $deviceModel,
            $description,
            $preferredDate,
            $preferredTime,
            $status,
            $priority,
            $adminNotes
        );

        if ($stmt->execute()) {
            $message = 'Booking submitted successfully. Your request is now in the queue.';
        } else {
            $message = 'There was a problem saving your booking. Please try again.';
        }

        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['settings_submit'])) {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    $updateParts = [];
    $types = '';
    $values = [];

    if ($fullName !== '') {
        $updateParts[] = 'full_name = ?';
        $types .= 's';
        $values[] = $fullName;
    }

    if ($phone !== '') {
        $updateParts[] = 'phone = ?';
        $types .= 's';
        $values[] = $phone;
    }

    if ($password !== '') {
        $updateParts[] = 'password_hash = ?';
        $types .= 's';
        $values[] = password_hash($password, PASSWORD_DEFAULT);
    }

    if (!empty($updateParts)) {
        $sql = 'UPDATE users SET ' . implode(', ', $updateParts) . ' WHERE id = ?';
        $bindValues = [$types . 'i', $values, $userId];

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($bindValues[0], ...array_merge($bindValues[1], [$userId]));
        $stmt->execute();
        $stmt->close();
    }

    $user = get_user_by_id($conn, $userId);
    $_SESSION['user_name'] = $user['full_name'];
    $message = 'Account settings updated successfully.';
}

$bookingRows = get_user_bookings($conn, $userId);
$stats = get_user_stats($conn, $userId);
$pricingGroups = get_pricing_groups($conn);
$premiumPlans = get_premium_plans($conn);
$softwareItems = get_software_items($conn);
$payments = get_user_payments($conn, $userId);

$nextDate = !empty($stats['next_date']) ? date('M j', strtotime($stats['next_date'])) : 'TBD';
$nextDevice = !empty($stats['next_device']) ? $stats['next_device'] : 'No booking';
$nextService = !empty($stats['next_service']) ? $stats['next_service'] : 'No service';
$initials = strtoupper(substr($user['full_name'], 0, 1));
$avatar = !empty($user['full_name']) ? strtoupper(substr($user['full_name'], 0, 2)) : 'NS';
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard | Nullified Solutions</title>
    <link rel="stylesheet" href="./css/style.css" />
    <link rel="stylesheet" href="./css/dashboard.css" />
    <link rel="icon" class="icon" href="images/Nullified_Logo.png" type="image/png" style="border-radius: 50%;" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600;700&display=swap" rel="stylesheet" />
  </head>
  <body class="dash-body">
    <div class="dash-layout">
      <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

      <aside class="sidebar" id="sidebar">
        <a class="sidebar-brand" href="dashboard.php">
          <img src="images/Nullified_Logo.png" alt="Nullified Solutions" />
          <span>Nullified Solutions</span>
        </a>

        <p class="sidebar-label">MENU</p>
        <nav class="sidebar-nav" id="sidebarNav">
          <a href="#overview" class="active" data-target="overview"><span class="ic">🏠</span> Dashboard</a>
          <a href="#book" data-target="book"><span class="ic">🗓️</span> Book a Repair</a>
          <a href="#bookings" data-target="bookings"><span class="ic">🧾</span> My Bookings</a>
          <a href="#pricing" data-target="pricing"><span class="ic">💵</span> Repair Pricing</a>
          <a href="#premium" data-target="premium"><span class="ic">⭐</span> Premium Accounts</a>
          <a href="#software" data-target="software"><span class="ic">💾</span> Software Store</a>
          <a href="#payments" data-target="payments"><span class="ic">💳</span> Payments</a>
          <a href="#settings" data-target="settings"><span class="ic">⚙️</span> Account Settings</a>
        </nav>

        <div class="sidebar-foot">
          <a class="sidebar-logout" href="dashboard.php?logout=1">
            <span class="ic">↩️</span> Log out
          </a>
        </div>
      </aside>

      <div class="dash-main">
        <header class="dash-topbar">
          <div class="dash-topbar-left">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">☰</button>
            <div class="dash-topbar-title">
              <p>Welcome back!</p>
              <h1 id="pageTitle">Dashboard</h1>
            </div>
          </div>

          <div class="dash-user">
            <div class="dash-user-info">
              <span class="dash-user-name" id="userName"><?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></span>
              <span class="dash-user-email" id="userEmail"><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="dash-avatar" id="userAvatar"><?php echo htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8'); ?></div>
            <a class="dash-logout" href="dashboard.php?logout=1">Log out</a>
          </div>
        </header>

        <main class="dash-content">
          <section class="dash-section active" id="overview">
            <div class="dash-section-head">
              <h2>Your overview</h2>
              <p>Here's what's happening with your repairs and account today.</p>
            </div>

            <?php if ($message !== ''): ?>
              <div class="form-message success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <div class="stat-grid">
              <div class="stat-card">
                <p class="stat-label">Active Bookings</p>
                <p class="stat-value" id="statActive"><?php echo (int) $stats['active_bookings']; ?></p>
                <p class="stat-sub">Currently in progress</p>
              </div>
              <div class="stat-card">
                <p class="stat-label">Completed Repairs</p>
                <p class="stat-value" id="statCompleted"><?php echo (int) $stats['completed_bookings']; ?></p>
                <p class="stat-sub">All time</p>
              </div>
              <div class="stat-card">
                <p class="stat-label">Premium Subscriptions</p>
                <p class="stat-value" id="statPremium"><?php echo (int) $stats['premium_count']; ?></p>
                <p class="stat-sub">Active plan</p>
              </div>
              <div class="stat-card">
                <p class="stat-label">Next Appointment</p>
                <p class="stat-value" style="font-size: 18px;"><?php echo htmlspecialchars($nextDate, ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="stat-sub"><?php echo htmlspecialchars($nextService, ENT_QUOTES, 'UTF-8'); ?> — <?php echo htmlspecialchars($nextDevice, ENT_QUOTES, 'UTF-8'); ?></p>
              </div>
            </div>

            <div class="quick-actions">
              <button type="button" data-target="book" class="nav-jump">Book a repair</button>
              <button type="button" class="ghost nav-jump" data-target="premium">Browse premium accounts</button>
              <button type="button" class="ghost nav-jump" data-target="software">Visit software store</button>
            </div>

            <div class="dash-panel">
              <h3>Recent activity</h3>
              <p>Your latest bookings and orders.</p>
              <div class="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>Booking ID</th>
                      <th>Device</th>
                      <th>Service</th>
                      <th>Date</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach (array_slice($bookingRows, 0, 5) as $booking): ?>
                      <tr>
                        <td>#<?php echo (int) $booking['id']; ?></td>
                        <td><?php echo htmlspecialchars($booking['device'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($booking['service'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($booking['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo $booking['status']; ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </section>

          <section class="dash-section" id="book">
            <div class="dash-section-head">
              <h2>Book a repair</h2>
              <p>Tell us about your device and we'll confirm a time that works for you.</p>
            </div>

            <div class="dash-panel">
              <form class="booking-form" method="post" action="dashboard.php#book">
                <label>Device type
                  <select name="device_type" required>
                    <option value="">Select device</option>
                    <option value="Laptop">Laptop / Computer</option>
                    <option value="Phone">Mobile Phone</option>
                    <option value="Tablet">Tablet</option>
                  </select>
                </label>

                <label>Brand
                  <input type="text" name="device_brand" placeholder="e.g. Dell, Samsung" required />
                </label>

                <label>Model
                  <input type="text" name="device_model" placeholder="e.g. XPS 13, Galaxy A52" required />
                </label>

                <label>Issue type
                  <select name="issue_type" required>
                    <option value="">Select issue</option>
                    <option>Diagnostic Check</option>
                    <option>Screen Replacement</option>
                    <option>Battery Replacement</option>
                    <option>Charging Port Repair</option>
                    <option>Camera Repair</option>
                    <option>Speaker / Microphone Repair</option>
                    <option>OS Installation / Software Issue</option>
                    <option>Virus Removal</option>
                    <option>Water Damage</option>
                    <option>Other</option>
                  </select>
                </label>

                <label>Service type
                  <select name="service_type" id="serviceType" required>
                    <option value="">Select service</option>
                    <option>Walk-in / Drop-off</option>
                    <option>Home Service</option>
                  </select>
                </label>

                <label>Home location
                  <input type="text" name="home_location" id="homeLocation" placeholder="Enter exact location if home service" />
                </label>

                <label>Preferred date
                  <input type="date" name="preferred_date" required />
                </label>

                <label>Preferred time
                  <input type="time" name="preferred_time" required />
                </label>

                <label class="full">Describe the problem
                  <textarea name="description" rows="4" placeholder="What's happening with your device?" required></textarea>
                </label>

                <div class="form-actions">
                  <button type="submit" name="booking_submit" value="1">Confirm booking <span aria-hidden="true">↗</span></button>
                </div>
              </form>
            </div>
          </section>

          <section class="dash-section" id="bookings">
            <div class="dash-section-head">
              <h2>My bookings</h2>
              <p>Track the status of every repair you've booked with us.</p>
            </div>

            <div class="dash-panel">
              <div class="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>Booking ID</th>
                      <th>Device</th>
                      <th>Issue</th>
                      <th>Date</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($bookingRows)): ?>
                      <tr><td colspan="5">No bookings yet.</td></tr>
                    <?php else: ?>
                      <?php foreach ($bookingRows as $booking): ?>
                        <tr>
                          <td>#<?php echo (int) $booking['id']; ?></td>
                          <td><?php echo htmlspecialchars($booking['device'], ENT_QUOTES, 'UTF-8'); ?></td>
                          <td><?php echo htmlspecialchars($booking['issue'], ENT_QUOTES, 'UTF-8'); ?></td>
                          <td><?php echo htmlspecialchars($booking['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                          <td><?php echo htmlspecialchars($booking['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </section>

          <section class="dash-section" id="pricing">
            <div class="dash-section-head">
              <h2>Repair pricing</h2>
              <p>View our standard repair pricing for common services.</p>
            </div>

            <div class="pricing-grid">
              <?php foreach ($pricingGroups as $groupName => $items): ?>
                <div class="price-card">
                  <h3><?php echo htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8'); ?></h3>
                  <ul>
                    <?php foreach ($items as $item): ?>
                      <li><span><?php echo htmlspecialchars($item['service_name'], ENT_QUOTES, 'UTF-8'); ?></span><strong><?php echo htmlspecialchars($item['price_label'], ENT_QUOTES, 'UTF-8'); ?></strong></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endforeach; ?>
            </div>
          </section>

          <section class="dash-section" id="premium">
            <div class="dash-section-head">
              <h2>Premium accounts</h2>
              <p>Licensed software subscriptions bundled with your repair account.</p>
            </div>

            <div class="store-grid">
              <?php foreach ($premiumPlans as $plan): ?>
                <div class="store-card">
                  <?php if ($plan['plan_name'] === 'Pro'): ?>
                    <span class="store-badge">Popular</span>
                  <?php endif; ?>
                  <h4><?php echo htmlspecialchars($plan['plan_name'], ENT_QUOTES, 'UTF-8'); ?></h4>
                  <p class="store-price">₱<?php echo number_format((float) $plan['monthly_price'], 0, '.', ','); ?> <span>/ month</span></p>
                  <p class="store-desc"><?php echo htmlspecialchars($plan['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                  <button type="button" class="buy-btn" data-item="<?php echo htmlspecialchars($plan['plan_name'], ENT_QUOTES, 'UTF-8'); ?>">Get now</button>
                </div>
              <?php endforeach; ?>
            </div>
          </section>

          <section class="dash-section" id="software">
            <div class="dash-section-head">
              <h2>Software store</h2>
              <p>One-time software services you can add to any booking.</p>
            </div>

            <div class="store-grid">
              <?php foreach ($softwareItems as $item): ?>
                <div class="store-card">
                  <h4><?php echo htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8'); ?></h4>
                  <p class="store-price">₱<?php echo number_format((float) $item['price'], 0, '.', ','); ?></p>
                  <p class="store-desc"><?php echo htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                  <button type="button" class="buy-btn" data-item="<?php echo htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8'); ?>">Add to booking</button>
                </div>
              <?php endforeach; ?>
            </div>
          </section>

          <section class="dash-section" id="payments">
            <div class="dash-section-head">
              <h2>Payments</h2>
              <p>Review active premiums and payment status for your account.</p>
            </div>

            <div class="dash-panel">
              <table>
                <thead>
                  <tr>
                    <th>Plan</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Expires</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($payments)): ?>
                    <tr><td colspan="4">No payment records yet.</td></tr>
                  <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($payment['plan_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>₱<?php echo number_format((float) $payment['monthly_price'], 0, '.', ','); ?></td>
                        <td><?php echo htmlspecialchars($payment['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars(date('M j, Y', strtotime($payment['expires_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </section>

          <section class="dash-section" id="settings">
            <div class="dash-section-head">
              <h2>Account settings</h2>
              <p>Keep your contact details up to date so we can reach you about your repairs.</p>
            </div>

            <div class="dash-panel" style="max-width: 640px;">
              <div class="avatar-lg" id="settingsAvatar"><?php echo htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8'); ?></div>
              <form class="booking-form" method="post" action="dashboard.php#settings">
                <label>Full name
                  <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?>" required />
                </label>
                <label>Email address
                  <input type="email" value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" disabled />
                </label>
                <label>Phone number
                  <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="09xx xxx xxxx" />
                </label>
                <label>New password
                  <input type="password" name="password" placeholder="Leave blank to keep current password" />
                </label>
                <div class="form-actions">
                  <button type="submit" name="settings_submit" value="1">Save changes</button>
                </div>
              </form>
            </div>
          </section>
        </main>

        <footer><p>© 2026 Nullified Solutions</p></footer>
      </div>
    </div>

    <div class="dash-toast" id="dashToast"><span class="dot"></span><span id="dashToastText">Saved</span></div>

    <script src="js/dashboard.js"></script>
  </body>
</html>
