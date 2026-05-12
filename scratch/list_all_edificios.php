<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
$res = $conn->query("SELECT id_edificio, num_edificio FROM edificios ORDER BY num_edificio");
while($row = $res->fetch_assoc()){
    echo "ID: " . $row['id_edificio'] . " -> " . $row['num_edificio'] . "\n";
}
$conn->close();
?>
