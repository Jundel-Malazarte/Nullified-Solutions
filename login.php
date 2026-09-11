<?php
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['user_id'])) {
    redirect_to('dashboard.php');
}

$error = '';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($requestMethod === 'POST') {
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } elseif (!is_valid_email($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $conn->prepare('SELECT id, full_name, email, password_hash, status FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] !== 'active') {
                $error = 'This account is not active. Please contact support.';
            } else {
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                redirect_to('dashboard.php');
            }
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Log In | Nullified Solutions</title>
    <link rel="stylesheet" href="css/style.css" />
    <link rel="icon" href="images/Nullified_icon.png" type="image/png" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  </head>
  <body class="account-page">
    <header>
      <a class="brand" href="index.php" aria-label="Nullified Solutions">
        <img src="images/Nullified_Logo.png" alt="Nullified Solutions" />
        <span class="logo">Nullified Solutions</span>
      </a>

      <button class="menu-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false">☰</button>

      <nav id="navMenu">
        <a href="index.php">Home</a>
        <a href="services.php">Services</a>
        <a href="pricing.php">Pricing</a>
        <a href="index.php#faq">FAQ</a>
        <a href="contact.php">Contact Us</a>
      </nav>

      <a class="header-cta" href="signup.php">Sign up to book <span aria-hidden="true">↗</span></a>
    </header>

    <main class="account-main">
      <section class="account-shell" aria-labelledby="login-title">
        <div class="account-intro">
          <p class="eyebrow">WELCOME BACK</p>
          <h1 id="login-title">Pick up where you left off.</h1>
          <p>Sign in to manage your details and keep your next repair appointment moving smoothly.</p>
        </div>

        <div class="account-form-wrap">
          <p class="eyebrow">NULLIFIED SOLUTIONS ACCOUNT</p>
          <h2>Log in to your account</h2>
          <p>Enter your details to continue.</p>

          <?php if ($error !== ''): ?>
            <div class="form-message error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif; ?>

          <button class="account-google" type="button"><span aria-hidden="true">G</span> Continue with Google</button>
          <div class="account-divider"><span>or use your email</span></div>

          <form class="account-form" method="post" action="login.php" id="loginForm">
            <label>Email address<input type="email" name="email" placeholder="you@example.com" autocomplete="email" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required /></label>

            <label>Password
              <div class="password-wrap">
                <input type="password" id="login-password" name="password" placeholder="Your password" autocomplete="current-password" required />
                <button type="button" class="password-toggle" data-target="login-password" aria-label="Show password">
                  <i class="fa-regular fa-eye"></i>
                </i>
                </button>
              </div>
            </label>

            <button type="submit">Log in <span aria-hidden="true">↗</span></button>
          </form>
          <p class="account-switch">New to Nullified Solutions? <a href="signup.php">Create an account</a></p>
        </div>
      </section>
    </main>

    <footer><p>© 2026 Nullified Solutions</p></footer>
    <script src="js/script.js"></script>
  </body>
</html>
