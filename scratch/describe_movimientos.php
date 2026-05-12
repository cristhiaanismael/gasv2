<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$res = $conn->query("DESCRIBE movimientos");
while($row = $res->fetch_assoc()){
    print_r($row);
}
$conn->close();
?>
