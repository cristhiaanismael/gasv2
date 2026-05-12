<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
$res = $conn->query("DESCRIBE clientes");
while($row = $res->fetch_assoc()){
    echo $row['Field'] . "\n";
}
$conn->close();
?>
