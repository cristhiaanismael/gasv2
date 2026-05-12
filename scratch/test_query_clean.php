<?php
$mysqli = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');

$id_edificio = 1;
$res_e = $mysqli->query("SELECT id_edificio, num_edificio FROM edificios WHERE num_edificio = 'LAURO-1'")->fetch_assoc();
if($res_e) {
    $id_edificio = $res_e['id_edificio'];
    echo "EDIFICIO LAURO-1 ID: $id_edificio\n";
}

$periodoRow = $mysqli->query("SELECT periodo FROM cortes WHERE status = 1 ORDER BY id_corte DESC LIMIT 1")->fetch_assoc();
$periodoNombre = $periodoRow['periodo'];
echo "Periodo: $periodoNombre\n";

$sql = "SELECT d.id_departamento, d.num_departamento, l.id_lectura, l.total_a_pagar 
        FROM departamentos d
        LEFT JOIN lectura l ON d.id_departamento = l.id_departamento AND l.periodo = '$periodoNombre'
        WHERE d.id_edificio = $id_edificio";

$res = $mysqli->query($sql);
$count = 0;
while($row = $res->fetch_assoc()){
    if($row['id_lectura']) {
        echo "FOUND READING! Depto: " . $row['num_departamento'] . " | LecID: " . $row['id_lectura'] . " | Total: " . $row['total_a_pagar'] . "\n";
        $count++;
    }
}
echo "Total Found: $count\n";
?>
