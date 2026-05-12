<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$res = $conn->query("SELECT * FROM cortes");
$data = [];
while($row = $res->fetch_assoc()) $data[] = $row;

echo json_encode($data, JSON_PRETTY_PRINT);
$conn->close();
