<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$val = '201';
$res = $conn->query("SELECT d.id_departamento, d.num_departamento, e.num_edificio, e.id_edificio 
                    FROM departamentos d 
                    JOIN edificios e ON d.id_edificio = e.id_edificio 
                    WHERE d.num_departamento = '$val'");

while($row = $res->fetch_assoc()){
    $did = $row['id_departamento'];
    $lec = $conn->query("SELECT lectura_fin, id_lectura FROM lectura WHERE id_departamento = $did ORDER BY id_lectura DESC LIMIT 1")->fetch_assoc();
    echo "Edificio: " . $row['num_edificio'] . " Depto: " . $row['num_departamento'] . " -> Last Lec: " . ($lec['lectura_fin'] ?? 'N/A') . " (LecID: ".($lec['id_lectura'] ?? 'N/A').")\n";
}
$conn->close();
?>
