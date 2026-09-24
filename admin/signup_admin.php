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
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($requestMethod === 'POST') {
    $fullName = trim($_POST['name'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm-password'] ?? '';

    if ($fullName === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $error = 'Please complete all fields to create the admin account.';
    } elseif (!is_valid_full_name($fullName)) {
        $error = 'Please enter a valid full name using letters and spaces only.';
    } elseif (!is_valid_email($email)) {
        $error = 'Please enter a valid email address.';
    } elseif (!is_strong_password($password)) {
        $error = 'Password must be at least 8 characters and include uppercase, lowercase, a number, and a symbol.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match. Please try again.';
    } else {
        $stmt = $conn->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            $error = 'An account already exists for that email address.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users (full_name, email, password_hash, phone, role, is_premium, status) VALUES (?, ?, ?, NULL, "admin", 0, "active")');
            $stmt->bind_param('ss', $fullName, $email, $passwordHash);

            if ($stmt->execute()) {
                $userId = $stmt->insert_id;
                $stmt->close();

                $_SESSION['user_id'] = (int) $userId;
                $_SESSION['user_name'] = $fullName;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'admin';

                redirect_to('admin_dashboard.php');
            }

            $error = 'We could not create the admin account right now. Please try again.';
            if (isset($stmt)) {
                $stmt->close();
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
    <title>Admin Sign Up | Nullified Solutions</title>
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
      <section class="account-shell" aria-labelledby="signup-title">
        <div class="account-intro">
          <p class="eyebrow">ADMIN PORTAL</p>
          <h1 id="signup-title">Create a secure admin account.</h1>
          <p>Register a new admin user with a hashed password so the account works with the same encrypted authentication flow as the app.</p>
        </div>

        <div class="account-form-wrap">
          <p class="eyebrow">JOIN NULLIFIED SOLUTIONS ADMIN</p>
          <h2>Create admin account</h2>
          <p>Start with the basics. The password will be stored securely as a hash.</p>

          <?php if ($error !== ''): ?>
            <div class="form-message error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif; ?>

          <button class="account-google" type="button"><span aria-hidden="true">G</span> Continue with Google</button>
          <div class="account-divider"><span>or use your email</span></div>

          <form class="account-form" method="post" action="signup_admin.php" id="signupForm">
            <label>Full name
              <input type="text" name="name" placeholder="Admin name" autocomplete="name" value="<?php echo htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
            </label>
            <label>Email address
              <input type="email" name="email" placeholder="admin@example.com" autocomplete="email" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
            </label>

            <label>Password
              <div class="password-wrap">
                <input type="password" id="password" name="password" placeholder="At least 8 characters" minlength="8" autocomplete="new-password" required />
                <button type="button" class="password-toggle" data-target="password" aria-label="Show password">
                  <i class="fa-regular fa-eye"></i>
                </button>
              </div>
            </label>

            <label>Confirm password
              <div class="password-wrap">
                <input type="password" id="confirm-password" name="confirm-password" placeholder="Re-enter your password" minlength="8" autocomplete="new-password" required />
                <button type="button" class="password-toggle" data-target="confirm-password" aria-label="Show password">
                  <i class="fa-regular fa-eye"></i>
                </button>
              </div>
            </label>

            <button type="submit">Create admin account <span aria-hidden="true">↗</span></button>
          </form>

          <p class="account-note">By creating an admin account, you agree to the administrative access requirements for Nullified Solutions.</p>
          <p class="account-switch">Already have an admin account? <a href="admin_login.php">Log in</a></p>
        </div>
      </section>
    </main>

    <footer><p>© 2026 Nullified Solutions</p></footer>
    <script src="../js/script.js"></script>
  </body>
</html>
