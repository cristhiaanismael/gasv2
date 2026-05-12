<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$res = $conn->query("SELECT id_lectura, id_departamento, periodo, fecha_register FROM lectura ORDER BY id_lectura DESC LIMIT 5");
while($row = $res->fetch_assoc()){
    print_r($row);
}

$conn->close();
?>
