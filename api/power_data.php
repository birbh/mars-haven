<?php
include '../includes/auth.php';
include '../config/db.php';
include '../lib/db_tools.php';

header('Content-Type: application/json');

$rows = db_fetch_all(
    $conn,
    'SELECT created_at, solar_output, battery_level, mode FROM power_logs ORDER BY created_at DESC LIMIT 12'
);
$rows = array_reverse($rows);

$labels = [];
$solar_output = [];
$battery_level = [];
$backup_status = [];
$latest = null;

foreach ($rows as $row) {
    $labels[] = date('H:i', strtotime($row['created_at']));
    $solar_output[] = (int) $row['solar_output'];
    $battery_level[] = (int) $row['battery_level'];

    // map backup status into a chartable value (0 or 100).
    $backup_status[] = $row['mode'] === 'critical' ? 100 : 0;
    $latest = $row;
}

echo json_encode([
    'labels' => $labels,
    'solar_output' => $solar_output,
    'battery_level' => $battery_level,
    'backup_status' => $backup_status,
    'latest' => $latest ? [
        'solar_output' => (int) $latest['solar_output'],
        'battery_level' => (int) $latest['battery_level'],
        'mode' => (string) $latest['mode'],
        'created_at' => (string) $latest['created_at'],
    ] : null,
]);
