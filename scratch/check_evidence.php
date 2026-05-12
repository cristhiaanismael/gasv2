<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$periodo = "26-03-2026 26-04-2026";
$res = $conn->query("SELECT id_departamento, nota FROM lectura WHERE periodo = '$periodo' AND (nota IS NOT NULL AND nota != '[]') LIMIT 5");
$data = [];
while($row = $res->fetch_assoc()) $data[] = $row;

echo "NOTAS ENCONTRADAS:\n";
echo json_encode($data, JSON_PRETTY_PRINT);

$res2 = $conn->query("SELECT m.* FROM movimientos m JOIN lectura l ON m.id_departamento = l.id_departamento WHERE l.periodo = '$periodo' AND m.tipo = 'abono' LIMIT 5");
$data2 = [];
while($row = $res2->fetch_assoc()) $data2[] = $row;

echo "\n\nABONOS ENCONTRADOS:\n";
echo json_encode($data2, JSON_PRETTY_PRINT);

$conn->close();
