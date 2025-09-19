<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Create an in-memory SQLite database for testing
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Read the schema and create tables
$schema = file_get_contents(__DIR__ . '/../schema.sql');

// SQLite uses a slightly different syntax for auto-incrementing keys.
// We'll replace the MySQL-specific syntax before creating the test tables.
$schema = str_replace('INT AUTO_INCREMENT PRIMARY KEY', 'INTEGER PRIMARY KEY AUTOINCREMENT', $schema);

$pdo->exec($schema);

// Store the test database connection in a global variable
// This is a simple way to make it accessible in our tests
$GLOBALS['test_pdo'] = $pdo;
