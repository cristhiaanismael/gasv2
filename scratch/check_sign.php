<?php
$mysqli = new mysqli("localhost", "root", "", "inggeinc_marvifet");
$res = $mysqli->query("SELECT * FROM movimientos WHERE monto < 0");
if($res->num_rows > 0) {
    echo "Found negative amounts\n";
} else {
    echo "No negative amounts found\n";
}
