<?php
session_start(); 
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
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600;700&display=swap"
      rel="stylesheet"
    />
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

          <button class="account-google" type="button"><span aria-hidden="true">G</span> Continue with Google</button>
          <div class="account-divider"><span>or use your email</span></div>

          <form class="account-form" id="loginForm">
            <label>Email address<input type="email" name="email" placeholder="you@example.com" autocomplete="email" required /></label>
            <label>Password<input type="password" name="password" placeholder="Your password" autocomplete="current-password" required /></label>
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