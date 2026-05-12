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

    public function __construct()
    {
        $this->deptModel    = new DepartamentoModel();
        $this->lecturaModel = new LecturaModel();
        $this->movModel     = new MovimientoModel();
        $this->corteModel   = new CorteModel();
        $this->empresaModel = new DatosEmpresaModel();
        $this->precioModel  = new PrecioLitroModel();
        $this->edificioModel = new EdificioModel();
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
                $row['pdf_exists'] = file_exists(FCPATH . 'recibos/' . $filename);
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
            $historia   = $this->lecturaModel->getHistorialReciente($id_departamento, 12);

            // NUEVO: Obtener Muro de Comentarios Global (Últimos 12 meses consolidado)
            $notasGlobales = $this->lecturaModel->getAllNotas($id_departamento, 12);
            
            // 3. Sumar abonos del periodo (Lógica encapsulada en el Modelo)
            $abonosPeriodo = $this->movModel->getSumAbonosByRange(
                $id_departamento, 
                $periodRow['fecha_inicio'] ?? null, 
                $periodRow['fecha_fin'] ?? null
            );

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
                'historico'        => [], 
                'periodo'          => $periodo,
                'notas_globales'   => [], 
                'periodo_data'     => [
                    'fecha_inicio' => $periodRow['fecha_inicio'] ?? null,
                    'fecha_fin'    => $periodRow['fecha_fin'] ?? null
                ],
                'config'           => $tecnico,
                'lec_ant_sugerida' => $lecAntSugerida
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Historial] Error MVC getDetails: ' . $e->getMessage());
            return $this->fail('Error interno al cargar detalles: ' . $e->getMessage());
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

            // Si ya existe y no se forzó, servir el archivo directo
            if (file_exists($filePath) && !$force) {
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
            $json = $this->request->getJSON();
            $id_lectura = $json->id_lectura ?? null;
            if (!$id_lectura) return $this->fail('ID de lectura no proporcionado');

            // Soporte para FormData (para archivos) o JSON
            $id_lectura   = $this->request->getPost('id_lectura') ?? null;
            $lectura_fin  = $this->request->getPost('lectura_fin') ?? null;
            $cargos_add   = $this->request->getPost('cargos_add') ?? 0;
            $ajuste       = $this->request->getPost('ajuste') ?? 0;
            $nota         = $this->request->getPost('nota') ?? null;
            $total_manual = $this->request->getPost('total_a_pagar') ?? null;

            if (!$id_lectura) {
                $json = $this->request->getJSON();
                if ($json) {
                    $id_lectura   = $json->id_lectura ?? null;
                    $lectura_fin  = $json->lectura_fin ?? null;
                    $cargos_add   = $json->cargos_add ?? 0;
                    $ajuste       = $json->ajuste ?? 0;
                    $nota         = $json->nota ?? null;
                    $total_manual = $json->total_a_pagar ?? null;
                }
            }

            if (!$id_lectura) return $this->fail('ID de lectura es requerido');

            // 1. Obtener la lectura actual y configuración del edificio
            $lectura = $this->lecturaModel->find($id_lectura);
            if (!$lectura) return $this->failNotFound('Lectura no encontrada');

            $depto = $this->deptModel->getWithBuilding($lectura['id_departamento']);
            $config = $this->edificioModel->getConfiguracion($depto['id_edificio']);

            // 2. Procesar Archivo (Foto) si viene
            $fotoName = $lectura['foto']; // Mantener anterior por defecto
            $file = $this->request->getFile('foto');
            if ($file && $file->isValid() && !$file->hasMoved()) {
                $newName = $file->getRandomName();
                $uploadPath = FCPATH . 'uploads/lecturas';
                if (!is_dir($uploadPath)) mkdir($uploadPath, 0777, true);
                $file->move($uploadPath, $newName);
                $fotoName = $newName;
            }

            // 3. Re-calcular (MVC Style)
            $lec_ini = (float)$lectura['lectura_ini'];
            $lec_fin = ($lectura_fin !== null) ? (float)$lectura_fin : (float)$lectura['lectura_fin'];
            
            $m3 = max(0, $lec_fin - $lec_ini);
            $lt = $m3 * (float)$config['factor'];
            $montoGas = $lt * (float)$config['precioLitro'];

            $data = [
                'lectura_fin' => $lec_fin,
                'cargos_add'  => (float)$cargos_add,
                'ajuste'      => (float)$ajuste,
                'foto'        => $fotoName
            ];
            
            if ($nota !== null) {
                $data['nota'] = $nota;
            }

            $data['consumo_m3']      = $m3;
            $data['consumos_litros'] = $lt;
            $data['monto']           = $montoGas;
            
            // 3. Cálculo del Total del Periodo (Exclusivo de esta lectura)
            // No incluimos adeudos anteriores aquí para evitar duplicidad en movimientos.
            $data['total_a_pagar'] = $montoGas + (float)($config['cuotaAdmin'] ?? 0) + (float)$data['cargos_add'] + (float)$data['ajuste'];

            // 3. Transacción: Actualizar Lectura y Movimientos
            $this->lecturaModel->db->transStart();
            
            $this->lecturaModel->update($id_lectura, $data);
            
            // Sincronizar con Movimientos (Desglosado en múltiples registros según petición del usuario)
            // Lógica encapsulada en el Modelo para mantener el controlador limpio
            $this->movModel->syncReadingMovements(
                $id_lectura, 
                $lectura['id_departamento'], 
                $montoGas, 
                (float)($config['cuotaAdmin'] ?? 0), 
                (float)$data['cargos_add'], 
                (float)$data['ajuste'],
                $depto['num_edificio'] . " - " . ($lectura['periodo'] ?? 'Actual')
            );
            
            $this->lecturaModel->db->transComplete();

            if ($this->lecturaModel->db->transStatus() === false) {
                return $this->fail('Error al actualizar la base de datos');
            }

            return $this->respond([
                'status'  => 'success',
                'total'   => number_format($data['total_a_pagar'], 2, '.', ''),
                'lectura' => $this->lecturaModel->find($id_lectura)
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
            $json = $this->request->getJSON();
            $id_departamento = $json->id_departamento ?? null;
            $monto = $json->monto ?? 0;

            if (!$id_departamento || $monto <= 0) {
                return $this->fail('Datos de pago inválidos');
            }

            $data = [
                'id_departamento' => $id_departamento,
                'tipo'            => 'pago', // USAR 'pago' PARA CUMPLIR CON ENUM ('cargo','pago','ajuste')
                'monto'           => $monto,
                'descripcion'     => $json->descripcion ?? 'Pago registrado',
                'referencia_id'   => $json->id_lectura ?? null,
                'referencia_tipo' => $json->id_lectura ? 'lectura' : null,
                'fecha'           => date('Y-m-d H:i:s')
            ];

            if ($this->movModel->insert($data)) {
                return $this->respondCreated(['status' => 'success', 'message' => 'Pago registrado']);
            }
            
            // SI LLEGAMOS AQUÍ ES QUE HUBO UN ERROR EN EL INSERT (Probablemente por TIPO inválido)
            throw new \Exception("Error Fatal: No se pudo insertar el movimiento. Verifique el campo 'tipo'.");
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
            $json = $this->request->getJSON();
            $id_departamento = $json->id_departamento ?? null;
            $monto = $json->monto ?? 0;

            if (!$id_departamento || $monto == 0) {
                return $this->fail('Monto de ajuste inválido');
            }

            $esRecargo = ($monto > 0);
            $data = [
                'id_departamento' => $id_departamento,
                'tipo'            => $esRecargo ? 'cargo' : 'ajuste', // cargo(+) aumenta deuda | ajuste(-) disminuye
                'monto'           => abs($monto),
                'descripcion'     => $json->descripcion ?? ($esRecargo ? 'Ajuste manual (Recargo)' : 'Ajuste manual (Rebaja)'),
                'fecha'           => date('Y-m-d H:i:s')
            ];

            if ($this->movModel->insert($data)) {
                return $this->respondCreated(['status' => 'success', 'message' => 'Ajuste registrado']);
            }
            
            throw new \Exception("Error Fatal: No se pudo insertar el ajuste. Verifique el campo 'tipo'.");
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function getMovimientos($id_departamento)
    {
        try {
            $movs = $this->movModel->getMovimientosConPeriodo($id_departamento);
                                  
            $totalCargos = 0;
            $totalAbonos = 0;
            foreach ($movs as $m) {
                if ($m['tipo'] == 'cargo') $totalCargos += (float)$m['monto'];
                else $totalAbonos += (float)$m['monto'];
            }

            return $this->respond([
                'totalCargos' => $totalCargos,
                'totalAbonos' => $totalAbonos,
                'saldoNeto'   => $totalCargos - $totalAbonos,
                'movimientos' => $movs
            ]);
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

            // Seguridad: Solo permitir borrar pagos o ajustes. 
            // Los cargos (consumo) están ligados a la lectura y no deben borrarse solos.
            if ($mov['tipo'] === 'cargo') {
                return $this->fail('No se permite eliminar cargos de consumo directamente. Elimine o edite la lectura asociada.');
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
     * Búsqueda Omnidireccional (Restringida a Periodo Actual)
     */
    public function buscar()
    {
        $query = $this->request->getGet('q');
        if (!$query) return $this->respond([]);

        // Obtener Periodo Activo usando el modelo centralizado
        $periodoRow = $this->corteModel->getActiveFullRow();
        
        $periodo = $periodoRow['periodo'] ?? date('F Y');
        $fechas = [
            'inicio' => $periodoRow['fecha_inicio'] ?? date('Y-m-01'), 
            'fin' => $periodoRow['fecha_fin'] ?? date('Y-m-t')
        ];

        $results = $this->lecturaModel->searchOmni($query, $periodo, $fechas);

        return $this->respond($results);
    }
}
