<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign Up | Nullified Solutions</title>
    <link rel="stylesheet" href="./css/style.css" />
    <link rel="icon" href="images/Nullified_Logo.png" type="image/png" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600;700&display=swap"
      rel="stylesheet"
    />
  </head>
  <body class="account-page">
    <header>
      <a class="brand" href="index.php" aria-label="Nullified Solutions home">
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

      <a class="header-cta" href="signup.php" aria-current="page">Sign up to book <span aria-hidden="true">↗</span></a>
    </header>

    <main class="account-main">
      <section class="account-shell" aria-labelledby="signup-title">
        <div class="account-intro">
          <p class="eyebrow">YOUR REPAIR, ON YOUR TIME</p>
          <h1 id="signup-title">Make your next repair simpler.</h1>
          <p>Create an account to keep your details ready and book your visit with less back-and-forth.</p>
        </div>

        <div class="account-form-wrap">
          <p class="eyebrow">JOIN NULLIFIED SOLUTIONS</p>
          <h2>Create your account</h2>
          <p>Start with the basics. You can update your details anytime.</p>

          
<button class="account-google" type="button"><span aria-hidden="true">G</span> Continue with Google</button>
          <div class="account-divider"><span>or use your email</span></div>
          <form class="account-form" id="signupForm">
            <label>Full name<input type="text" name="name" placeholder="Your name" autocomplete="name" required /></label>
            <label>Email address<input type="email" name="email" placeholder="you@example.com" autocomplete="email" required /></label>
            <label>Password<input type="password" name="password" placeholder="At least 8 characters" minlength="8" autocomplete="new-password" required /></label>
            <label>Confirm password<input type="password" name="confirm-password" placeholder="Re-enter your password" minlength="8" autocomplete="new-password" required /></label>
            <button type="submit">Create account <span aria-hidden="true">↗</span></button>
          </form>
          <p class="account-note">By creating an account, you agree to receive appointment updates from Nullified Solutions.</p>
          <p class="account-switch">Already have an account? <a href="login.php">Log in</a></p>
        </div>
      </section>
    </main>

    <footer><p>© 2026 Nullified Solutions</p></footer>
    <script src="js/script.js"></script>
  </body>
</html>