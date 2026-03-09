<?php
include '../includes/auth.php';
include '../config/db.php';
include '../lib/db_tools.php';

header('Content-Type: application/json');

$rows = db_fetch_all(
    $conn,
    'SELECT created_at, radiation_level, status FROM radiation_logs ORDER BY created_at DESC LIMIT 12'
);
$rows = array_reverse($rows);

$labels = [];
$values = [];
$latest = null;

foreach ($rows as $row) {
    $labels[] = date('H:i', strtotime($row['created_at']));
    $values[] = round((float) $row['radiation_level'], 2);
    $latest = $row;
}

echo json_encode([
    'labels' => $labels,
    'values' => $values,
    'latest' => $latest ? [
        'radiation_level' => round((float) $latest['radiation_level'], 2),
        'status' => (string) $latest['status'],
        'created_at' => (string) $latest['created_at'],
    ] : null,
]);
