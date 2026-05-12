<?php
// require 'apis_marvi/public/index.php';
// Mock logic of Departamentos::info
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
$id = 126;

$db_res = $conn->query("SELECT lectura_fin, id_lectura FROM lectura WHERE id_departamento = $id ORDER BY id_lectura DESC LIMIT 1");
$row = $db_res->fetch_assoc();
echo "Ultima Lectura Calc: " . ($row['lectura_fin'] ?? 'N/A') . " (ID: ".($row['id_lectura'] ?? 'N/A').")\n";
$conn->close();
?>
