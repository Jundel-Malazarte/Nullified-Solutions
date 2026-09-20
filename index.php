<?php
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/includes/functions.php';

$pricingGroups = get_pricing_groups($conn);
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Nullified Solutions</title>
    <link rel="stylesheet" href="css/style.css?v=20260920" />
    <link rel="icon" class="icon" href="images/Nullified_Logo.png" type="image/png" style="border:radius: 50%;" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet"/>
  </head>
  <body>
    <header class="site-header">
      <a class="brand" href="index.php" aria-label="Nullified Solutions">
        <div class="logo">
          <img src="images/Nullified_Logo.png" alt="Nullified Solutions" />
        </div>
        <div class="logo">Nullified Solutions</div>
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

    <main>
      <section class="hero">
        <div class="overlay">
          <p class="eyebrow">NULLIFIED SOLUTIONS TECH REPAIR</p>
          <h1 class="typing-wrap">
            <span class="typing-text" data-text="Computer & Phone Repair | Laptop & Tablet Repair | Fast Tech Repair |"></span>
          </h1>

          <p class="hero-copy">Fast, affordable, and reliable repair services for the devices your work and life depend on.</p>

          <div class="hero-actions">
            <button onclick="window.location.href='login.php'" type="button">Book a repair <span aria-hidden="true">↗</span></button>
            <a class="text-link" href="#services">Explore services <span aria-hidden="true">↓</span></a>
          </div>
        </div>
        <div class="hero-note"><span class="status-dot"></span> Same-day service available</div>
      </section>

      <section class="services" id="services">
        <div class="section-heading">
          <p class="eyebrow">WHAT WE FIX</p>
          <h2>Our Services</h2>
          <p>Practical, careful repairs from quick fixes to tricky hardware problems.</p>
        </div>

        <div class="cards">
          <a href="services.php" class="service-link">
            <div class="card">
              <h3>💻 Laptop Repair</h3>
              <p>Hardware upgrades, SSD installation, screen replacement and motherboard repair.</p>
            </div>
          </a>

          <a href="services.php" class="service-link">
            <div class="card">
              <h3>📱 Phone Repair</h3>
              <p>Screen replacement, battery replacement, camera repair and charging port repair.</p>
            </div>
          </a>

          <a href="services.php" class="service-link">
            <div class="card">
              <h3>🛠 Diagnostics</h3>
              <p>Complete hardware and software diagnostics for all major brands.</p>
            </div>
          </a>
        </div>
      </section>

      <section class="pricing">
        <h2>Repair Pricing</h2>
        <p class="pricing-subtitle">Transparent pricing with no hidden fees.</p>

        <div class="pricing-grid">
          <?php foreach ($pricingGroups as $groupName => $items): ?>
            <div class="price-card">
              <h3><?php echo htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8'); ?></h3>
              <ul>
                <?php foreach ($items as $item): ?>
                  <li>
                    <span><?php echo htmlspecialchars($item['service_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <strong><?php echo htmlspecialchars($item['price_label'], ENT_QUOTES, 'UTF-8'); ?></strong>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="faq" id="faq">
        <h2>Frequently Asked Questions</h2>
        <div class="faq-container">
          <div class="faq-item">
            <button class="faq-question">
              How long does a repair take?
              <span>+</span>
            </button>
            <div class="faq-answer">
              <p>Most repairs are completed within 1–2 hours. Complex motherboard repairs may require 2–5 business days.</p>
            </div>
          </div>

          <div class="faq-item">
            <button class="faq-question">
              Do you provide a warranty?
              <span>+</span>
            </button>
            <div class="faq-answer">
              <p>Yes. Most repairs include a 30–90 day service warranty depending on the repair performed.</p>
            </div>
          </div>

          <div class="faq-item">
            <button class="faq-question">
              Do I need an appointment?
              <span>+</span>
            </button>
            <div class="faq-answer">
              <p>Walk-ins are welcome, but appointments help us serve you faster.</p>
            </div>
          </div>

          <div class="faq-item">
            <button class="faq-question">
              Can my files be recovered?
              <span>+</span>
            </button>
            <div class="faq-answer">
              <p>In many cases yes. We offer data recovery services depending on the condition of the storage device.</p>
            </div>
          </div>
        </div>
      </section>

      <section class="contact" id="contact">
        <h2>Send Us a Message</h2>
        <p>Have questions? Send us a message and we'll get back to you as soon as possible.</p>

        <form id="contactForm">
          <input type="text" placeholder="Full Name" required />
          <input type="email" placeholder="Email Address" required />
          <input type="text" placeholder="Phone Number" />
          <textarea rows="6" placeholder="Describe your device problem..." required></textarea>
          <button type="submit">Send Message</button>
        </form>
      </section>

      <section class="about">
        <h2>Why Choose Us?</h2>
        <div class="features">
          <div>✔ Certified Technicians</div>
          <div>✔ Genuine Parts</div>
          <div>✔ Same Day Service</div>
          <div>✔ 90-Day Warranty</div>
        </div>
      </section>
    </main>

    <footer>
      <p>© 2026 Nullified Solutions</p>
    </footer>

    <script src="./js/script.js"></script>
  </body>
</html>
