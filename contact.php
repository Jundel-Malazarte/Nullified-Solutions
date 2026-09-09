<!doctype html>
<html lang="en">
	<head>
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<title>Contact Us | Nullified Solutions</title>
		<link rel="stylesheet" href="./css/style.css" />
		<link rel="icon" href="images/Nullified_Logo.png" type="image/png" />
		<link rel="preconnect" href="https://fonts.googleapis.com" />
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
		<link
			href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600;700&display=swap"
			rel="stylesheet"
		/>
	</head>
	<body class="contact-page">
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
				<a class="active" href="contact.php" aria-current="page">Contact</a>
			</nav>

			<a class="header-cta" href="signup.php">Sign up to book <span aria-hidden="true">↗</span></a>
		</header>

		<main>
			<section class="contact-hero">
				<div>
					<p class="eyebrow">LET'S GET YOUR DEVICE RIGHT</p>
					<h1>Contact Nullified Solutions</h1>
					<p>
						Tell us what is going on with your device. Our team is ready to
						help with clear advice and dependable tech repair.
					</p>
				</div>
				<div class="contact-hero-mark" aria-hidden="true">↗</div>
			</section>

			<section class="contact-content">
				<div class="contact-details">
					<p class="eyebrow">REACH THE SHOP</p>
					<h2>We are here to help.</h2>
					<p class="contact-intro">Choose the easiest way to connect with Nullified Solutions Tech Repair.</p>

					<div class="contact-list">
						<a class="contact-detail" href="tel:+639641186918">
							<span class="contact-icon" aria-hidden="true">☎</span>
							<span><small>Mobile number</small><strong>0964 118 6918</strong></span>
						</a>
						<a class="contact-detail" href="mailto:nullifiedsolutions@gmail.com">
							<span class="contact-icon" aria-hidden="true">@</span>
							<span><small>Email</small><strong>nullifiedsolutions@gmail.com</strong></span>
						</a>
						<div class="contact-detail">
							<span class="contact-icon" aria-hidden="true">⌖</span>
							<span><small>Shop location</small><strong>Nullified Solution Tech Repair<br />Taptap, Cebu City</strong></span>
						</div>
					</div>

					<div class="future-booking">
						<span class="booking-badge">COMING SOON</span>
						<h3>Online booking is on the way.</h3>
						<p>Our future website and booking service will make scheduling your repair even easier.</p>
					</div>
				</div>

				<div class="contact-form-wrap">
					<p class="eyebrow">SEND A MESSAGE</p>
					<h2>What can we repair?</h2>
					<form id="contactForm">
						<label>Full name<input type="text" name="name" placeholder="Your name" required /></label>
						<label>Email address<input type="email" name="email" placeholder="you@example.com" required /></label>
						<label>Phone number<input type="tel" name="phone" placeholder="09** *** ****" /></label>
						<label>Tell us about your device<textarea name="message" rows="5" placeholder="Describe the issue..." required></textarea></label>
						<button type="submit">Send inquiry <span aria-hidden="true">↗</span></button>
					</form>
				</div>
			</section>
		</main>

		<footer><p>© 2026 Nullified Solutions</p></footer>
		<script src="js/script.js"></script>
	</body>
</html>
