<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('hola', 'Home::getHola');
$routes->get('api/edificios', 'Edificios::index');
$routes->get('api/edificio/(:num)/config', 'Edificios::getConfig/$1');
$routes->get('api/departamento/(:num)', 'Departamentos::info/$1');
$routes->get('api/edificio/(:num)/departamentos', 'Departamentos::listByBuilding/$1');
$routes->get('api/config/periodos', 'Configuracion::getPeriodos');
$routes->post('api/config/periodo', 'Configuracion::addPeriodo');
$routes->post('api/config/edificio', 'Configuracion::addEdificio');
$routes->post('api/config/departamentos/bulk', 'Configuracion::massAddDepartamentos');

// Lecturas
$routes->post('api/lectura', 'Lecturas::registrar');
$routes->get('api/lectura/ultima/(:num)', 'Lecturas::ultima/$1');

// Historial
$routes->get('api/historial/edificio/(:num)', 'Historial::getList/$1');
$routes->get('api/historial/detalle/(:num)', 'Historial::getDetails/$1');
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
$routes->get('api/historial/buscar', 'Historial::buscar');
$routes->delete('api/historial/movimiento/(:num)', 'Historial::deleteMovimiento/$1');

// Auth
$routes->post('api/login', 'Auth::login');
$routes->get('api/logout', 'Auth::logout');

