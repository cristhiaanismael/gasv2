<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$correctPeriod = '26-03-2026 26-04-2026';

$res = $conn->query("UPDATE lectura SET periodo = '$correctPeriod' WHERE periodo = '2026-04'");
echo "Updated " . $conn->affected_rows . " rows in lectura table.\n";

$conn->close();
?>
