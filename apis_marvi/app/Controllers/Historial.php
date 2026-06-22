<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use Spipu\Html2Pdf\Html2Pdf;
use SendGrid\Mail\Mail as SendGridMail;
use SendGrid;

use App\Models\Departamentos as DepartamentoModel;
use App\Models\Lectura as LecturaModel;
use App\Models\Movimientos as MovimientoModel;
use App\Models\Cortes as CorteModel;
use App\Models\Datos_empresa as DatosEmpresaModel;
use App\Models\Precio_litros as PrecioLitroModel;
use App\Models\Edificios as EdificioModel;
use App\Models\Clientes as ClienteModel;

class Historial extends BaseController
{
    use ResponseTrait;

    protected $deptModel;
    protected $lecturaModel;
    protected $movModel;
    protected $corteModel;
    protected $empresaModel;
    protected $precioModel;
    protected $edificioModel;
    protected $clienteModel;

    public function __construct()
    {
        $this->deptModel    = new DepartamentoModel();
        $this->lecturaModel = new LecturaModel();
        $this->movModel     = new MovimientoModel();
        $this->corteModel   = new CorteModel();
        $this->empresaModel = new DatosEmpresaModel();
        $this->precioModel  = new PrecioLitroModel();
        $this->edificioModel = new EdificioModel();
        $this->clienteModel  = new ClienteModel();
    }

    /**
     * Obtiene el listado de lecturas para un edificio específico en el periodo actual.
     * GET api/historial/edificio/(:num)
     */
    public function getList($id_edificio)
    {
        try {
            $periodo = $this->corteModel->getActivePeriod();
            $result = $this->deptModel->getDeptosConLectura($id_edificio, $periodo);

            // Verificar si el PDF existe físicamente para cada departamento
            foreach ($result as &$row) {
                $filename = "recibo_gas_" . $row['id_departamento'] . "_" . str_replace(' ', '_', $periodo) . ".pdf";
                $filepath = FCPATH . 'recibos/' . $filename;
                clearstatcache(true, $filepath);
                $row['pdf_exists'] = file_exists($filepath);
            }

            return $this->respond([
                'periodo' => $periodo,
                'data'    => $result
            ]);
        } catch (\Exception $e) {
            log_message('error', '[Historial] Error getList: ' . $e->getMessage());
            return $this->fail($e->getMessage());
        }
    }

    /**
     * Obtiene el consumo M3 del periodo anterior de forma separada y optimizada.
     * GET api/historial/edificio-anterior/(:num)
     */
    public function getPreviousList($id_edificio)
    {
        try {
            $periodoRow        = $this->corteModel->getActiveFullRow();
            $prevPeriodo       = null;
            $fechaInicioActivo = null;

            if ($periodoRow) {
                $fechaInicioActivo = $periodoRow['fecha_inicio'];
                // BUG6 FIX: Query movida al Modelo (Cortes::getPeriodoAnterior)
                $prevRow     = $this->corteModel->getPeriodoAnterior($periodoRow['fecha_inicio']);
                $prevPeriodo = $prevRow['periodo'] ?? null;
            }

            $fechaInicio = $fechaInicioActivo ?: date('Y-m-d H:i:s');
            $result      = $this->deptModel->getPreviousDataByBuilding($id_edificio, $prevPeriodo, $fechaInicio);

            return $this->respond($result);
        } catch (\Exception $e) {
            log_message('error', '[Historial] Error getPreviousList: ' . $e->getMessage());
            return $this->fail($e->getMessage());
        }
    }

