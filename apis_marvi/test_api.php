<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
$loader = require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/app/Config/Events.php';
$app = \Config\Services::codeigniter();
$app->initialize();

// Test the model directly
$model = new \App\Models\PeriodoModel();
$data = $model->orderBy('fecha_register', 'DESC')->findAll();

echo "Count: " . count($data) . "\n";
if (count($data) > 0) {
    print_r($data[0]);
}
?>
