<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$res = $conn->query("SHOW TABLES");
while($row = $res->fetch_array()){
    echo $row[0] . "\n";
}
$conn->close();
?>
