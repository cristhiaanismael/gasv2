<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'inggeinc_marvifet';

echo "Testing connection to $host with user $user...\n";

$conn = @new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}

echo "Connected successfully to MySQL server.\n";

if ($conn->select_db($db)) {
    echo "Database '$db' selected successfully.\n";
} else {
    echo "Database '$db' does not exist or access denied.\n";
    
    $result = $conn->query("SHOW DATABASES");
    echo "Available databases:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['Database'] . "\n";
    }
}

$conn->close();
?>
