<?php
// Mocking the environment barely enough to test the method
require_once 'apis_marvi/vendor/autoload.php';

// We need to define BASEPATH and other CI constants if we want to bootstrap correctly,
// but it's easier to just do a manual query to see if the data exists for the join.

$mysqli = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');

// 1. Same logic as controller
$periodoRow = $mysqli->query("SELECT periodo FROM cortes WHERE status = 1 ORDER BY id_corte DESC LIMIT 1")->fetch_assoc();
$periodoNombre = $periodoRow['periodo'];

echo "Periodo: $periodoNombre\n";

$sql = "SELECT d.id_departamento, l.id_lectura, l.total_a_pagar 
        FROM departamentos d
        LEFT JOIN lectura l ON d.id_departamento = l.id_departamento AND l.periodo = '$periodoNombre'
        WHERE d.id_edificio = 1";

$res = $mysqli->query($sql);
$count = 0;
while($row = $res->fetch_assoc()){
    if($row['id_lectura']) {
        echo "FOUND READING! Depto ID: " . $row['id_departamento'] . " | Total: " . $row['total_a_pagar'] . "\n";
        $count++;
    }
}
echo "Total Found: $count\n";
?>
