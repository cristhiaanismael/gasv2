<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
$res = $conn->query("SELECT * FROM cortes ORDER BY id_corte DESC LIMIT 5");
while($row = $res->fetch_assoc()){
    print_r($row);
}
$conn->close();
?>
