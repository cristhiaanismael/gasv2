<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inggeinc_marvifet";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$sql = "SHOW TABLES";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  while($row = $result->fetch_assoc()) {
    echo "Table: " . $row["Tables_in_inggeinc_marvifet"] . "\n";
  }
} else {
  echo "0 results";
}
$conn->close();
?>
