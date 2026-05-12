<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
$res = $conn->query("SELECT * FROM usuarios");
while($row = $res->fetch_assoc()){
    print_r($row);
}
$conn->close();
?>