    /**
     * Obtiene los detalles de un departamento y su histórico de 12 meses.
     * GET api/historial/detalle/(:num)
     */
    public function getDetails($id_departamento)
    {
        try {
            // 1. Obtener Periodo Activo (Fila completa desde el Modelo)
            $periodRow = $this->corteModel->getActiveFullRow();
            $periodo   = $periodRow['periodo'] ?? '---';
            
            // 2. Cargar Datos desde Modelos (MVC Estricto)
            $depto      = $this->deptModel->getInfoCompleta($id_departamento);
            $lectura    = $this->lecturaModel->getLecturaByPeriodo($id_departamento, $periodo);
            $saldoTotal = $this->movModel->getSaldoTotal($id_departamento);
            // Nota: historial y notas se cargan via endpoints separados
            // (sidebar-history y sidebar-notes) para no bloquear la respuesta principal.
            
            // 3. Sumar abonos del periodo (Lógica encapsulada en el Modelo)
            $abonosPeriodo = $this->movModel->getSumAbonosByRange(
                $id_departamento, 
                $periodRow['fecha_inicio'] ?? null, 
                $periodRow['fecha_fin'] ?? null
            );

            // 3.5. Calcular Saldo Inicial Real utilizando el Modelo de Movimientos (MVC puro y estricto)
            $saldoInicial = $this->movModel->getSaldoInicialAntesDeFecha($id_departamento, $periodRow['fecha_inicio'] ?? null);

            // 3.6. Último abono para refrescar la tabla después de pagos
            $ultimoAbono = $this->movModel->getUltimoAbono($id_departamento);

            // 4. Configuración de Precios (Lógica de Modelo centralizada)
            $idEdificio = $depto['id_edificio'] ?? 0;
            $tecnico = $this->edificioModel->getConfiguracion($idEdificio);
            
            // 5. Lectura anterior sugerida (Encapsulada en el Modelo)
            $lecAntSugerida = $this->lecturaModel->getUltimaFin($id_departamento);

            // 6. Respuesta Estructurada
            return $this->respond([
                'depto'            => $depto,
                'lectura'          => $lectura,
                'saldo'            => (float)$saldoTotal,
                'saldo_inicial'    => (float)$saldoInicial,
                'abonos_periodo'   => (float)$abonosPeriodo,
                'historico'        => [], 
                'periodo'          => $periodo,
                'notas_globales'   => [], 
                'periodo_data'     => [
                    'fecha_inicio' => $periodRow['fecha_inicio'] ?? null,
                    'fecha_fin'    => $periodRow['fecha_fin'] ?? null
                ],
                'config'           => $tecnico,
                'lec_ant_sugerida' => $lecAntSugerida,
                'ultimo_abono'     => $ultimoAbono ? [
                    'monto' => $ultimoAbono['monto'],
                    'fecha' => $ultimoAbono['fecha']
                ] : null
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Historial] Error MVC getDetails: ' . $e->getMessage());
            return $this->fail($e->getMessage());
        }
    }

    /**
     * Obtiene el desglose de los últimos 10 movimientos para justificar el Saldo Anterior
     * GET api/historial/breakdown-saldo/(:num)
     */
    public function getBreakdownSaldo($id_departamento)
    {
        try {
            $periodRow = $this->corteModel->getActiveFullRow();
            $fechaInicio = $periodRow['fecha_inicio'] ?? date('Y-m-d H:i:s');
            
            $movs = $this->movModel->getBreakdownSaldoMovs($id_departamento, $fechaInicio);
                                   
            return $this->respond($movs);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * Obtiene el desglose del último recibo anterior (para la columna Recibo Ant.)
     * GET api/historial/breakdown-recibo-ant/(:num)
     */
    public function getBreakdownReciboAnt($id_departamento)
    {
        try {
            $periodoRow = $this->corteModel->getActiveFullRow();
            $prevRow = $periodoRow ? $this->corteModel->getPeriodoAnterior($periodoRow['fecha_inicio']) : null;
            $prevPeriodo = $prevRow['periodo'] ?? null;
            
            if (!$prevPeriodo) {
                return $this->respond([]);
            }

            // Buscar la lectura exacta de ese periodo, asegurando que sea de 2026 en adelante
            $lectura = $this->lecturaModel->where('id_departamento', $id_departamento)
                                          ->where('periodo', $prevPeriodo)
                                          ->where('fecha_register >=', '2026-01-01 00:00:00')
                                          ->first();
                                          
            if (!$lectura) {
                return $this->respond([]);
            }

            // Construir el desglose
            $desglose = [];
            
            // Consumo de gas
            $montoGas = (float)($lectura['monto'] ?? 0);
            if ($montoGas > 0) {
                $desglose[] = [
                    'descripcion' => 'Consumo de Gas (' . (float)($lectura['consumo_m3'] ?? 0) . ' m3)',
                    'monto' => $montoGas,
                    'tipo' => 'cargo'
                ];
            }
            
            // Cuota de administración
            $cuotaAdmin = (float)($lectura['cuota_admin'] ?? 0);
            if ($cuotaAdmin > 0) {
                $desglose[] = [
                    'descripcion' => 'Cuota de Administración',
                    'monto' => $cuotaAdmin,
                    'tipo' => 'cargo'
                ];
            }
            
            // Cargos adicionales
            $cargosAdd = (float)($lectura['cargos_add'] ?? 0);
            if ($cargosAdd > 0) {
                $desglose[] = [
                    'descripcion' => 'Cargos Adicionales',
                    'monto' => $cargosAdd,
                    'tipo' => 'cargo'
                ];
            }
            
            // Saldo pendiente que venía arrastrando ESE recibo
            // El total a pagar es monto + cuota_admin + cargos_add + saldo_pendiente
            $totalCobrado = (float)($lectura['total_a_pagar'] ?? 0);
            $sumaCargos = $montoGas + $cuotaAdmin + $cargosAdd;
            $saldoPendienteArrastrado = $totalCobrado - $sumaCargos;
            
            if (abs($saldoPendienteArrastrado) > 0.05) {
                $desglose[] = [
                    'descripcion' => 'Saldo Pendiente Anterior',
                    'monto' => $saldoPendienteArrastrado,
                    'tipo' => $saldoPendienteArrastrado > 0 ? 'cargo' : 'abono'
                ];
            }
            
            return $this->respond([
                'total' => $totalCobrado,
                'periodo' => $lectura['periodo'],
                'fecha' => $lectura['fecha_register'] ?? null,
                'desglose' => $desglose
            ]);
            
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * Genera, guarda y descarga el recibo en PDF.
     * GET api/historial/pdf/(:num)
     */
    public function generarPDF($id_departamento)
    {
        try {
            $periodo = $this->corteModel->getActivePeriod();
            $depto = $this->deptModel->find($id_departamento);
            $filename = "recibo_gas_" . ($depto['id_departamento'] ?? $id_departamento) . "_" . str_replace(' ', '_', $periodo) . ".pdf";
            $filePath = FCPATH . 'recibos/' . $filename;

            // Lógica de Regeneración (Borrado físico)
            $force = $this->request->getGet('force') == '1';
            if ($force && file_exists($filePath)) {
                unlink($filePath);
                log_message('info', "[Historial] PDF Regenerado: $filename borrado para recreación.");
            }

            // Si ya existe y no se forzó, servir el archivo directo o JSON si es AJAX
            if (file_exists($filePath) && !$force) {
                if ($this->request->getGet('ajax') == '1') {
                    return $this->respond([
                        'status' => 'success',
                        'message' => 'Recibo ya existía y está listo',
                        'pdf_url' => base_url("api/historial/pdf/$id_departamento")
                    ]);
                }
                return $this->response->setHeader('Content-Type', 'application/pdf')
                                      ->setBody(file_get_contents($filePath));
            }

            // Si no existe, lo generamos
            $htmlContent = $this->prepararHtmlRecibo($id_departamento);
            $html2pdf = new Html2Pdf('P', 'A4', 'es', true, 'UTF-8', [10, 10, 10, 10]);
            $html2pdf->writeHTML($htmlContent);
            
            // Guardar en disco para persistencia
            $pdfContent = $html2pdf->output('', 'S');
            file_put_contents($filePath, $pdfContent);

            // ENVÍO DE EMAIL AUTOMÁTICO (A menos que se pida específicamente nosend=1)
            $noSend = $this->request->getGet('nosend') == '1';
            if (!$noSend) {
                $emailResult = $this->enviarPorEmail($id_departamento, $periodo, $pdfContent, $filename);
                if (!$emailResult['status']) {
                    log_message('error', "[Historial] ERROR DE ENVÍO AUTOMÁTICO: " . $emailResult['message']);
                    // Opcionalmente podrías pasar el error por header si quieres que el JS lo lea
                    $this->response->setHeader('X-Email-Error', bin2hex($emailResult['message']));
                } else {
                    log_message('info', "[Historial] Email automático enviado para $filename");
                }
            }

            // Si no se pidió vía AJAX, servimos el archivo directo
            if ($this->request->getGet('ajax') != '1') {
                return $this->response->setHeader('Content-Type', 'application/pdf')
                                      ->setBody($pdfContent);
            }

            // Si es AJAX, devolvemos JSON con el estado del email
            return $this->respond([
                'status' => 'success',
                'message' => 'Recibo generado correctamente',
                'email' => $emailResult ?? ['status' => true, 'message' => 'No se solicitó envío'],
                'pdf_url' => base_url("api/historial/pdf/$id_departamento")
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Historial] Error generarPDF: ' . $e->getMessage());
            return $this->fail($e->getMessage());
        }
    }

    /**
     * Endpoint para REENVIAR notificación (Usa el PDF en caché si existe)
     * GET api/historial/notificar/(:num)
     */
    public function enviarNotificacion($id_departamento)
    {
        try {
            $periodo = $this->corteModel->getActivePeriod();
            $pdfContent = $this->obtenerPdfString($id_departamento); // Usa cache automáticamente
            $depto = $this->deptModel->find($id_departamento);
            $filename = "recibo_gas_" . ($depto['id_departamento'] ?? $id_departamento) . "_" . str_replace(' ', '_', $periodo) . ".pdf";

            $result = $this->enviarPorEmail($id_departamento, $periodo, $pdfContent, $filename);

            if (!$result['status']) {
                log_message('error', 'FALLO ENVÍO EMAIL: ' . $result['message']);
                return $this->fail($result['message']);
            }

            return $this->respond($result);

        } catch (\Exception $e) {
            log_message('error', '[Historial] Error enviarNotificacion: ' . $e->getMessage());
            return $this->fail($e->getMessage());
        }
    }
    
    /**
     * POST api/historial/actualizar-lectura
     */
    public function updateReading()
    {
        try {
            // Soporte unificado: FormData primero (cuando viene con foto), luego JSON puro
            $id_lectura  = $this->request->getPost('id_lectura');
            $lectura_fin = $this->request->getPost('lectura_fin');
            $cargos_add  = $this->request->getPost('cargos_add') ?? 0;
            $ajuste      = $this->request->getPost('ajuste') ?? 0;
            $nota        = $this->request->getPost('nota');

            if (!$id_lectura) {
                $json = $this->request->getJSON();
                if ($json) {
                    $id_lectura  = $json->id_lectura ?? null;
                    $lectura_fin = $json->lectura_fin ?? null;
                    $cargos_add  = $json->cargos_add ?? 0;
                    $ajuste      = $json->ajuste ?? 0;
                    $nota        = $json->nota ?? null;
                }
            }

            if (!$id_lectura) return $this->fail('ID de lectura es requerido');

            // 1. Obtener datos de contexto via Modelos (responsabilidad del Controlador: orquestar)
            $lectura = $this->lecturaModel->find($id_lectura);
            if (!$lectura) return $this->failNotFound('Lectura no encontrada');

            $depto  = $this->deptModel->getWithBuilding($lectura['id_departamento']);
            $config = $this->edificioModel->getConfiguracion($depto['id_edificio']);

            // 2. Procesar Foto (el manejo de archivos es responsabilidad del Controlador)
            $fotoName = $lectura['foto'];
            $file     = $this->request->getFile('foto');
            if ($file && $file->isValid() && !$file->hasMoved()) {
                $newName    = $file->getRandomName();
                $uploadPath = FCPATH . 'uploads/lecturas';
                if (!is_dir($uploadPath)) mkdir($uploadPath, 0777, true);
                $file->move($uploadPath, $newName);
                $fotoName = $newName;
            }

            $total_a_pagar = $this->request->getPost('total_a_pagar') ?? ($json->total_a_pagar ?? null);

            // 3. Delegar TODO el cálculo y la transacción al Modelo (BUG1 + BUG5 FIX)
            $payload = [
                'lectura_fin'   => $lectura_fin,
                'cargos_add'    => $cargos_add,
                'ajuste'        => $ajuste,
                'nota'          => $nota,
                'foto'          => $fotoName,
                'total_a_pagar' => $total_a_pagar
            ];

            $result = $this->lecturaModel->actualizarLectura($id_lectura, $lectura, $payload, $config, $this->movModel);

            return $this->respond([
                'status'  => 'success',
                'total'   => number_format($result['total_a_pagar'], 2, '.', ''),
                'lectura' => $result['lectura']
            ]);

        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * POST api/historial/registrar-pago
     */
    public function registerPayment()
    {
        try {
            $json            = $this->request->getJSON();
            $id_departamento = $json->id_departamento ?? null;
            $monto           = $json->monto ?? 0;
            $descripcion     = $json->descripcion ?? 'Pago registrado';
            $id_lectura      = $json->id_lectura ?? null;

            if (!$id_departamento || $monto <= 0) {
                return $this->fail('Datos de pago inválidos');
            }

            // BUG8 FIX: Lógica de negocio delegada al Modelo
            if ($this->movModel->registrarPago($id_departamento, $monto, $descripcion, $id_lectura)) {
                // Devolver el saldo actualizado para que el JS pueda refrescar la tabla sin otro fetch
                $nuevoSaldo = $this->movModel->getSaldoTotal($id_departamento);
                return $this->respondCreated([
                    'status'      => 'success',
                    'message'     => 'Pago registrado',
                    'saldo_total' => (float)$nuevoSaldo
                ]);
            }

            throw new \Exception('No se pudo insertar el movimiento de pago.');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * POST api/historial/registrar-ajuste
     */
    public function registerAdjustment()
    {
        try {
            $json            = $this->request->getJSON();
            $id_departamento = $json->id_departamento ?? null;
            $monto           = $json->monto ?? 0;
            $descripcion     = $json->descripcion ?? null;

            if (!$id_departamento || $monto == 0) {
                return $this->fail('Monto de ajuste inválido');
            }

            // BUG8 FIX: Lógica de negocio delegada al Modelo
            if ($this->movModel->registrarAjuste($id_departamento, $monto, $descripcion)) {
                // Devolver el saldo actualizado para que el JS pueda refrescar la tabla sin otro fetch
                $nuevoSaldo = $this->movModel->getSaldoTotal($id_departamento);
                return $this->respondCreated([
                    'status'      => 'success',
                    'message'     => 'Ajuste registrado',
                    'saldo_total' => (float)$nuevoSaldo
                ]);
            }

            throw new \Exception('No se pudo insertar el movimiento de ajuste.');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function getMovimientos($id_departamento)
    {
        try {
            // BUG7 FIX: Cálculo de totales delegado al Modelo (Movimientos::getTotalesMovimientos)
            return $this->respond(
                $this->movModel->getTotalesMovimientos($id_departamento)
            );
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * Helper centralizado para preparar el HTML del recibo
     */
    private function prepararHtmlRecibo($id_departamento)
    {
        $fila_empresa = $this->empresaModel->getInfo();
        $depto = $this->deptModel->getInfoCompleta($id_departamento);
        $periodo = $this->corteModel->getActivePeriod();
        $precioGas = $this->precioModel->getPrecioVigente($depto['id_edificio']);
        $lectura = $this->lecturaModel->getLecturaByPeriodo($id_departamento, $periodo);
        $saldoTotal = $this->movModel->getSaldoTotal($id_departamento);
        $historia = $this->lecturaModel->getHistorialReciente($id_departamento, 6);

        $vencido = false;
        $historialHtml = "";
        foreach ($historia as $idx => $h) {
            $p = $h['periodos'] ?? $h['periodo'];
            $saldoPen = (float)($h['saldo_pendiente'] ?? 0);
            
            // Lógica de Producción: Consumo Mes = monto + cuota_admin
            $consumoMes = (float)$h['monto'] + (float)($h['cuota_admin'] ?? 0);
            
            // Lógica de Producción: Pagado (Determinado indirectamente por el saldo pendiente)
            $totalOriginal = (float)($h['total_a_pagar'] ?? 0);
            $pagado = max(0, $totalOriginal - $saldoPen);
            
            // Highlight para el periodo actual (el primero de la lista desc)
            $bgColor = ($idx === 0) ? 'background-color: yellow' : '';
            
            // Mostramos total_a_pagar si es el actual, saldo_inicial si es histórico (si existe)
            $montoFinal = ($idx === 0) ? $totalOriginal : ($h['saldo_inicial'] ?? $totalOriginal);

            $historialHtml .= "<tr>
                <td style='font-size: 9px;'>{$p}</td>
                <td>".round($h['lectura_ini'], 2)."</td>
                <td>".round($h['lectura_fin'], 2)."</td>
                <td>".round($h['consumo_m3'], 3)."</td>
                <td>".round($h['consumos_litros'], 2)."</td>
                <td>$".number_format($consumoMes, 2)."</td>
                <td>$".number_format($pagado, 2)."</td>
                <td style='{$bgColor}'>$".number_format($montoFinal, 2)."</td>
            </tr>";

            // Lógica de Producción para Vencido: Si el periodo ANTERIOR (idx 1) no está liquidado
            if ($idx === 1) {
                $vencido = ($saldoPen > 0.05); 
            }
        }

        // Formatear fecha_limite con fallback robusto para evitar 01-01-1970
        $rawFecha = $lectura['fecha_limite'] ?? null;
        $ts = ($rawFecha && strtotime($rawFecha)) ? strtotime($rawFecha) : strtotime('+5 days');
        $fechaFormateada = date('d-m-Y', $ts);

        $data = [
            'folio' => $lectura['id_lectura'] ?? 'N/A',
            'cliente' => $depto,
            'lectura' => [
                'total_a_pagar' => (float)$lectura['total_a_pagar'] ?? 0,
                'periodo' => $lectura['periodo'] ?? $periodo,
                'fecha_limite' => $fechaFormateada,
                'lectura_ini' => $lectura['lectura_ini'] ?? 0,
                'lectura_fin' => $lectura['lectura_fin'] ?? 0,
                'consumos_litros' => $lectura['consumos_litros'] ?? 0,
                'cargos_add' => $lectura['cargos_add'] ?? 0,
                'cuota_admin' => $lectura['cuota_admin'] ?? 0
            ],
            'vencido' => $vencido,
            'ruta_img' => (!empty($lectura['foto']) ? $lectura['foto'] : 'default.jpg'),
            'adeudo' => $saldoTotal > 0 ? $saldoTotal : 0,
            'saldofavor' => $saldoTotal < 0 ? abs($saldoTotal) : 0,
            'total_real' => $saldoTotal > 0 ? $saldoTotal : 0, // Lo que realmente debe al momento
            'precio_gas' => $precioGas,
            'historial' => $historialHtml
        ];

        if (!defined('img')) define('img', FCPATH . 'img/');
        if (!defined('user_foto')) define('user_foto', FCPATH . 'uploads/lecturas/');

        return view('recibo_pdf', ['fila_empresa' => $fila_empresa, 'data' => $data]);
    }

    private function obtenerPdfString($id_departamento)
    {
        $periodo = $this->corteModel->getActivePeriod();
        $depto = $this->deptModel->find($id_departamento);
        $filename = "recibo_gas_" . ($depto['id_departamento'] ?? $id_departamento) . "_" . str_replace(' ', '_', $periodo) . ".pdf";
        $filePath = FCPATH . 'recibos/' . $filename;

        // Si ya existe en disco, lo tomamos de ahí (más rápido)
        if (file_exists($filePath)) {
            return file_get_contents($filePath);
        }

        // Si no existe, lo generamos y lo guardamos
        $htmlContent = $this->prepararHtmlRecibo($id_departamento);
        $html2pdf = new Html2Pdf('P', 'A4', 'es', true, 'UTF-8', [10, 10, 10, 10]);
        $html2pdf->writeHTML($htmlContent);
        $pdfContent = $html2pdf->output('', 'S');
        
        file_put_contents($filePath, $pdfContent);
        return $pdfContent;
    }

    private function enviarPorEmail($id_departamento, $periodo, $pdfContent, $filename)
    {
        try {
            $depto = $this->deptModel->getInfoCompleta($id_departamento);
            $destinatario = $depto['correo'] ?? null;
            
            if (!$destinatario || $destinatario == "0" || !filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
                return ['status' => false, 'message' => "El cliente no tiene un correo electrónico válido registrado."];
            }

            $email = new SendGridMail(); 
            $email->setFrom("recibos@marvifet.com.mx", "Marvifet");
            $email->setSubject("Recibo de Gas - Depto " . ($depto['num_departamento'] ?? "S/N") . " - " . $periodo);
            $email->addTo($destinatario, $depto['nombre'] ?? $destinatario);
            
            $msj = "Hola " . ($depto['nombre'] ?? 'cliente') . ",<br><br>Adjuntamos tu recibo de gas correspondiente al periodo <strong>" . $periodo . "</strong>.<br><br>Saludos,<br>Equipo Marvifet";
            $email->addContent("text/html", $msj);

            if (!empty($pdfContent)) {
                $attachment = base64_encode($pdfContent);
                $email->addAttachment($attachment, "application/pdf", $filename, "attachment");
            }

            $apiKey = getenv('SENDGRID_API_KEY');
            if (empty($apiKey)) {
                return ['status' => false, 'message' => "Configuración faltante: SENDGRID_API_KEY no definida en el archivo .env"];
            }

            $sendgrid = new SendGrid($apiKey);
            $response = $sendgrid->send($email);

            $sc = $response->statusCode();
            if ($sc >= 200 && $sc < 300) {
                return ['status' => true, 'message' => "Email enviado con éxito (Código $sc)"];
            } else {
                $body = json_decode($response->body(), true);
                $errorMsg = $body['errors'][0]['message'] ?? "Error desconocido en SendGrid";
                return ['status' => false, 'message' => "SendGrid rechazó el envío: $errorMsg (Código $sc)"];
            }
        } catch (\Exception $e) {
            log_message('error', 'Excepción Crítica SendGrid: ' . $e->getMessage());
            return ['status' => false, 'message' => "Fallo técnico en el motor de envío: " . $e->getMessage()];
        }
    }

    /**
     * GET api/historial/email-template
     * Obtiene y resuelve la plantilla de correo para un departamento en un periodo.
     */
    public function getEmailTemplate()
    {
        $id_departamento = $this->request->getGet('id_departamento');
        
        if (!$id_departamento) {
            return $this->fail('ID de departamento requerido');
        }

        // Obtener datos del departamento
        $depto = $this->deptModel->getInfoCompleta($id_departamento);
        if (!$depto) {
            return $this->fail('Departamento no encontrado');
        }

        $id_edificio = $depto['id_edificio'];

        // Obtener el periodo activo
        $activeRow = $this->corteModel->getActiveFullRow();
        $periodo_nombre = $activeRow ? $activeRow['periodo'] : 'Periodo Actual';

        // Obtener la lectura para ese periodo (si existe) para las etiquetas de totales
        $lectura = null;
        if ($activeRow) {
            $lectura = $this->lecturaModel->where('id_departamento', $id_departamento)
                                    ->where('periodo', $periodo_nombre)
                                    ->first();
        }

        // Obtener saldo actual del estado de cuenta
        $db = \Config\Database::connect();
        $estado = $db->table('estado_cuenta')->where('id_departamento', $id_departamento)->get()->getRowArray();
        $saldo_actual = $estado ? floatval($estado['saldo_actual']) : 0;

        // Cargar modelo de configuracion y obtener plantilla
        $configModel = new \App\Models\ConfiguracionCobranzaModel();
        $template = $configModel->getTemplateResolved($id_departamento, $id_edificio);

        if (!$template) {
            return $this->fail('No hay plantilla configurada.');
        }

        $asunto = $template['asunto'];
        $mensaje = $template['mensaje'];

        // Resolver etiquetas
        $nombre_titular = trim(($depto['nombre'] ?? '') . ' ' . ($depto['ape_pat'] ?? ''));
        if (!$nombre_titular) $nombre_titular = 'Cliente';

        $total_periodo = $lectura ? floatval($lectura['total_a_pagar'] ?? 0) : 0;

        // Obtener lectura anterior
        $prevRow = $activeRow ? $this->corteModel->getPeriodoAnterior($activeRow['fecha_inicio']) : null;
        $prevPeriodo = $prevRow['periodo'] ?? null;
        $lecturaAnt = null;
        if ($prevPeriodo) {
            $lecturaAnt = $this->lecturaModel->where('id_departamento', $id_departamento)
                                          ->where('periodo', $prevPeriodo)
                                          ->first();
        }
        $total_periodo_ant = $lecturaAnt ? floatval($lecturaAnt['total_a_pagar'] ?? 0) : 0;
        $saldo_cierre_ant = $saldo_actual - $total_periodo; // Saldo antes de los cargos de este mes

        // Diccionario de meses
        $meses = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];

        $mes_inicio = $activeRow ? $meses[date('m', strtotime($activeRow['fecha_inicio'] ?? date('Y-m-d')))] : '';
        $mes_fin = $activeRow ? $meses[date('m', strtotime($activeRow['fecha_fin'] ?? date('Y-m-d')))] : '';
        $mes_siguiente = $activeRow ? $meses[date('m', strtotime('+1 month', strtotime($activeRow['fecha_fin'] ?? date('Y-m-d'))))] : '';

        // Diccionario de reemplazo
        $tags = [
            '{{nombre_titular}}' => $nombre_titular,
            '{{edificio}}' => $depto['num_edificio'] ?? 'Edificio',
            '{{numero_departamento}}' => $depto['num_departamento'] ?? '0',
            '{{saldo_actual}}' => number_format($saldo_actual, 2),
            '{{total_periodo}}' => number_format($total_periodo, 2),
            '{{corte}}' => $activeRow ? ($activeRow['fecha_fin'] ?? '') : '',
            '{{mes_curso}}' => $periodo_nombre,
            '{{mes_inicio_periodo}}' => $mes_inicio,
            '{{mes_fin_periodo}}' => $mes_fin,
            '{{mes_siguiente}}' => $mes_siguiente,
            '{{total_periodo_ant}}' => number_format($total_periodo_ant, 2),
            '{{saldo_cierre_ant}}' => number_format($saldo_cierre_ant, 2)
        ];

        foreach ($tags as $tag => $value) {
            $asunto = str_replace($tag, $value, $asunto);
            $mensaje = str_replace($tag, $value, $mensaje);
        }

        return $this->respond([
            'status' => true,
            'data' => [
                'asunto' => $asunto,
                'mensaje' => $mensaje
            ]
        ]);
    }

    /**
     * POST api/historial/enviar-custom-email
     * Envía un correo personalizado con asunto, mensaje y adjunto del recibo.
     */
    public function enviarCustomEmail()
    {
        $id_departamento = $this->request->getPost('id_departamento');
        $tipo_envio = $this->request->getPost('tipo_envio');
        $custom_email = $this->request->getPost('custom_email');
        $subject = $this->request->getPost('subject');
        $message = $this->request->getPost('message');
        $adjuntar_recibo = $this->request->getPost('adjuntar_recibo') === '1';

        if (!$id_departamento || !$tipo_envio || !$subject || !$message) {
            return $this->fail('Faltan datos requeridos para el envío');
        }

        // Obtener el periodo activo para generar el PDF (y para el nombre del archivo)
        $activeRow = $this->corteModel->getActiveFullRow();
        if (!$activeRow) return $this->fail('No hay un periodo de corte activo');
        $periodo = $activeRow['periodo'];

        $pdfOutput = null;
        $filename = "recibo_gas_" . $id_departamento . "_" . str_replace(' ', '_', $periodo) . ".pdf";

        if ($adjuntar_recibo) {
            // 1. Generar PDF (lo sacamos en memoria)
            $pdfOutput = $this->obtenerPdfString($id_departamento);
            if (!$pdfOutput) {
                return $this->fail('No se pudo generar el documento PDF para adjuntar.');
            }

            $filepath = FCPATH . 'recibos/' . $filename;
            
            // Guardarlo físicamente por si no existía (actúa también como un "Generar PDF" implícito)
            file_put_contents($filepath, $pdfOutput);
            clearstatcache(true, $filepath);
        }

        // 2. Enviar el correo
        $resultadoEmail = $this->enviarEmailPersonalizado(
            $id_departamento, 
            $pdfOutput, 
            $filename, 
            $tipo_envio, 
            $custom_email, 
            $subject, 
            $message
        );

        if ($resultadoEmail['status']) {
            return $this->respond(['status' => true, 'message' => 'El correo ha sido enviado con éxito.']);
        } else {
            return $this->fail($resultadoEmail['message']);
        }
    }

    private function enviarEmailPersonalizado($id_departamento, $pdfContent, $filename, $tipo_envio, $custom_email, $subject, $messageText)
    {
        try {
            $depto = $this->deptModel->getInfoCompleta($id_departamento);
            $nombre = $depto['nombre'] ?? 'Cliente';
            
            $destinatarios = [];
            
            if ($tipo_envio === 'otro') {
                if (filter_var($custom_email, FILTER_VALIDATE_EMAIL)) {
                    $destinatarios[] = ['email' => $custom_email, 'name' => $nombre];
                }
            } else {
                $pEmail = $depto['correo'] ?? null;
                $sEmail = $depto['correo_2'] ?? null;
                
                $pValid = $pEmail && $pEmail !== '0' && filter_var($pEmail, FILTER_VALIDATE_EMAIL);
                $sValid = $sEmail && $sEmail !== '0' && filter_var($sEmail, FILTER_VALIDATE_EMAIL);
                
                if ($tipo_envio === 'primario' && $pValid) {
                    $destinatarios[] = ['email' => $pEmail, 'name' => $nombre];
                } else if ($tipo_envio === 'secundario' && $sValid) {
                    $destinatarios[] = ['email' => $sEmail, 'name' => $nombre];
                } else if ($tipo_envio === 'ambos') {
                    if ($pValid) $destinatarios[] = ['email' => $pEmail, 'name' => $nombre];
                    if ($sValid && $sEmail !== $pEmail) $destinatarios[] = ['email' => $sEmail, 'name' => $nombre];
                }
            }
            
            if (empty($destinatarios)) {
                return ['status' => false, 'message' => "No hay destinatarios válidos para el tipo de envío seleccionado."];
            }

            $email = new SendGridMail(); 
            $email->setFrom("recibos@marvifet.com.mx", "Marvifet");
            $email->setSubject($subject);
            
            foreach ($destinatarios as $dest) {
                $email->addTo($dest['email'], $dest['name']);
            }
            
            // Format message allowing HTML line breaks
            $msjHtml = nl2br(htmlspecialchars($messageText));
            $email->addContent("text/html", $msjHtml);

            if (!empty($pdfContent)) {
                $attachment = base64_encode($pdfContent);
                $email->addAttachment($attachment, "application/pdf", $filename, "attachment");
            }

            $apiKey = getenv('SENDGRID_API_KEY');
            if (empty($apiKey)) {
                return ['status' => false, 'message' => "Configuración faltante: SENDGRID_API_KEY no definida."];
            }

            $sendgrid = new SendGrid($apiKey);
            $response = $sendgrid->send($email);

            $sc = $response->statusCode();
            if ($sc >= 200 && $sc < 300) {
                return ['status' => true, 'message' => "Email enviado con éxito"];
            } else {
                $body = json_decode($response->body(), true);
                $errorMsg = $body['errors'][0]['message'] ?? "Error desconocido";
                return ['status' => false, 'message' => "SendGrid rechazó el envío: $errorMsg"];
            }

        } catch (\Exception $e) {
            log_message('error', 'Excepción Crítica SendGrid Personalizado: ' . $e->getMessage());
            return ['status' => false, 'message' => "Fallo técnico en el motor de envío: " . $e->getMessage()];
        }
    }

    /**
     * Añadir una nota (comentario) a una lectura.
     * POST api/historial/add-nota
     */
    public function addNota()
    {
        $json = $this->request->getJSON();
        $id_lectura = $json->id_lectura ?? null;
        $texto = $json->texto ?? '';

        if (!$id_lectura || !$texto) {
            return $this->fail('Datos incompletos');
        }

        if ($this->lecturaModel->addNota($id_lectura, $texto)) {
            return $this->respond([
                'status' => 'success',
                'message' => 'Comentario añadido'
            ]);
        }

        return $this->fail('No se pudo añadir el comentario');
    }

    /**
     * Eliminar un comentario específico por su índice.
     * POST api/historial/delete-nota
     */
    public function deleteNota()
    {
        $json = $this->request->getJSON();
        $id_lectura = $json->id_lectura ?? null;
        $index = $json->index ?? null;

        if (!$id_lectura || !is_numeric($index)) {
            return $this->fail('Datos incompletos');
        }

        if ($this->lecturaModel->deleteNota($id_lectura, $index)) {
            return $this->respond([
                'status' => 'success',
                'message' => 'Comentario eliminado'
            ]);
        }

        return $this->fail('No se pudo eliminar el comentario');
    }

    /**
     * Eliminar un movimiento financiero (Pago o Ajuste).
     * DELETE api/historial/movimiento/(:num)
     */
    public function deleteMovimiento($id_movimiento)
    {
        try {
            $mov = $this->movModel->find($id_movimiento);
            if (!$mov) return $this->failNotFound('Movimiento no encontrado');

            // Seguridad: No permitir borrar movimientos automáticos ligados a una lectura, excepto pagos manuales.
            if ($mov['tipo'] !== 'pago' && $mov['referencia_tipo'] === 'lectura') {
                return $this->fail('No se permite eliminar movimientos generados por una lectura. Elimine o edite la lectura asociada.');
            }

            if ($this->movModel->delete($id_movimiento)) {
                return $this->respondDeleted([
                    'status' => 'success',
                    'message' => 'Movimiento eliminado correctamente'
                ]);
            }

            return $this->fail('No se pudo eliminar el movimiento');

        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * Cargar solo el historial de 12 meses
     * GET api/historial/sidebar-history/(:num)
     */
    public function getSidebarHistory($id_departamento)
    {
        $historia = $this->lecturaModel->getHistorialReciente($id_departamento, 12);
        return $this->respond($historia);
    }


    /**
     * Cargar solo el muro de notas global
     * GET api/historial/sidebar-notes/(:num)
     */
    public function getSidebarNotes($id_departamento)
    {
        $notas = $this->lecturaModel->getAllNotas($id_departamento, 12);
        return $this->respond($notas);
    }

    /**
     * Genera un archivo ZIP con todos los PDFs del periodo para un edificio.
     * GET api/historial/descargar-zip/(:num)
     */
    public function descargarZIP($id_edificio)
    {
        try {
            $periodo = $this->corteModel->getActivePeriod();
            if (!$periodo) return $this->fail('No hay un periodo activo configurado.');

            $data = $this->lecturaModel->getPdfsByEdificio($id_edificio, $periodo);
            
            $missingReadings = [];
            $missingFiles = [];
            $filesToZip = [];

            foreach ($data as $row) {
                // 1. Verificar si tiene lectura
                if (!$row['id_lectura']) {
                    $missingReadings[] = $row['num_departamento'];
                    continue;
                }

                // 2. Verificar archivo físico
                // Usamos el mismo patrón de nombre que en generarPDF
                $filename = "recibo_gas_" . $row['num_departamento'] . "_" . str_replace(' ', '_', $periodo) . ".pdf";
                
                // NOTA: El sistema usa IDs en archivos físicamente por seguridad, 
                // pero lo normalizamos aquí para buscar el archivo correcto.
                // Re-calculamos el path real usando la lógica de IDs
                $physicalName = "recibo_gas_" . $row['id_departamento'] . "_" . str_replace(' ', '_', $periodo) . ".pdf";
                $filePath = FCPATH . 'recibos/' . $physicalName;

                if (file_exists($filePath)) {
                    $filesToZip[] = [
                        'path' => $filePath,
                        'name' => "Recibo_Depto_{$row['num_departamento']}.pdf"
                    ];
                } else {
                    $missingFiles[] = $row['num_departamento'];
                }
            }

            // Si no hay absolutamente nada que descargar
            if (empty($filesToZip)) {
                return $this->respond([
                    'status' => 'error',
                    'message' => 'No hay archivos generados para descargar en este edificio/periodo.',
                    'details' => [
                        'missing_readings' => $missingReadings,
                        'missing_files'    => $missingFiles
                    ]
                ]);
            }

            // 3. Crear el ZIP
            $zip = new \ZipArchive();
            $zipName = "Lote_Recibos_" . str_replace(' ', '_', $periodo) . ".zip";
            $zipPath = WRITEPATH . 'temp/' . $id_edificio . '_' . time() . '.zip';

            if (!is_dir(WRITEPATH . 'temp')) mkdir(WRITEPATH . 'temp', 0777, true);

            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                foreach ($filesToZip as $f) {
                    $zip->addFile($f['path'], $f['name']);
                }
                $zip->close();

                if (!file_exists($zipPath)) throw new \Exception('El archivo ZIP no se creó correctamente.');

                $content = file_get_contents($zipPath);
                unlink($zipPath); // Limpieza inmediata

                $response = $this->response
                    ->setHeader('Content-Type', 'application/zip')
                    ->setHeader('Content-Disposition', 'attachment; filename="' . $zipName . '"')
                    // Envío de reporte de faltantes vía Header personalizado (Base64 para evitar problemas de caracteres)
                    ->setHeader('X-Diagnosis-Report', base64_encode(json_encode([
                        'missing_readings' => $missingReadings,
                        'missing_files'    => $missingFiles
                    ])));

                return $response->setBody($content);
            }

            return $this->fail('Fallo interno al crear el contenedor ZIP.');
        } catch (\Exception $e) {
            log_message('error', '[Historial::descargarZIP] ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }


    /**
     * Endpoint para OmniSearch
     * Recibe query "q" y "filters" (separados por coma).
     * Dependiendo de los filtros elegidos, llama a los modelos correspondientes.
     * GET api/historial/omnisearch?q=cadena&filters=edificio,cliente
     */
    public function omnisearch()
    {
        try {
            $query = $this->request->getGet('q');
            $filtersParam = $this->request->getGet('filters');

            if (empty($query) || strlen(trim($query)) < 3) {
                return $this->fail('La cadena de búsqueda debe tener al menos 3 caracteres.');
            }

            $filters = $filtersParam ? explode(',', $filtersParam) : [];
            $activeRow = $this->corteModel->getActiveFullRow();
            $periodo = $activeRow['periodo'] ?? '---';
            $prevRow = $this->corteModel->getPeriodoAnterior($activeRow['fecha_inicio'] ?? null);
            $periodoAnterior = $prevRow['periodo'] ?? null;

            $ids_departamentos = [];

            // Recorrer las opciones enviadas y llamar al método correspondiente del Modelo
            foreach ($filters as $filter) {
                switch (trim($filter)) {
                    case 'edificio':
                        // 1. Encontrar los edificios que coinciden
                        $edificios = $this->edificioModel->searchEdificios($query);
                        $ids_edificios = array_column($edificios, 'id_edificio');
                        
                        // 2. Extraer solo los IDs de sus departamentos
                        if (!empty($ids_edificios)) {
                            $deptos_ids = $this->deptModel->getIdsByEdificios($ids_edificios);
                            $ids_departamentos = array_merge($ids_departamentos, $deptos_ids);
                        }
                        break;

                    case 'departamento':
                        // Encontrar departamentos que coinciden con el número
                        $deptos_ids = $this->deptModel->searchIdsByNumDepartamento($query);
                        if (!empty($deptos_ids)) {
                            $ids_departamentos = array_merge($ids_departamentos, $deptos_ids);
                        }
                        break;

                    case 'cliente':
                        // 1. Encontrar los clientes que coinciden
                        $clientes = $this->clienteModel->searchClientes($query);
                        $ids_clientes = array_column($clientes, 'id_cliente');
                        
                        // 2. Extraer solo los IDs de sus departamentos
                        if (!empty($ids_clientes)) {
                            $deptos_ids = $this->deptModel->getIdsByClientes($ids_clientes);
                            $ids_departamentos = array_merge($ids_departamentos, $deptos_ids);
                        }
                        break;

                    case 'correo':
                        // 1. Encontrar los clientes que coinciden
                        $clientes = $this->clienteModel->searchCorreos($query);
                        $ids_clientes = array_column($clientes, 'id_cliente');
                        
                        // 2. Extraer solo los IDs de sus departamentos
                        if (!empty($ids_clientes)) {
                            $deptos_ids = $this->deptModel->getIdsByClientes($ids_clientes);
                            $ids_departamentos = array_merge($ids_departamentos, $deptos_ids);
                        }
                        break;

                    case 'lt_ant':
                        $lecturas = $this->lecturaModel->searchByLecturaIni($query, $periodo);
                        $ids_lecturas = array_column($lecturas, 'id_departamento');
                        if (!empty($ids_lecturas)) {
                            $ids_departamentos = array_merge($ids_departamentos, $ids_lecturas);
                        }
                        break;

                    case 'recibo_ant':
                        if ($periodoAnterior) {
                            $lecturas = $this->lecturaModel->searchByTotalPeriodo($query, $periodoAnterior);
                            $ids_lecturas = array_column($lecturas, 'id_departamento');
                            if (!empty($ids_lecturas)) {
                                $ids_departamentos = array_merge($ids_departamentos, $ids_lecturas);
                            }
                        }
                        break;

                    case 'saldo_ant':
                        if (!empty($activeRow['fecha_inicio'])) {
                            $movs = $this->movModel->searchBySaldoAnt($query, $activeRow['fecha_inicio']);
                            $ids_movs = array_column($movs, 'id_departamento');
                            if (!empty($ids_movs)) {
                                $ids_departamentos = array_merge($ids_departamentos, $ids_movs);
                            }
                        }
                        break;

                    case 'lt':
                        $lecturas = $this->lecturaModel->searchByLecturaFin($query, $periodo);
                        $ids_lecturas = array_column($lecturas, 'id_departamento');
                        if (!empty($ids_lecturas)) {
                            $ids_departamentos = array_merge($ids_departamentos, $ids_lecturas);
                        }
                        break;

                    case 'total_periodo':
                        $lecturas = $this->lecturaModel->searchByTotalPeriodo($query, $periodo);
                        $ids_lecturas = array_column($lecturas, 'id_departamento');
                        if (!empty($ids_lecturas)) {
                            $ids_departamentos = array_merge($ids_departamentos, $ids_lecturas);
                        }
                        break;

                    case 'saldo_actual':
                        $movs = $this->movModel->searchBySaldoActual($query);
                        $ids_movs = array_column($movs, 'id_departamento');
                        if (!empty($ids_movs)) {
                            $ids_departamentos = array_merge($ids_departamentos, $ids_movs);
                        }
                        break;

                    case 'adeudos':
                        $movs = $this->movModel->searchByAdeudos($query);
                        $ids_movs = array_column($movs, 'id_departamento');
                        if (!empty($ids_movs)) {
                            $ids_departamentos = array_merge($ids_departamentos, $ids_movs);
                        }
                        break;

                    case 'saldo_favor':
                        $movs = $this->movModel->searchBySaldoFavor($query);
                        $ids_movs = array_column($movs, 'id_departamento');
                        if (!empty($ids_movs)) {
                            $ids_departamentos = array_merge($ids_departamentos, $ids_movs);
                        }
                        break;

                    case 'abonos':
                        $movs = $this->movModel->searchByAbonos($query);
                        $ids_movs = array_column($movs, 'id_departamento');
                        if (!empty($ids_movs)) {
                            $ids_departamentos = array_merge($ids_departamentos, $ids_movs);
                        }
                        break;
                }
            }

            // Quitar duplicados de IDs (por si un depto hizo match por edificio y cliente al mismo tiempo)
            $ids_departamentos = array_unique($ids_departamentos);
            $results = [];

            // Ejecutar la consulta maestra pesada una ÚNICA vez
            if (!empty($ids_departamentos)) {
                $results = $this->deptModel->getDeptosConLecturaByIds($ids_departamentos, $periodo);

                // Verificar si el PDF existe físicamente para cada departamento
                foreach ($results as &$row) {
                    $filename = "recibo_gas_" . $row['id_departamento'] . "_" . str_replace(' ', '_', $periodo) . ".pdf";
                    $filepath = FCPATH . 'recibos/' . $filename;
                    clearstatcache(true, $filepath);
                    $row['pdf_exists'] = file_exists($filepath);
                }
            }

            return $this->respond([
                'status'  => true,
                'periodo' => $periodo,
                'data'    => $results
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Historial] Error omnisearch: ' . $e->getMessage());
            return $this->fail('Error interno al realizar la búsqueda: ' . $e->getMessage());
        }
    }
}
