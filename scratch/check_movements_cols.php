<?php
require_once 'apis_marvi/public/index.php';
$db = \Config\Database::connect();
$query = $db->query("SHOW COLUMNS FROM movimientos");
foreach ($query->getResult() as $row) {
    echo "Field: {$row->Field} | Type: {$row->Type}\n";
}
