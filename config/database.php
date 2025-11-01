<?php
// Database configuration
$db_config = [
    'host' => '12',
    'dbname' => 'repair shop', //RepairShopDB
    'username' => 'root',
    'password' => '',
    'port' => 3307,
    'charset' => 'utf8mb4'
];

try {
    // Create PDO connection 
    $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
    
    try {
        $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
    } catch (PDOException $e) {
        //to Unix socket 
        $socket_dsn = "mysql:unix_socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock;dbname={$db_config['dbname']};charset={$db_config['charset']}";
        $pdo = new PDO($socket_dsn, $db_config['username'], $db_config['password']);
    }
    
    // Set PDO attributes for better error handling and security
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}


function executeQuery($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query execution failed: " . $e->getMessage());
        return false;
    }
}


function hashPassword($password) {
    return hash('sha256', $password);
}


function verifyPassword($password, $hash) {
    return hash('sha256', $password) === $hash;
}


function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}


function generateTicketId() {
    return 'TKT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}
?>
