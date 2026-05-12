<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
// Buscar movimientos para lectura 80928
$res = $conn->query("SELECT * FROM movimientos WHERE referencia_id = 80928 AND referencia_tipo = 'lectura'");
while($row = $res->fetch_assoc()){
    print_r($row);
}
$conn->close();
?>
