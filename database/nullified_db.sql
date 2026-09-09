-- =========================================================
-- Nullified Solutions Tech Repair Database
-- MySQL / XAMPP database schema for PHP application
-- =========================================================

CREATE DATABASE IF NOT EXISTS nullified_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nullified_db;

-- ---------------------------------------------------------
-- 1. Users and authentication
-- ---------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    role ENUM('customer', 'admin', 'technician') NOT NULL DEFAULT 'customer',
    is_premium TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_email (email),
    INDEX idx_users_role (role)
);

CREATE TABLE user_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address VARCHAR(255) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    province VARCHAR(100) DEFAULT NULL,
    postal_code VARCHAR(20) DEFAULT NULL,
    preferred_contact ENUM('email', 'sms', 'phone') NOT NULL DEFAULT 'email',
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_user_profile (user_id)
);

CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    login_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_sessions_user (user_id),
    INDEX idx_sessions_token (session_token)
);

-- ---------------------------------------------------------
-- 2. Repair services and pricing
-- ---------------------------------------------------------
CREATE TABLE repair_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category ENUM('computer', 'phone', 'tablet', 'accessory', 'software') NOT NULL,
    service_name VARCHAR(150) NOT NULL,
    short_description VARCHAR(255) DEFAULT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE repair_pricing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    device_type VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    price_label VARCHAR(50) NOT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES repair_services(id) ON DELETE CASCADE,
    INDEX idx_pricing_service (service_id)
);

-- ---------------------------------------------------------
-- 3. Booking and repair appointments
-- ---------------------------------------------------------
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_id INT DEFAULT NULL,
    device_name VARCHAR(100) NOT NULL,
    device_brand VARCHAR(100) DEFAULT NULL,
    device_model VARCHAR(100) DEFAULT NULL,
    issue_description TEXT NOT NULL,
    preferred_date DATE DEFAULT NULL,
    preferred_time TIME DEFAULT NULL,
    status ENUM('pending', 'confirmed', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    priority ENUM('low', 'normal', 'high') NOT NULL DEFAULT 'normal',
    admin_notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES repair_services(id) ON DELETE SET NULL,
    INDEX idx_bookings_user (user_id),
    INDEX idx_bookings_status (status),
    INDEX idx_bookings_date (preferred_date)
);

CREATE TABLE booking_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- 4. Contact form / send us a message
-- ---------------------------------------------------------
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    subject VARCHAR(200) DEFAULT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contact_email (email)
);

-- ---------------------------------------------------------
-- 5. Premium accounts and subscriptions
-- ---------------------------------------------------------
CREATE TABLE premium_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    monthly_price DECIMAL(10,2) NOT NULL,
    features JSON DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE premium_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    status ENUM('active', 'expired', 'cancelled', 'pending') NOT NULL DEFAULT 'pending',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    payment_reference VARCHAR(150) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES premium_plans(id) ON DELETE RESTRICT,
    INDEX idx_premium_user (user_id),
    INDEX idx_premium_status (status)
);

-- ---------------------------------------------------------
-- 6. Software store
-- ---------------------------------------------------------
CREATE TABLE software_store_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(150) NOT NULL,
    category VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT DEFAULT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    image_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE software_store_purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    purchase_status ENUM('paid', 'pending', 'refunded') NOT NULL DEFAULT 'paid',
    purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES software_store_items(id) ON DELETE CASCADE,
    INDEX idx_store_purchases_user (user_id)
);

-- ---------------------------------------------------------
-- 7. Account settings
-- ---------------------------------------------------------
CREATE TABLE account_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    theme ENUM('light', 'dark', 'system') NOT NULL DEFAULT 'system',
    email_notifications TINYINT(1) NOT NULL DEFAULT 1,
    sms_notifications TINYINT(1) NOT NULL DEFAULT 0,
    booking_reminders TINYINT(1) NOT NULL DEFAULT 1,
    auto_login TINYINT(1) NOT NULL DEFAULT 0,
    timezone VARCHAR(80) NOT NULL DEFAULT 'Asia/Manila',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_account_settings_user (user_id)
);

-- ---------------------------------------------------------
-- 8. Dashboard support views
-- ---------------------------------------------------------
CREATE VIEW v_dashboard_summary AS
SELECT
    u.id,
    u.full_name,
    u.email,
    u.is_premium,
    COUNT(DISTINCT b.id) AS total_bookings,
    SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END) AS completed_bookings,
    SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) AS pending_bookings,
    MAX(pa.expires_at) AS premium_expires_at
FROM users u
LEFT JOIN bookings b ON b.user_id = u.id
LEFT JOIN premium_accounts pa ON pa.user_id = u.id AND pa.status = 'active'
GROUP BY u.id, u.full_name, u.email, u.is_premium;

CREATE VIEW v_user_bookings AS
SELECT
    b.id,
    b.user_id,
    u.full_name,
    rs.service_name,
    b.device_name,
    b.device_brand,
    b.device_model,
    b.issue_description,
    b.preferred_date,
    b.preferred_time,
    b.status,
    b.created_at
