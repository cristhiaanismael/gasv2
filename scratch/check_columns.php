<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$res = $conn->query("SHOW COLUMNS FROM cortes");
while($row = $res->fetch_assoc()){
    print_r($row);
}

$res = $conn->query("SELECT * FROM cortes WHERE status = 1");
while($row = $res->fetch_assoc()){
    echo "Active Period: " . $row['periodo'] . " | Start: " . ($row['fecha_ini'] ?? $row['fecha_inicio'] ?? 'N/A') . " | End: " . ($row['fecha_fin'] ?? 'N/A') . "\n";
}

$conn->close();
?>
