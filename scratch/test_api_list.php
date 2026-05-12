<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
// 1. Periodo actual
$periodo = $conn->query("SELECT * FROM cortes WHERE status = 1 ORDER BY id_corte DESC LIMIT 1")->fetch_assoc();
$periodoNombre = $periodo['periodo'];

// 2. Query similar al del controller
$sql = "SELECT d.id_departamento, d.num_departamento, l.id_lectura, l.total_a_pagar 
        FROM departamentos d
        LEFT JOIN lectura l ON d.id_departamento = l.id_departamento AND l.periodo = '$periodoNombre'
        WHERE d.id_edificio = 1";
$res = $conn->query($sql);
$facturado = 0;
while($row = $res->fetch_assoc()){
    if($row['id_lectura']) {
        echo "Depto: " . $row['num_departamento'] . " | Lec: " . $row['id_lectura'] . " | Total: " . $row['total_a_pagar'] . "\n";
        $facturado += $row['total_a_pagar'];
    }
}
echo "Total Facturado Calculado: " . $facturado . "\n";
$conn->close();
?>
