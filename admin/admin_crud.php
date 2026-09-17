<?php
require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login();
require_admin();

function redirect_admin($message, $success = true, $section = '')
{
    $_SESSION['admin_flash'] = [
        'message' => $message,
        'success' => $success,
        'section' => $section,
    ];

    $target = 'admin_dashboard.php';
    if ($section !== '') {
        $target .= '#' . $section;
    }

    header('Location: ' . $target);
    exit;
}

function bind_param_values($stmt, array $params): void
{
    $types = '';
    foreach ($params as $value) {
        if (is_int($value) || is_bool($value)) {
            $types .= 'i';
        } elseif (is_float($value) || is_double($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }

    $refs = [];
    foreach ($params as $index => $value) {
        $refs[$index] = &$params[$index];
    }

    array_unshift($refs, $types);
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

function fetch_single_row($conn, $query, ...$params)
{
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return null;
    }

    bind_param_values($stmt, $params);

    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('Invalid request method.', false, 'overview');
}

$action = $_POST['action'] ?? '';
$section = $_POST['section'] ?? 'overview';

if ($action === 'user_save') {
    $id = (int) ($_POST['id'] ?? 0);
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $role = in_array($_POST['role'] ?? '', ['customer', 'admin', 'technician'], true) ? $_POST['role'] : 'customer';
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive', 'suspended'], true) ? $_POST['status'] : 'active';
    $password = $_POST['password'] ?? '';

    if ($fullName === '' || $email === '') {
        redirect_admin('Name and email are required for each user.', false, $section ?: 'users');
    }
    if (!is_valid_email($email)) {
        redirect_admin('Please provide a valid user email address.', false, $section ?: 'users');
    }

    if ($id > 0) {
        $existing = fetch_single_row($conn, 'SELECT id FROM users WHERE email = ? AND id != ?', $email, $id);
        if ($existing) {
            redirect_admin('Another user already uses that email address.', false, $section ?: 'users');
        }

        $sql = 'UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?, status = ?';
        $params = [$fullName, $email, $phone, $role, $status];

        if ($password !== '') {
            $sql .= ', password_hash = ?';
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql .= ' WHERE id = ?';
        $params[] = $id;

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            redirect_admin('Could not update the user record.', false, $section ?: 'users');
        }

        bind_param_values($stmt, $params);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) {
            redirect_admin('User update failed. Please try again.', false, $section ?: 'users');
        }

        redirect_admin('User updated successfully.', true, $section ?: 'users');
    }

    if (fetch_single_row($conn, 'SELECT id FROM users WHERE email = ?', $email)) {
        redirect_admin('This email address is already registered.', false, $section ?: 'users');
    }
    if ($password === '') {
        redirect_admin('A password is required when creating a user.', false, $section ?: 'users');
    }
    if (!is_strong_password($password)) {
        redirect_admin('Password must be at least 8 characters and include uppercase, lowercase, number, and special character.', false, $section ?: 'users');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO users (full_name, email, phone, role, status, password_hash) VALUES (?, ?, ?, ?, ?, ?)');
    $params = [$fullName, $email, $phone, $role, $status, $hash];
    bind_param_values($stmt, $params);

    if (!$stmt->execute()) {
        $stmt->close();
        redirect_admin('User creation failed. Please check the data and try again.', false, $section ?: 'users');
    }

    $stmt->close();
    redirect_admin('User created successfully.', true, $section ?: 'users');
}

if ($action === 'user_delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        redirect_admin('A valid user is required to delete.', false, $section ?: 'users');
    }

    $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
    bind_param_values($stmt, [$id]);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        redirect_admin('User could not be deleted.', false, $section ?: 'users');
    }

    redirect_admin('User deleted successfully.', true, $section ?: 'users');
}

