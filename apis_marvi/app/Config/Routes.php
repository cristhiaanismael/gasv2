<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('hola', 'Home::getHola');
$routes->get('api/edificios', 'Edificios::index');
$routes->post('api/edificios/save', 'Edificios::save');
$routes->post('api/edificios/reorder', 'Edificios::reorder');
$routes->delete('api/edificios/(:num)', 'Edificios::delete/$1');

$routes->get('api/edificio/(:num)/config', 'Edificios::getConfig/$1');
$routes->post('api/edificio/(:num)/config', 'Edificios::saveConfig/$1');
$routes->get('api/edificio/(:num)/history', 'Edificios::getConfigHistory/$1');
$routes->get('api/departamento/(:num)', 'Departamentos::info/$1');
$routes->get('api/edificio/(:num)/departamentos', 'Departamentos::listByBuilding/$1');
$routes->post('api/departamentos/save', 'Departamentos::save');
$routes->post('api/departamentos/migrate', 'Departamentos::migrate');
$routes->delete('api/departamentos/(:num)', 'Departamentos::delete/$1');

$routes->post('api/clientes/save', 'Clientes::save');

$routes->get('api/config/periodos', 'Configuracion::getPeriodos');
$routes->get('api/config/periodo-activo', 'Configuracion::getPeriodoActivo');
$routes->post('api/config/periodo', 'Configuracion::addPeriodo');
$routes->post('api/config/edificio', 'Configuracion::addEdificio');
$routes->post('api/config/departamentos/bulk', 'Configuracion::massAddDepartamentos');

// Módulo de Recibos
$routes->get('recibos', 'Recibos::index');
$routes->get('recibos/generar', 'Recibos::generarPDF');

// Módulo de Historial
$routes->get('api/historial/lecturas', 'Historial::getLecturasConDetalle');
$routes->post('api/historial/enviar-custom-email', 'Historial::enviarCustomEmail');
$routes->get('api/historial/email-template', 'Historial::getEmailTemplate');

// Módulo Configuracion Cobranza
$routes->get('api/configuracion-cobranza/get-template', 'ConfiguracionCobranza::getTemplate');
$routes->post('api/configuracion-cobranza/save-template', 'ConfiguracionCobranza::saveTemplate');
$routes->get('api/configuracion-cobranza/get-options', 'ConfiguracionCobranza::getOptions');

// Lecturas
$routes->post('api/lectura', 'Lecturas::registrar');
$routes->get('api/lectura/ultima/(:num)', 'Lecturas::ultima/$1');
$routes->get('api/lecturas/progreso', 'Lecturas::progreso');
$routes->get('api/lecturas/progreso/edificio/(:num)', 'Lecturas::detalleProgreso/$1');

// Historial
$routes->get('api/historial/edificio/(:num)', 'Historial::getList/$1');
$routes->get('api/historial/edificio-anterior/(:num)', 'Historial::getPreviousList/$1');
$routes->get('api/historial/detalle/(:num)', 'Historial::getDetails/$1');
$routes->get('api/historial/breakdown-saldo/(:num)', 'Historial::getBreakdownSaldo/$1');
$routes->get('api/historial/breakdown-recibo-ant/(:num)', 'Historial::getBreakdownReciboAnt/$1');
$routes->post('api/historial/actualizar-lectura', 'Historial::updateReading');
$routes->post('api/historial/registrar-pago', 'Historial::registerPayment');
$routes->post('api/historial/registrar-ajuste', 'Historial::registerAdjustment');
$routes->post('api/historial/add-nota', 'Historial::addNota');
$routes->post('api/historial/delete-nota', 'Historial::deleteNota');
$routes->get('api/historial/sidebar-history/(:num)', 'Historial::getSidebarHistory/$1');
$routes->get('api/historial/sidebar-notes/(:num)', 'Historial::getSidebarNotes/$1');
$routes->get('api/historial/movimientos/(:num)', 'Historial::getMovimientos/$1');
$routes->get('api/historial/pdf/(:num)', 'Historial::generarPDF/$1');
$routes->get('api/historial/notificar/(:num)', 'Historial::enviarNotificacion/$1');
$routes->get('api/historial/descargar-zip/(:num)', 'Historial::descargarZIP/$1');
$routes->get('api/historial/buscar', 'Historial::buscar'); // TODO: Retirar en el futuro si ya no se usa
$routes->get('api/historial/omnisearch', 'Historial::omnisearch');
$routes->delete('api/historial/movimiento/(:num)', 'Historial::deleteMovimiento/$1');

// Auth
$routes->post('api/login', 'Auth::login');
$routes->get('api/logout', 'Auth::logout');

