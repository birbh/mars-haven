<?php
include '../includes/auth.php';
include '../config/db.php';
include '../lib/db_tools.php';

header('Content-Type: application/json');

$rows = db_fetch_all(
    $conn,
    'SELECT created_at, intensity FROM solar_storms ORDER BY created_at DESC LIMIT 12'
);
$rows = array_reverse($rows);

$labels = [];
$values = [];
$latest = null;

foreach ($rows as $row) {
    $labels[] = date('H:i', strtotime($row['created_at']));
    $values[] = (int) $row['intensity'];
    $latest = $row;
}

echo json_encode([
    'labels' => $labels,
    'values' => $values,
    'latest' => $latest ? [
        'intensity' => (int) $latest['intensity'],
        'description' => (string) ($latest['description'] ?? ''),
        'created_at' => (string) $latest['created_at'],
    ] : null,
]);
