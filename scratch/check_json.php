<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
$periodoRow = $conn->query("SELECT periodo FROM cortes WHERE status = 1 ORDER BY id_corte DESC LIMIT 1")->fetch_assoc();
$periodoNombre = $periodoRow['periodo'];

$sql = "SELECT d.id_departamento, l.id_lectura, l.total_a_pagar 
        FROM departamentos d
        LEFT JOIN lectura l ON d.id_departamento = l.id_departamento AND l.periodo = '$periodoNombre'
        WHERE d.id_edificio = 19"; // LAURO-1 is 19

$res = $conn->query($sql);
$data = [];
while($row = $res->fetch_assoc()){
    $data[] = $row;
}
echo json_encode(['periodo' => $periodoNombre, 'data' => $data]);
$conn->close();
?>
