<?php
include '../includes/auth.php';
include '../config/db.php';
include '../lib/db_tools.php';

header('Content-Type: application/json');

$latest_radiation = db_fetch_one($conn, 'SELECT status FROM radiation_logs ORDER BY created_at DESC LIMIT 1');
$latest_power = db_fetch_one($conn, 'SELECT mode, battery_level FROM power_logs ORDER BY created_at DESC LIMIT 1');

$health = 100;

if ($latest_radiation) {
    if ($latest_radiation['status'] === 'danger') {
        $health -= 30;
    } elseif ($latest_radiation['status'] === 'warning') {
        $health -= 15;
    }
}

if ($latest_power) {
    if ($latest_power['mode'] === 'critical') {
        $health -= 25;
    }

    if ((float) $latest_power['battery_level'] < 40) {
        $health -= 15;
    }

    if ((float) $latest_power['battery_level'] < 20) {
        $health -= 10;
    }
}

if ($health < 0) {
    $health = 0;
}

$avg_radiation = db_fetch_value(
    $conn,
    'SELECT AVG(radiation_level) AS avg_radiation FROM radiation_logs WHERE created_at >= NOW() - INTERVAL 1 DAY',
    'avg_radiation'
);

$avg_battery = db_fetch_value(
    $conn,
    'SELECT AVG(battery_level) AS avg_battery FROM power_logs WHERE created_at >= NOW() - INTERVAL 1 DAY',
    'avg_battery'
);

$events_24h = db_fetch_value(
    $conn,
    'SELECT COUNT(*) AS total_events FROM events WHERE created_at >= NOW() - INTERVAL 1 DAY',
    'total_events'
);

echo json_encode([
    'health' => $health,
    'avg_radiation' => $avg_radiation !== null ? round((float) $avg_radiation, 2) : null,
    'avg_battery' => $avg_battery !== null ? round((float) $avg_battery, 2) : null,
    'events_24h' => $events_24h !== null ? (int) $events_24h : 0,
]);
