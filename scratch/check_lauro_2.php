<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
$id_edificio = 20; // LAURO-2
$depts = $conn->query("SELECT id_departamento, num_departamento FROM departamentos WHERE id_edificio = $id_edificio");
while($d = $depts->fetch_assoc()){
    $did = $d['id_departamento'];
    $lec = $conn->query("SELECT lectura_fin, id_lectura FROM lectura WHERE id_departamento = $did ORDER BY id_lectura DESC LIMIT 1")->fetch_assoc();
    echo "ID: $did -> " . $d['num_departamento'] . " -> Last Lec: " . ($lec['lectura_fin'] ?? 'N/A') . "\n";
}
$conn->close();
?>
