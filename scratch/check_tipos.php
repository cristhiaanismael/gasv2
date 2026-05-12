<?php
$mysqli = new mysqli("localhost", "root", "", "inggeinc_marvifet");
$res = $mysqli->query("SELECT tipo, COUNT(*) as total FROM movimientos GROUP BY tipo");
while($row = $res->fetch_assoc()){
    echo "Tipo: '{$row['tipo']}' | Total: {$row['total']}\n";
}
