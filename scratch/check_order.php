<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
$res = $conn->query("SELECT id_corte, periodo, fecha_register, fecha_inicio FROM cortes ORDER BY fecha_register DESC, id_corte DESC LIMIT 10");
$i = 1;
while($row = $res->fetch_assoc()){
    echo "$i. " . $row['periodo'] . " (Reg: " . $row['fecha_register'] . ", ID: " . $row['id_corte'] . ")\n";
    $i++;
}
$conn->close();
?>
