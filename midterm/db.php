<?php
// db.php - Database connection using PHP Data Objects (PDO)

// 1. Database credentials
$host     = 'localhost';
$dbname   = 'campus_helpdesk_midterm';
$username = 'root';
$password = ''; // Default password in XAMPP is empty

// 2. Data Source Name (DSN) with UTF-8 character encoding
$dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

// 3. PDO configuration attributes
$options = [
    // Throw exceptions on SQL errors for structured try/catch handling
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    
    // Return query rows as associative arrays (column_name => value)
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    
    // Turn off prepared statement emulation to enforce native parameter binding
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // 4. Instantiate PDO connection
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // 5. Safely handle connection failures without revealing sensitive credentials
    die("Database connection failed: " . htmlspecialchars($e->getMessage()));
}