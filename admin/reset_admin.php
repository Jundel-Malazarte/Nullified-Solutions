<?php
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['user_id'])) {
    if (($_SESSION['user_role'] ?? '') === 'admin') {
        redirect_to('admin_dashboard.php');
    }
    redirect_to('../dashboard.php');
}

$error = '';
$success = '';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($requestMethod === 'POST') {
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm-password'] ?? '';

    if ($email === '' || $password === '' || $confirmPassword === '') {
        $error = 'Please complete all fields to reset the admin password.';
    } elseif (!is_valid_email($email)) {
        $error = 'Please enter a valid email address.';
    } elseif (!is_strong_password($password)) {
        $error = 'Password must be at least 8 characters and include uppercase, lowercase, a number, and a symbol.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match. Please try again.';
    } else {
        $stmt = $conn->prepare('SELECT id, role FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $error = 'No account found with that email address.';
        } elseif (($user['role'] ?? 'customer') !== 'admin') {
            $error = 'This account does not have admin access.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->bind_param('si', $passwordHash, $user['id']);

            if ($stmt->execute()) {
                $stmt->close();
                $success = 'Admin password updated successfully. You can now log in with your new password.';
            } else {
                $error = 'We could not update the password right now. Please try again.';
                if (isset($stmt)) {
                    $stmt->close();
                }
            }
        }
    }
}
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset Admin Password | Nullified Solutions</title>
    <link rel="icon" href="../images/Nullified_Logo.png" type="image/png" />
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  </head>
  <body class="account-page">
    <header>
      <a class="brand" href="../index.php" aria-label="Nullified Solutions home">
        <img src="../images/Nullified_Logo.png" alt="Nullified Solutions" />
        <span class="logo">Nullified Solutions</span>
      </a>

      <button class="menu-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false">☰</button>

      <nav id="navMenu">
        <a href="../index.php">Home</a>
        <a href="../services.php">Services</a>
        <a href="../pricing.php">Pricing</a>
        <a href="../index.php#faq">FAQ</a>
        <a href="../contact.php">Contact Us</a>
      </nav>

      <a class="header-cta" href="admin_login.php" aria-current="page">Admin login <span aria-hidden="true">↗</span></a>
    </header>

    <main class="account-main">
      <section class="account-shell" aria-labelledby="reset-title">
        <div class="account-intro">
          <p class="eyebrow">ADMIN PORTAL</p>
          <h1 id="reset-title">Reset your admin password.</h1>
          <p>Update the password for an existing admin account. Enter your admin email and create a new secure password.</p>
        </div>

        <div class="account-form-wrap">
          <p class="eyebrow">NULLIFIED SOLUTIONS ADMIN</p>
          <h2>Reset admin password</h2>
          <p>Enter your admin email and create a new password. The password will be stored securely as a hash.</p>

          <?php if ($error !== ''): ?>
            <div class="form-message error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif; ?>

          <?php if ($success !== ''): ?>
            <div class="form-message success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif; ?>

          <form class="account-form" method="post" action="reset_admin.php" id="resetForm">
            <label>Admin email address
              <input type="email" name="email" placeholder="admin@example.com" autocomplete="email" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
            </label>

            <label>New password
              <div class="password-wrap">
                <input type="password" id="password" name="password" placeholder="At least 8 characters" minlength="8" autocomplete="new-password" required />
                <button type="button" class="password-toggle" data-target="password" aria-label="Show password">
                  <i class="fa-regular fa-eye"></i>
                </button>
              </div>
            </label>

            <label>Confirm new password
              <div class="password-wrap">
                <input type="password" id="confirm-password" name="confirm-password" placeholder="Re-enter your new password" minlength="8" autocomplete="new-password" required />
                <button type="button" class="password-toggle" data-target="confirm-password" aria-label="Show password">
                  <i class="fa-regular fa-eye"></i>
                </button>
              </div>
            </label>

            <button type="submit">Update admin password <span aria-hidden="true">↗</span></button>
          </form>

          <p class="account-switch">Remember your password? <a href="admin_login.php">Log in</a></p>
        </div>
      </section>
    </main>

    <footer><p>© 2026 Nullified Solutions</p></footer>
    <script src="../js/script.js"></script>
  </body>
</html>