FROM bookings b
LEFT JOIN users u ON u.id = b.user_id
LEFT JOIN repair_services rs ON rs.id = b.service_id;

-- ---------------------------------------------------------
-- 9. Seed data for Nullified Solutions Tech Repair
-- ---------------------------------------------------------
INSERT INTO users (full_name, email, password_hash, phone, role, is_premium, status) VALUES
('Admin Nullified', 'admin@nullifiedsolutions.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '09171234567', 'admin', 1, 'active'),
('Maria Santos', 'maria.santos@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '09181234567', 'customer', 0, 'active'),
('Daniel Cruz', 'daniel.cruz@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '09991234567', 'customer', 1, 'active'),
('Angela Reyes', 'angela.reyes@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '09211234567', 'customer', 0, 'active');

INSERT INTO user_profiles (user_id, address, city, province, postal_code, preferred_contact, avatar) VALUES
(1, '123 Tech Avenue', 'Quezon City', 'Metro Manila', '1100', 'email', NULL),
(2, '45 Green Tower Street', 'Davao City', 'Davao del Sur', '8000', 'sms', NULL),
(3, '7 Cove Road', 'Cebu City', 'Cebu', '6000', 'phone', NULL),
(4, '20 Sunset Lane', 'Bacolod City', 'Negros Occidental', '6100', 'email', NULL);

INSERT INTO repair_services (category, service_name, short_description, is_featured) VALUES
('computer', 'Diagnostic Check', 'Full hardware and software device diagnosis', 1),
('computer', 'OS Installation', 'Clean OS installation and setup', 1),
('computer', 'Virus Removal', 'Malware and virus cleanup', 1),
('computer', 'SSD Installation', 'Upgrade to faster solid-state storage', 1),
('computer', 'RAM Upgrade', 'Boost multitasking performance', 0),
('computer', 'Screen Replacement', 'Laptop display replacement', 1),
('computer', 'Keyboard Replacement', 'Keyboard repair and replacement', 0),
('computer', 'Motherboard Repair', 'Advanced electrical board repair', 1),
('phone', 'Screen Replacement', 'Display replacement for smartphones', 1),
('phone', 'Battery Replacement', 'Phone battery replacement service', 1),
('phone', 'Charging Port', 'Charging port repair', 0),
('phone', 'Camera Repair', 'Rear/front camera repair', 0),
('phone', 'Speaker Repair', 'Audio speaker issue fix', 0),
('phone', 'Water Damage Cleaning', 'Device cleaning and recovery', 1),
('phone', 'Software Update / Flashing', 'System update and firmware repair', 0),
('tablet', 'Screen Replacement', 'Tablet display replacement', 0),
('tablet', 'Battery Replacement', 'Tablet battery replacement', 0),
('tablet', 'Charging Port', 'Tablet charging port repair', 0),
('tablet', 'Software Repair', 'System troubleshooting and repair', 0),
('tablet', 'Water Damage Repair', 'Tablet cleaning and internal repair', 0),
('software', 'Antivirus Premium', 'Security protection and malware defense', 1),
('software', 'Driver Pack', 'Essential driver installer package', 0),
('software', 'Remote Support', 'One-on-one troubleshooting session', 1),
('software', 'System Optimization', 'Boost performance and remove clutter', 0);

INSERT INTO repair_pricing (service_id, device_type, price, price_label, notes) VALUES
(1, 'Computer / Laptop', 300.00, '₱300', 'Initial diagnostic fee'),
(2, 'Computer / Laptop', 500.00, '₱500', 'Operating system installation'),
(3, 'Computer / Laptop', 700.00, '₱700', 'Malware and virus cleanup'),
(4, 'Computer / Laptop', 600.00, '₱600', 'SSD upgrade service'),
(5, 'Computer / Laptop', 500.00, '₱500', 'Performance memory upgrade'),
(6, 'Computer / Laptop', 2500.00, '₱2,500+', 'Price depends on display model'),
(7, 'Computer / Laptop', 1200.00, '₱1,200+', 'Keyboard replacement varies by model'),
(8, 'Computer / Laptop', 3500.00, '₱3,500+', 'Complex motherboard repair'),
(9, 'Mobile Phone', 1500.00, '₱1,500+', 'Display replacement on common models'),
(10, 'Mobile Phone', 1000.00, '₱1,000+', 'Battery replacement service'),
(11, 'Mobile Phone', 800.00, '₱800+', 'Port repair and cleaning'),
(12, 'Mobile Phone', 1200.00, '₱1,200+', 'Camera replacement and calibration'),
(13, 'Mobile Phone', 700.00, '₱700+', 'Speaker repair and replacement'),
(14, 'Mobile Phone', 1500.00, '₱1,500+', 'Internal cleaning and recovery'),
(15, 'Mobile Phone', 600.00, '₱600', 'Update and flashing support'),
(16, 'Tablet', 2500.00, '₱2,500+', 'Tablet screen replacement'),
(17, 'Tablet', 1500.00, '₱1,500+', 'Tablet battery replacement'),
(18, 'Tablet', 900.00, '₱900+', 'Charging port issue fix'),
(19, 'Tablet', 700.00, '₱700', 'Software repair and troubleshooting'),
(20, 'Tablet', 1800.00, '₱1,800+', 'Water damage and deep cleaning'),
(21, 'Software', 799.00, '₱799', 'Premium malware protection'),
(22, 'Software', 499.00, '₱499', 'Driver pack for system support'),
(23, 'Software', 1500.00, '₱1,500', 'Remote tech support session'),
(24, 'Software', 899.00, '₱899', 'Performance optimization service');

INSERT INTO bookings (user_id, service_id, device_name, device_brand, device_model, issue_description, preferred_date, preferred_time, status, priority) VALUES
(2, 6, 'Laptop', 'Dell', 'Inspiron 15', 'Screen flickering and dim display after a drop.', '2026-09-12', '10:00:00', 'pending', 'normal'),
(3, 9, 'Phone', 'Samsung', 'Galaxy A52', 'Cracked screen with touch issues.', '2026-09-13', '13:30:00', 'confirmed', 'high'),
(4, 1, 'Desktop Computer', 'HP', 'Pavilion', 'Computer shuts down unexpectedly and runs slow.', '2026-09-14', '09:15:00', 'in_progress', 'normal');

INSERT INTO contact_messages (full_name, email, phone, subject, message, status) VALUES
('John Rivera', 'john.rivera@gmail.com', '09160001001', 'Laptop repair inquiry', 'I need help checking my laptop battery and charger port. Can I walk in for a same-day repair?', 'new'),
('Liza Gomez', 'liza.gomez@gmail.com', '09160001002', 'Phone screen replacement', 'My phone screen has black pixels and touch input is not working. How much is the replacement?', 'read');

INSERT INTO premium_plans (plan_name, description, monthly_price, features) VALUES
('Basic', 'Essential support coverage for minor repairs and diagnostics.', 299.00, '{"priority": "Standard", "discount": "10%", "support": "Email support"}'),
('Pro', 'Priority scheduling and repair discounts for frequent customers.', 699.00, '{"priority": "Priority", "discount": "20%", "support": "Phone + email support"}'),
('Elite', 'Full premium support with advanced device care and priority service.', 1299.00, '{"priority": "VIP", "discount": "30%", "support": "Dedicated support line"}');

INSERT INTO premium_accounts (user_id, plan_id, status, started_at, expires_at, payment_reference) VALUES
(1, 3, 'active', '2026-01-15 00:00:00', '2026-12-15 00:00:00', 'PREM-001'),
(3, 2, 'active', '2026-07-05 00:00:00', '2026-10-05 00:00:00', 'PREM-002');

INSERT INTO software_store_items (item_name, category, price, description, is_featured, image_url) VALUES
('Nullified Antivirus Pro', 'Security', 799.00, 'Real-time protection against malware, ransomware, and phishing threats.', 1, NULL),
('Driver Essentials Pack', 'Utilities', 499.00, 'Updated driver software package for Windows and office devices.', 0, NULL),
('Remote Tech Support', 'Support', 1500.00, 'Hands-on remote troubleshooting session with our technicians.', 1, NULL),
('System Tune-Up Bundle', 'Optimization', 899.00, 'Performance cleaning, startup optimization, and maintenance tasks.', 0, NULL);

INSERT INTO software_store_purchases (user_id, item_id, purchase_status) VALUES
(2, 1, 'paid'),
(3, 3, 'paid'),
(4, 4, 'pending');

INSERT INTO account_settings (user_id, theme, email_notifications, sms_notifications, booking_reminders, auto_login, timezone) VALUES
(1, 'dark', 1, 1, 1, 1, 'Asia/Manila'),
(2, 'light', 1, 0, 1, 0, 'Asia/Manila'),
(3, 'system', 1, 1, 1, 0, 'Asia/Manila'),
(4, 'light', 1, 0, 1, 0, 'Asia/Manila');

-- ---------------------------------------------------------
-- 10. Useful query examples for PHP
-- ---------------------------------------------------------
-- Example login query:
-- SELECT id, full_name, email, password_hash, role, is_premium, status
-- FROM users
-- WHERE email = ?;

-- Example signup query:
-- INSERT INTO users (full_name, email, password_hash, phone, role)
-- VALUES (?, ?, ?, ?, 'customer');

-- Example dashboard query:
-- SELECT * FROM v_dashboard_summary WHERE id = ?;

-- Example my bookings query:
-- SELECT * FROM v_user_bookings WHERE user_id = ? ORDER BY created_at DESC;

-- Example premium accounts query:
-- SELECT p.plan_name, p.monthly_price, pa.status, pa.expires_at
-- FROM premium_accounts pa
-- JOIN premium_plans p ON p.id = pa.plan_id
-- WHERE pa.user_id = ?;

-- Example software store query:
-- SELECT * FROM software_store_items WHERE is_featured = 1 ORDER BY created_at DESC;

-- Example message submission:
-- INSERT INTO contact_messages (full_name, email, phone, subject, message)
-- VALUES (?, ?, ?, ?, ?);

-- Example account settings query:
-- SELECT * FROM account_settings WHERE user_id = ?;
