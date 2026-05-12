<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
$res = $conn->query("SELECT * FROM cortes WHERE periodo LIKE '%26-02-2026%'");
while($row = $res->fetch_assoc()){
    print_r($row);
}
$conn->close();
?>
