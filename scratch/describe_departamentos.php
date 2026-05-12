<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inggeinc_marvifet";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$sql = "DESCRIBE departamentos";
$result = $conn->query($sql);

while($row = $result->fetch_assoc()) {
    print_r($row);
}
$conn->close();
?>
