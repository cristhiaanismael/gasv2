<?php
require_once 'apis_marvi/public/index.php';
$lecturaModel = new \App\Models\LecturaModel();
$corteModel = new \App\Models\CorteModel();
$periodo = $corteModel->getActivePeriod();

$id_depto = 1; // Change to an existing ID if known, or just list some
$lecturas = $lecturaModel->where('periodo', $periodo)->limit(5)->findAll();

echo "Periodo: $periodo\n";
foreach($lecturas as $l) {
    echo "ID Depto: {$l['id_departamento']} | Total a Pagar DB: {$l['total_a_pagar']}\n";
}
