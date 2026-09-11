<?php

function get_user_by_id($conn, $userId)
{
    $stmt = $conn->prepare('SELECT id, full_name, email, phone, is_premium, status FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    return $user ?: null;
}

function get_user_bookings($conn, $userId)
{
    $stmt = $conn->prepare(
        'SELECT b.id, b.device_name, b.device_brand, b.device_model, rs.service_name, b.issue_description, b.preferred_date, b.status
         FROM bookings b
         LEFT JOIN repair_services rs ON rs.id = b.service_id
         WHERE b.user_id = ?
         ORDER BY b.created_at DESC'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $bookings = [];

    while ($row = $result->fetch_assoc()) {
        $device = trim($row['device_name'] . ' ' . $row['device_brand'] . ' ' . $row['device_model']);
        $bookings[] = [
            'id' => $row['id'],
            'device' => $device !== '' ? $device : 'Device',
            'service' => $row['service_name'] ?: 'General Repair',
            'issue' => $row['issue_description'] ?: 'No issue description provided',
            'date' => $row['preferred_date'] ?: 'TBD',
            'status' => $row['status'] ?: 'pending',
        ];
    }

    $stmt->close();

    return $bookings;
}

function get_user_stats($conn, $userId)
{
    $stmt = $conn->prepare(
        'SELECT
            COUNT(CASE WHEN status IN ("pending", "confirmed", "in_progress") THEN 1 END) AS active_bookings,
            COUNT(CASE WHEN status = "completed" THEN 1 END) AS completed_bookings,
            (SELECT COUNT(*) FROM premium_accounts WHERE user_id = ? AND status = "active") AS premium_count,
            (SELECT MIN(preferred_date) FROM bookings WHERE user_id = ? AND status IN ("pending", "confirmed", "in_progress") AND preferred_date >= CURDATE()) AS next_date,
            (SELECT device_name FROM bookings WHERE user_id = ? AND status IN ("pending", "confirmed", "in_progress") ORDER BY preferred_date ASC LIMIT 1) AS next_device,
            (SELECT rs.service_name FROM bookings b LEFT JOIN repair_services rs ON rs.id = b.service_id WHERE b.user_id = ? AND b.status IN ("pending", "confirmed", "in_progress") ORDER BY b.preferred_date ASC LIMIT 1) AS next_service
         FROM bookings
         WHERE user_id = ?'
    );

    $stmt->bind_param('iiiii', $userId, $userId, $userId, $userId, $userId);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $stats ?: [
        'active_bookings' => 0,
        'completed_bookings' => 0,
        'premium_count' => 0,
        'next_date' => null,
        'next_device' => null,
        'next_service' => null,
    ];
}

function get_pricing_groups($conn)
{
    $sql = "SELECT rs.category, rs.service_name, rp.price_label
            FROM repair_services rs
            INNER JOIN repair_pricing rp ON rp.service_id = rs.id
            WHERE rp.is_active = 1
            ORDER BY CASE rs.category
                WHEN 'computer' THEN 1
                WHEN 'phone' THEN 2
                WHEN 'tablet' THEN 3
                WHEN 'software' THEN 4
                ELSE 5
            END, rs.id ASC";

    $result = $conn->query($sql);
    $groups = [
        '💻 Computer / Laptop' => [],
        '📱 Mobile Phone' => [],
        '📲 Tablet' => [],
        '💾 Software' => [],
    ];

    while ($row = $result->fetch_assoc()) {
        $category = $row['category'];
        $label = match ($category) {
            'computer' => '💻 Computer / Laptop',
            'phone' => '📱 Mobile Phone',
            'tablet' => '📲 Tablet',
            'software' => '💾 Software',
            default => '📦 Other',
        };

        if (!isset($groups[$label])) {
            $groups[$label] = [];
        }

        $groups[$label][] = [
            'service_name' => $row['service_name'],
            'price_label' => $row['price_label'],
        ];
    }

    foreach ($groups as $label => $items) {
        if (empty($items)) {
            unset($groups[$label]);
        }
    }

    return $groups;
}

function get_premium_plans($conn)
{
    $result = $conn->query('SELECT plan_name, description, monthly_price, features FROM premium_plans WHERE is_active = 1 ORDER BY monthly_price ASC');
    $plans = [];

    while ($row = $result->fetch_assoc()) {
        $plans[] = [
            'plan_name' => $row['plan_name'],
            'description' => $row['description'],
            'monthly_price' => $row['monthly_price'],
            'features' => $row['features'],
        ];
    }

    return $plans;
}

function get_software_items($conn)
{
    $result = $conn->query('SELECT item_name, category, price, description FROM software_store_items ORDER BY created_at DESC');
    $items = [];

    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'item_name' => $row['item_name'],
            'category' => $row['category'],
            'price' => $row['price'],
            'description' => $row['description'],
        ];
    }

    return $items;
}

function get_user_payments($conn, $userId)
{
    $stmt = $conn->prepare(
        'SELECT pa.status, pp.plan_name, pp.monthly_price, pa.expires_at
         FROM premium_accounts pa
         JOIN premium_plans pp ON pp.id = pa.plan_id
         WHERE pa.user_id = ?
         ORDER BY pa.created_at DESC'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $payments = [];

    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }

    $stmt->close();

    return $payments;
}

function status_badge($status)
{
    $labelMap = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    $statusKey = $status ?? 'pending';
    $label = $labelMap[$statusKey] ?? ucfirst(str_replace('_', ' ', $statusKey));

    return '<span class="status-pill status-' . htmlspecialchars($statusKey, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
}
