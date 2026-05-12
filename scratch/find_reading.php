<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
$res = $conn->query("SELECT * FROM lectura WHERE lectura_fin LIKE '%2960.605%' OR lectura_ini LIKE '%2960.605%'");
while($row = $res->fetch_assoc()){
    print_r($row);
}
$conn->close();
?>
