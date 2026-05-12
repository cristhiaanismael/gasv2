<?php
$conn = new mysqli('localhost', 'root', '', 'inggeinc_marvifet');
$id = 131; // Depto 102
$lec = $conn->query("SELECT * FROM lectura WHERE id_departamento = $id ORDER BY id_lectura DESC LIMIT 3");
while($row = $lec->fetch_assoc()){
    print_r($row);
}
$conn->close();
?>
