<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
$usuario = 'usu';
$password = 'root';

$res = $conn->query("SELECT * FROM usuarios WHERE usuario = '$usuario' AND password = '$password'");
if($row = $res->fetch_assoc()){
    echo "DB MATCH OK: ID " . $row['id_user'] . "\n";
} else {
    echo "DB MATCH FAILED\n";
    $all = $conn->query("SELECT * FROM usuarios");
    while($r = $all->fetch_assoc()){
        echo "Found in DB: [" . $r['usuario'] . "] / [" . $r['password'] . "]\n";
    }
}
$conn->close();
?>
