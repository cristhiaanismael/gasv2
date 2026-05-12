<?php
$mysqli = new mysqli("localhost", "root", "", "inggeinc_marvifet");
$mysqli->query("UPDATE movimientos SET tipo = 'pago' WHERE id_movimiento = 196613");
if ($mysqli->affected_rows > 0) {
    echo "Fixed record 196613\n";
} else {
    echo "Record not found or already fixed\n";
}
