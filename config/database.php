<?php
/**
 * Database connection using MySQLi with prepared statements support.
 * Override credentials in config/database.local.php (not committed).
 */

$dbConfig = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'name' => 'internconnect_sl',
    'charset' => 'utf8mb4',
];

$localConfig = __DIR__ . '/database.local.php';
if (file_exists($localConfig)) {
    $dbConfig = array_merge($dbConfig, require $localConfig);
}

$mysqli = new mysqli(
    $dbConfig['host'],
    $dbConfig['user'],
    $dbConfig['pass'],
    $dbConfig['name']
);

if ($mysqli->connect_error) {
    die('Database connection failed: ' . htmlspecialchars($mysqli->connect_error));
}

$mysqli->set_charset($dbConfig['charset']);
