<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
$corte = $conn->query("SELECT periodo FROM cortes WHERE id_corte = 160")->fetch_assoc()['periodo'];
echo "Corte 160: [" . $corte . "]\n";

$lectura = $conn->query("SELECT periodo FROM lectura WHERE id_lectura = 80928")->fetch_assoc()['periodo'];
echo "Lectura 80928: [" . $lectura . "]\n";

if ($corte === $lectura) {
    echo "LAS CADENAS SON IDENTICAS.\n";
} else {
    echo "LAS CADENAS SON DIFERENTES.\n";
    echo "HEX CORTE: " . bin2hex($corte) . "\n";
    echo "HEX LECTURA: " . bin2hex($lectura) . "\n";
}
$conn->close();
?>
