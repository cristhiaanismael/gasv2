<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
$res = $conn->query("SHOW TABLES LIKE 'usuarios'");
if ($res->num_rows > 0) {
    echo "EXISTS\n";
    $cols = $conn->query("DESCRIBE usuarios");
    while($c = $cols->fetch_assoc()) print_r($c);
} else {
    echo "NOT EXISTS\n";
}
$conn->close();
?>
