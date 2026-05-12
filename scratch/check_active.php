<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
$res = $conn->query("SELECT id_corte, periodo, status FROM cortes WHERE status = 1");
while($row = $res->fetch_assoc()){
    print_r($row);
}
$conn->close();
?>