if ($action === 'user_status') {
    $id = (int) ($_POST['id'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive', 'suspended'], true) ? $_POST['status'] : 'active';

    if ($id <= 0) {
        redirect_admin('A valid user ID is required.', false, $section ?: 'users');
    }

    $stmt = $conn->prepare('UPDATE users SET status = ? WHERE id = ?');
    bind_param_values($stmt, [$status, $id]);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        redirect_admin('User status could not be updated.', false, $section ?: 'users');
    }

    redirect_admin('User status updated successfully.', true, $section ?: 'users');
}

if ($action === 'booking_save') {
    $id = (int) ($_POST['id'] ?? 0);
    $userId = (int) ($_POST['user_id'] ?? 0);
    $serviceId = (int) ($_POST['service_id'] ?? 0);
    $deviceName = trim((string) ($_POST['device_name'] ?? ''));
    $deviceBrand = trim((string) ($_POST['device_brand'] ?? ''));
    $deviceModel = trim((string) ($_POST['device_model'] ?? ''));
    $issue = trim((string) ($_POST['issue_description'] ?? ''));
    $preferredDate = $_POST['preferred_date'] ?? '';
    $preferredTime = $_POST['preferred_time'] ?? '';
    $status = in_array($_POST['status'] ?? '', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'], true) ? $_POST['status'] : 'pending';
    $priority = in_array($_POST['priority'] ?? '', ['low', 'normal', 'high'], true) ? $_POST['priority'] : 'normal';
    $adminNotes = trim((string) ($_POST['admin_notes'] ?? ''));

    if ($userId <= 0 || $deviceName === '' || $issue === '') {
        redirect_admin('User, device name, and issue description are required.', false, $section ?: 'bookings');
    }

    if ($preferredDate === '') {
        $preferredDate = null;
    }
    if ($preferredTime === '') {
        $preferredTime = null;
    }

    if ($id > 0) {
        $stmt = $conn->prepare('UPDATE bookings SET user_id = ?, service_id = ?, device_name = ?, device_brand = ?, device_model = ?, issue_description = ?, preferred_date = ?, preferred_time = ?, status = ?, priority = ?, admin_notes = ? WHERE id = ?');
        $params = [$userId, $serviceId, $deviceName, $deviceBrand, $deviceModel, $issue, $preferredDate, $preferredTime, $status, $priority, $adminNotes, $id];
    } else {
        $stmt = $conn->prepare('INSERT INTO bookings (user_id, service_id, device_name, device_brand, device_model, issue_description, preferred_date, preferred_time, status, priority, admin_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $params = [$userId, $serviceId, $deviceName, $deviceBrand, $deviceModel, $issue, $preferredDate, $preferredTime, $status, $priority, $adminNotes];
    }

    bind_param_values($stmt, $params);
    if (!$stmt->execute()) {
        $stmt->close();
        redirect_admin('Booking save failed. Please verify the details and try again.', false, $section ?: 'bookings');
    }

    $stmt->close();
    redirect_admin($id > 0 ? 'Booking updated successfully.' : 'Booking created successfully.', true, $section ?: 'bookings');
}

if ($action === 'booking_delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        redirect_admin('A valid booking ID is required.', false, $section ?: 'bookings');
    }

    $stmt = $conn->prepare('DELETE FROM bookings WHERE id = ?');
    bind_param_values($stmt, [$id]);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        redirect_admin('Booking could not be deleted.', false, $section ?: 'bookings');
    }

    redirect_admin('Booking deleted successfully.', true, $section ?: 'bookings');
}

if ($action === 'booking_status') {
    $id = (int) ($_POST['id'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'], true) ? $_POST['status'] : 'pending';

    if ($id <= 0) {
        redirect_admin('A valid booking ID is required.', false, $section ?: 'bookings');
    }

    $stmt = $conn->prepare('UPDATE bookings SET status = ? WHERE id = ?');
    bind_param_values($stmt, [$status, $id]);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        redirect_admin('Booking status could not be updated.', false, $section ?: 'bookings');
    }

    redirect_admin('Booking status updated successfully.', true, $section ?: 'bookings');
}

if ($action === 'pricing_save') {
    $id = (int) ($_POST['id'] ?? 0);
    $serviceId = (int) ($_POST['service_id'] ?? 0);
    $deviceType = trim((string) ($_POST['device_type'] ?? ''));
    $price = (float) ($_POST['price'] ?? 0);
    $priceLabel = trim((string) ($_POST['price_label'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($serviceId <= 0 || $deviceType === '' || $priceLabel === '') {
        redirect_admin('Service, device type, and price label are required.', false, $section ?: 'pricing');
    }

    if ($id > 0) {
        $stmt = $conn->prepare('UPDATE repair_pricing SET service_id = ?, device_type = ?, price = ?, price_label = ?, notes = ?, is_active = ? WHERE id = ?');
        $params = [$serviceId, $deviceType, $price, $priceLabel, $notes, $isActive, $id];
    } else {
        $stmt = $conn->prepare('INSERT INTO repair_pricing (service_id, device_type, price, price_label, notes, is_active) VALUES (?, ?, ?, ?, ?, ?)');
        $params = [$serviceId, $deviceType, $price, $priceLabel, $notes, $isActive];
    }

    bind_param_values($stmt, $params);
    if (!$stmt->execute()) {
        $stmt->close();
        redirect_admin('Repair pricing save failed. Please check the values and try again.', false, $section ?: 'pricing');
    }

    $stmt->close();
    redirect_admin($id > 0 ? 'Pricing item updated successfully.' : 'Pricing item created successfully.', true, $section ?: 'pricing');
}

if ($action === 'pricing_delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        redirect_admin('A valid pricing ID is required.', false, $section ?: 'pricing');
    }

    $stmt = $conn->prepare('DELETE FROM repair_pricing WHERE id = ?');
    bind_param_values($stmt, [$id]);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        redirect_admin('Pricing entry could not be deleted.', false, $section ?: 'pricing');
    }

    redirect_admin('Pricing entry deleted successfully.', true, $section ?: 'pricing');
}

if ($action === 'plan_save') {
    $id = (int) ($_POST['id'] ?? 0);
    $planName = trim((string) ($_POST['plan_name'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $monthlyPrice = (float) ($_POST['monthly_price'] ?? 0);
    $features = trim((string) ($_POST['features'] ?? ''));
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($planName === '' || $monthlyPrice <= 0) {
        redirect_admin('Plan name and monthly price are required.', false, $section ?: 'premium');
    }

    if ($id > 0) {
        $stmt = $conn->prepare('UPDATE premium_plans SET plan_name = ?, description = ?, monthly_price = ?, features = ?, is_active = ? WHERE id = ?');
        $params = [$planName, $description, $monthlyPrice, $features, $isActive, $id];
    } else {
        $stmt = $conn->prepare('INSERT INTO premium_plans (plan_name, description, monthly_price, features, is_active) VALUES (?, ?, ?, ?, ?)');
        $params = [$planName, $description, $monthlyPrice, $features, $isActive];
    }

    bind_param_values($stmt, $params);
    if (!$stmt->execute()) {
        $stmt->close();
        redirect_admin('Premium plan save failed.', false, $section ?: 'premium');
    }

    $stmt->close();
    redirect_admin($id > 0 ? 'Premium plan updated successfully.' : 'Premium plan created successfully.', true, $section ?: 'premium');
}

if ($action === 'plan_delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        redirect_admin('A valid premium plan ID is required.', false, $section ?: 'premium');
    }

    $stmt = $conn->prepare('DELETE FROM premium_plans WHERE id = ?');
    bind_param_values($stmt, [$id]);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        redirect_admin('Premium plan could not be deleted.', false, $section ?: 'premium');
    }

    redirect_admin('Premium plan deleted successfully.', true, $section ?: 'premium');
}

if ($action === 'account_save') {
    $id = (int) ($_POST['id'] ?? 0);
    $userId = (int) ($_POST['user_id'] ?? 0);
    $planId = (int) ($_POST['plan_id'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['active', 'expired', 'cancelled', 'pending'], true) ? $_POST['status'] : 'pending';
    $startedAt = $_POST['started_at'] ?? '';
    $expiresAt = $_POST['expires_at'] ?? '';
    $paymentReference = trim((string) ($_POST['payment_reference'] ?? ''));

    if ($userId <= 0 || $planId <= 0) {
        redirect_admin('User and premium plan are required.', false, $section ?: 'premium');
    }

    if ($startedAt === '') {
        $startedAt = null;
    }
    if ($expiresAt === '') {
        $expiresAt = null;
    }

    if ($id > 0) {
        $stmt = $conn->prepare('UPDATE premium_accounts SET user_id = ?, plan_id = ?, status = ?, started_at = ?, expires_at = ?, payment_reference = ? WHERE id = ?');
        $params = [$userId, $planId, $status, $startedAt, $expiresAt, $paymentReference, $id];
    } else {
        $stmt = $conn->prepare('INSERT INTO premium_accounts (user_id, plan_id, status, started_at, expires_at, payment_reference) VALUES (?, ?, ?, ?, ?, ?)');
        $params = [$userId, $planId, $status, $startedAt, $expiresAt, $paymentReference];
    }

    bind_param_values($stmt, $params);
    if (!$stmt->execute()) {
        $stmt->close();
        redirect_admin('Premium account save failed.', false, $section ?: 'premium');
    }

    $stmt->close();
    redirect_admin($id > 0 ? 'Premium account updated successfully.' : 'Premium account created successfully.', true, $section ?: 'premium');
}

if ($action === 'account_delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        redirect_admin('A valid premium account ID is required.', false, $section ?: 'premium');
    }

    $stmt = $conn->prepare('DELETE FROM premium_accounts WHERE id = ?');
    bind_param_values($stmt, [$id]);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        redirect_admin('Premium account could not be deleted.', false, $section ?: 'premium');
    }

    redirect_admin('Premium account deleted successfully.', true, $section ?: 'premium');
}

if ($action === 'item_save') {
    $id = (int) ($_POST['id'] ?? 0);
    $itemName = trim((string) ($_POST['item_name'] ?? ''));
    $category = trim((string) ($_POST['category'] ?? ''));
    $price = (float) ($_POST['price'] ?? 0);
    $description = trim((string) ($_POST['description'] ?? ''));
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $imageUrl = trim((string) ($_POST['image_url'] ?? ''));

    if ($itemName === '' || $category === '' || $price <= 0) {
        redirect_admin('Item name, category, and price are required.', false, $section ?: 'software');
    }

    if ($id > 0) {
        $stmt = $conn->prepare('UPDATE software_store_items SET item_name = ?, category = ?, price = ?, description = ?, is_featured = ?, image_url = ? WHERE id = ?');
        $params = [$itemName, $category, $price, $description, $isFeatured, $imageUrl, $id];
    } else {
        $stmt = $conn->prepare('INSERT INTO software_store_items (item_name, category, price, description, is_featured, image_url) VALUES (?, ?, ?, ?, ?, ?)');
        $params = [$itemName, $category, $price, $description, $isFeatured, $imageUrl];
    }

    bind_param_values($stmt, $params);
    if (!$stmt->execute()) {
        $stmt->close();
        redirect_admin('Software item save failed. Please verify the values and try again.', false, $section ?: 'software');
    }

    $stmt->close();
    redirect_admin($id > 0 ? 'Software item updated successfully.' : 'Software item created successfully.', true, $section ?: 'software');
}

if ($action === 'item_delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        redirect_admin('A valid software item ID is required.', false, $section ?: 'software');
    }

    $stmt = $conn->prepare('DELETE FROM software_store_items WHERE id = ?');
    bind_param_values($stmt, [$id]);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        redirect_admin('Software item could not be deleted.', false, $section ?: 'software');
    }

    redirect_admin('Software item deleted successfully.', true, $section ?: 'software');
}

redirect_admin('The requested action is not supported.', false, $section ?: 'overview');
