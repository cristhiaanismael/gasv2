<?php
$mysqli = new mysqli("localhost", "root", "", "inggeinc_marvifet");
if ($mysqli->connect_error) { die("Connect Error: " . $mysqli->connect_error); }
$res = $mysqli->query("SHOW COLUMNS FROM movimientos");
while($row = $res->fetch_assoc()){
    echo "Field: {$row['Field']} | Type: {$row['Type']}\n";
}
echo "--- SAMPLE DATA ---\n";
$res = $mysqli->query("SELECT * FROM movimientos ORDER BY id_movimiento DESC LIMIT 1");
$row = $res->fetch_assoc();
print_r($row);
