<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
// 1. Periodo actual
$periodo = $conn->query("SELECT * FROM cortes WHERE status = 1 OR 1=1 ORDER BY status DESC, id_corte DESC LIMIT 1")->fetch_assoc();
echo "Periodo Detectado: " . $periodo['periodo'] . " (ID: " . $periodo['id_corte'] . ", Status: " . $periodo['status'] . ")\n";

// 2. Lectura para Depto 126 en ese periodo
$res = $conn->query("SELECT * FROM lectura WHERE id_departamento = 126 AND periodo = '" . $periodo['periodo'] . "'");
if($row = $res->fetch_assoc()){
    print_r($row);
} else {
    echo "No hay lectura para 126 en este periodo.\n";
    echo "Ultimas lecturas de 126:\n";
    $res2 = $conn->query("SELECT * FROM lectura WHERE id_departamento = 126 ORDER BY id_lectura DESC LIMIT 3");
    while($r2 = $res2->fetch_assoc()){
        echo "LecID: " . $r2['id_lectura'] . " | Periodo: " . $r2['periodo'] . " | Total: " . $r2['total_a_pagar'] . "\n";
    }
}
$conn->close();
?>
