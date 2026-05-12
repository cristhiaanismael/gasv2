<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
$periodo = "26-03-2026 26-04-2026";
$q = "hola"; // Simulación de búsqueda

$sql = "SELECT d.id_departamento FROM departamentos d 
        LEFT JOIN clientes c ON d.id_cliente = c.id_cliente
        LEFT JOIN lectura l ON d.id_departamento = l.id_departamento AND l.periodo = '$periodo'
        WHERE (c.nombre LIKE '%$q%' OR l.nota LIKE '%$q%')";

$res = $conn->query($sql);
$results = [];
while($row = $res->fetch_assoc()) $results[] = $row;

echo "RESULTADO SIMULADO API PARA '$q':\n";
echo json_encode($results, JSON_PRETTY_PRINT);
$conn->close();
