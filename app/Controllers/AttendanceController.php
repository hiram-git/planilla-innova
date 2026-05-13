<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Middleware\AuthMiddleware;
use App\Models\Attendance;
use App\Models\AttendanceHeader;
use App\Models\AttendanceDetail;
use App\Models\AttendanceSyncLog;
use App\Models\AttendanceDevice;
use App\Models\AttendanceApiConfig;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\OvertimeApproval;
use App\Services\Attendance\Calculators\AttendanceCalculator;
use App\Services\Attendance\Calculators\AbsenceDetector;
use App\Services\Attendance\Calculators\WorkScheduleResolver;
use App\Services\Attendance\RecordsProcessor;
use Exception;

/**
 * Controlador para gestión de marcaciones de asistencias
 * Incluye integración con calculadores avanzados:
 * - AttendanceCalculator: Cálculos de horas trabajadas, tardanzas, etc.
 * - AbsenceDetector: Detección automática de ausencias
 */
class AttendanceController extends Controller
{
    private $attendanceModel;
    private $headerModel;
    private $detailModel;
    private $syncLogModel;
    private $deviceModel;
    private $employeeModel;
    private $recordModel;

    // Calculadores y Procesadores
    private $attendanceCalculator;
    private $absenceDetector;
    private $scheduleResolver;
    private $recordsProcessor;

    public function __construct()
    {
        $this->requireAuth();
        $this->attendanceModel = new Attendance();
        $this->headerModel = new AttendanceHeader();
        $this->detailModel = new AttendanceDetail();
        $this->syncLogModel = new AttendanceSyncLog();
        $this->deviceModel = new AttendanceDevice();
        $this->employeeModel = new Employee();
        $this->recordModel = new AttendanceRecord();

        // Inicializar calculadores y procesadores
        $this->attendanceCalculator = new AttendanceCalculator();
        $this->absenceDetector = new AbsenceDetector();
        $this->scheduleResolver = new WorkScheduleResolver();
        $this->recordsProcessor = new RecordsProcessor();
    }

    /**
     * Vista principal - Listado de marcaciones agrupadas por día (cabecera)
     * REFACTORIZADO: Usa AttendanceHeader en lugar de Attendance
     */
    public function index()
    {
        // Obtener parámetros de filtro
        $year = $_GET['year'] ?? date('Y');
        $month = $_GET['month'] ?? date('m');
        $deviceId = $_GET['device_id'] ?? null;
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;

        // Preparar filtros
        $filters = [];

        if ($startDate && $endDate) {
            $filters['date_from'] = $startDate;
            $filters['date_to'] = $endDate;
        } elseif ($year && $month) {
            $filters['year'] = $year;
            $filters['month'] = $month;
        }

        if ($deviceId) {
            $filters['device_id'] = $deviceId;
        }

        // Obtener headers (cabeceras de marcaciones por día)
        $headers = $this->headerModel->getAll($filters);

        // Obtener estadísticas generales
        $stats = $this->headerModel->getStatistics($filters);

        // Obtener años y dispositivos disponibles para filtros
        $availableYears = $this->headerModel->getAvailableYears();
        $devices = $this->deviceModel->getActive();

        $data = [
            'title' => 'Marcaciones de Asistencia',
            'page_title' => 'Listado de Marcaciones por Día',
            'headers' => $headers,
            'stats' => $stats,
            'devices' => $devices,
            'available_years' => $availableYears,
            'current_year' => $year,
            'current_month' => $month,
            'current_device_id' => $deviceId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'csrf_token' => Security::generateToken()
        ];

        $this->render('admin/attendance/list', $data);
    }

    /**
     * Vista de detalle - Marcaciones de un día específico
     * REFACTORIZADO: Usa AttendanceHeader y AttendanceDetail
     * Incluye cálculos existentes para cada marcación
     */
    public function detail($date)
    {
        if (!$date) {
            $this->setFlashMessage('Fecha no especificada', 'error');
            $this->redirect('/panel/attendance');
            return;
        }

        // Validar formato de fecha
        if (!strtotime($date)) {
            $this->setFlashMessage('Formato de fecha inválido', 'error');
            $this->redirect('/panel/attendance');
            return;
        }

        // Obtener header del día
        $header = $this->headerModel->getByDate($date);

        if (!$header) {
            $this->setFlashMessage('No hay marcaciones registradas para esta fecha.', 'warning');
            $this->redirect('/panel/attendance');
            return;
        }

        // Obtener detalles de marcaciones del día
        $statusFilter = $_GET['status'] ?? null;
        $filters = [];
        if ($statusFilter) {
            $filters['status'] = $statusFilter;
        }

        $details = $this->detailModel->getByHeader($header['id'], $filters);

        // Cargar cálculos existentes para cada marcación
        foreach ($details as &$detail) {
            $calculation = $this->attendanceCalculator->getCalculation($detail['id']);
            $detail['calculation'] = $calculation;
        }
        unset($detail); // Romper la referencia

        $data = [
            'title' => 'Detalle de Marcaciones - ' . date('d/m/Y', strtotime($date)),
            'page_title' => 'Detalle de Marcaciones',
            'date' => $date,
            'header' => $header,
            'details' => $details,
            'current_status_filter' => $statusFilter,
            'csrf_token' => Security::generateToken()
        ];

        $this->render('admin/attendance/detail', $data);
    }

    /**
     * Vista de sincronización manual
     * REFACTORIZADO: Con 3 tabs (API, Archivo, Manual)
     */
    public function sync()
    {
        // Obtener dispositivos por tipo
        $apiDevices = $this->deviceModel->getByType('API');
        $fileDevices = $this->deviceModel->getByType('TEXT_FILE');

        // Obtener últimas sincronizaciones
        $recentSyncs = $this->syncLogModel->getRecent(5);

        // Obtener configuración API y estadísticas
        $apiConfigModel = new AttendanceApiConfig();
        $apiConfig = $apiConfigModel->getActiveConfig();
        $syncStats = null;

        if ($apiConfig) {
            $syncStats = $apiConfigModel->getSyncStats($apiConfig['id'], 7);
        }

        $data = [
            'title' => 'Sincronización de Marcaciones',
            'page_title' => 'Control de Sincronización',
            'api_devices' => $apiDevices,
            'file_devices' => $fileDevices,
            'recent_syncs' => $recentSyncs,
            'api_config' => $apiConfig,
            'sync_stats' => $syncStats,
            'csrf_token' => Security::generateToken()
        ];

        $this->render('admin/attendance/sync', $data);
    }

    /**
     * Vista de ausencias pendientes
     * Lista ausencias sin justificar para su resolución
     */
    public function pendingAbsencesView()
    {
        // Obtener filtros
        $employeeId = $_GET['employee_id'] ?? null;
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;

        // Obtener ausencias pendientes
        $pendingAbsences = $this->absenceDetector->getPendingAbsences();

        // Aplicar filtros adicionales si existen
        if ($employeeId) {
            $pendingAbsences = array_filter($pendingAbsences, function($absence) use ($employeeId) {
                return $absence['employee_id'] == $employeeId;
            });
        }

        if ($startDate && $endDate) {
            $pendingAbsences = array_filter($pendingAbsences, function($absence) use ($startDate, $endDate) {
                return $absence['absence_date'] >= $startDate && $absence['absence_date'] <= $endDate;
            });
        }

        // Obtener lista de empleados para filtro
        $employees = $this->employeeModel->all();
        $activeEmployees = array_filter($employees, function($emp) {
            return $emp['situacion_id'] == 1;
        });

        // Calcular estadísticas
        $stats = [
            'total_pending' => count($pendingAbsences),
            'unjustified' => count(array_filter($pendingAbsences, fn($a) => $a['absence_type'] === 'UNJUSTIFIED')),
            'pending' => count(array_filter($pendingAbsences, fn($a) => $a['absence_type'] === 'PENDING')),
            'employees_affected' => count(array_unique(array_column($pendingAbsences, 'employee_id')))
        ];

        $data = [
            'title' => 'Ausencias Pendientes',
            'page_title' => 'Gestión de Ausencias Pendientes',
            'absences' => array_values($pendingAbsences),
            'employees' => $activeEmployees,
            'stats' => $stats,
            'filters' => [
                'employee_id' => $employeeId,
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'csrf_token' => Security::generateToken()
        ];

        $this->render('admin/attendance/pending-absences', $data);
    }

    /**
     * Vista historial de sincronizaciones
     */
    public function syncHistory()
    {
        $filters = [];

        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }

        if (!empty($_GET['date_from'])) {
            $filters['date_from'] = $_GET['date_from'];
        }

        if (!empty($_GET['date_to'])) {
            $filters['date_to'] = $_GET['date_to'];
        }

        $syncLogs = $this->syncLogModel->getAll($filters);
        $stats = $this->syncLogModel->getStats();

        $data = [
            'title' => 'Historial de Sincronizaciones',
            'page_title' => 'Historial de Sincronizaciones',
            'sync_logs' => $syncLogs,
            'stats' => $stats,
            'filters' => $filters,
            'csrf_token' => Security::generateToken()
        ];

        $this->render('admin/attendance/sync-history/index', $data);
    }

    /**
     * Detalle de sincronización (AJAX)
     */
    public function syncHistoryDetail($syncId)
    {
        try {
            $detail = $this->syncLogModel->getDetailBySyncId($syncId);

            if (!$detail) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Sincronización no encontrada.'
                ]);
            }

            return $this->jsonResponse([
                'success' => true,
                'data' => $detail
            ]);

        } catch (Exception $e) {
            error_log("Error getting sync detail: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error al obtener detalle: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Ejecutar sincronización manual (AJAX)
     * Sincroniza desde API y crea automáticamente las cabeceras y detalles
     */
    public function syncNow()
    {
        try {
            $syncType = $_POST['sync_type'] ?? 'full';
            $startDate = $_POST['start_date'] ?? null;
            $endDate = $_POST['end_date'] ?? null;

            // Importar clase de servicio
            require_once __DIR__ . '/../Services/Attendance/AttendanceSyncService.php';
            $syncService = new \App\Services\Attendance\AttendanceSyncService();

            // Ejecutar sincronización según tipo
            // El servicio ahora crea automáticamente headers y details
            switch ($syncType) {
                case 'full':
                    $stats = $syncService->syncAll();
                    break;

                case 'today':
                    $today = date('Y-m-d');
                    $stats = $syncService->syncByDateRange($today, $today);
                    break;

                case 'daterange':
                    if (!$startDate || !$endDate) {
                        return $this->jsonResponse([
                            'success' => false,
                            'message' => 'Debe especificar fechas de inicio y fin.'
                        ]);
                    }
                    $stats = $syncService->syncByDateRange($startDate, $endDate);
                    break;

                default:
                    return $this->jsonResponse([
                        'success' => false,
                        'message' => 'Tipo de sincronización no válido.'
                    ]);
            }
            print_r($stats);exit;
            return $this->jsonResponse([
                'success' => true,
                'message' => "Sincronización completada. Insertados: {$stats['inserted']}, Actualizados: {$stats['updated']}, Omitidos: {$stats['skipped']}, Errores: {$stats['errors']}",
                'data' => $stats
            ]);

        } catch (Exception $e) {
            error_log("Error in syncNow: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error en sincronización: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Importar archivo de texto (AJAX)
     */
    public function importFile()
    {
        try {
            // TODO: Implementar importación de archivos
            // Validar archivo, parsear según config del dispositivo, insertar en BD

            return $this->jsonResponse([
                'success' => false,
                'message' => 'Importación de archivos - En desarrollo'
            ]);

        } catch (Exception $e) {
            error_log("Error importing file: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Registro manual de marcación (POST)
     */
    public function manualEntry()
    {
        try {
            // TODO: Implementar registro manual
            // Validar datos, crear header si no existe, crear detail

            $this->setFlashMessage('Registro manual - En desarrollo', 'info');
            return $this->redirect('/panel/attendance/sync');

        } catch (Exception $e) {
            error_log("Error in manualEntry: " . $e->getMessage());
            $this->setFlashMessage('Error: ' . $e->getMessage(), 'error');
            return $this->redirect('/panel/attendance/sync');
        }
    }

    /**
     * Actualizar marcación individual (AJAX)
     * Incluye recálculo automático de métricas
     */
    public function updateDetail($id)
    {
        try {
            $detail = $this->detailModel->getById($id);

            if (!$detail) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Marcación no encontrada.'
                ]);
            }

            // Normalizar entradas de tiempo: convertir '' a NULL y completar segundos
            $padSec = function ($t) {
                if ($t === null) return null;
                $t = trim($t);
                if ($t === '') return null;
                return (strlen($t) === 5) ? ($t . ':00') : $t; // HH:MM -> HH:MM:SS
            };

            $timeInRaw = isset($_POST['time_in']) ? trim($_POST['time_in']) : '';
            $timeOutRaw = isset($_POST['time_out']) ? trim($_POST['time_out']) : '';
            $lunchOutRaw = isset($_POST['lunch_out']) ? trim($_POST['lunch_out']) : '';
            $lunchInRaw  = isset($_POST['lunch_in']) ? trim($_POST['lunch_in']) : '';

            $dateBase = $detail['date']; // YYYY-MM-DD

            $data = [
                'time_in'  => ($timeInRaw !== '') ? $padSec($timeInRaw) : null,
                'time_out' => ($timeOutRaw !== '') ? $padSec($timeOutRaw) : null,
                // lunch_* son DATETIME en BD: componer fecha + hora cuando haya valor
                'lunch_out' => ($lunchOutRaw !== '') ? ($dateBase . ' ' . $padSec($lunchOutRaw)) : null,
                'lunch_in'  => ($lunchInRaw !== '') ? ($dateBase . ' ' . $padSec($lunchInRaw)) : null,
                'notes' => $_POST['notes'] ?? null
            ];

            // Reglas de estado en edición manual:
            // - Si hay entrada y salida: PRESENT
            // - Si solo hay una de las dos: INCOMPLETE
            if (!empty($data['time_in']) && !empty($data['time_out'])) {
                $data['status'] = 'PRESENT';
            } elseif (!empty($data['time_in']) || !empty($data['time_out'])) {
                $data['status'] = 'INCOMPLETE';
            }

            $result = $this->detailModel->update($id, $data);

            if ($result) {
                // Obtener el detalle actualizado para procesos posteriores
                $updatedDetail = $this->detailModel->getById($id);

                // Recalcular automáticamente si tiene time_in y time_out
                $calculationData = null;
                // Recalcular si hay cambios en horas de entrada/salida o almuerzo
                if (($data['time_in'] && $data['time_out']) || ($data['lunch_out'] || $data['lunch_in'])) {
                    try {
                        // Preparar datos para calculador
                        $attendanceData = [
                            'id' => $updatedDetail['id'],
                            'employee_id' => $updatedDetail['employee_id'],
                            'date' => $updatedDetail['date'],
                            'time_in' => $updatedDetail['time_in'],
                            'time_out' => $updatedDetail['time_out'],
                            'lunch_out' => $updatedDetail['lunch_out'] ?? null,
                            'lunch_in' => $updatedDetail['lunch_in'] ?? null,
                        ];

                        // Calcular y guardar
                        $calculation = $this->attendanceCalculator->calculateAndSave($attendanceData);

                        $calculationData = [
                            'total_hours' => $calculation['total_hours'],
                            'is_late' => $calculation['is_late'],
                            'tardiness_minutes' => $calculation['tardiness_minutes'],
                            'punctuality_score' => $calculation['punctuality_score']
                        ];

                        // NUEVO: Eliminar ausencia si se agregó/corrigió marcación
                        // Si ahora tiene entrada Y salida, ya NO es ausente
                        if ($updatedDetail['time_in'] && $updatedDetail['time_out']) {
                            $this->absenceDetector->deleteAbsenceByEmployeeAndDate(
                                $updatedDetail['employee_id'],
                                $updatedDetail['date']
                            );
                            error_log("Ausencia eliminada para empleado {$updatedDetail['employee_id']} - fecha {$updatedDetail['date']}");
                        }

                    } catch (Exception $calcError) {
                        error_log("Error en recálculo automático: " . $calcError->getMessage());
                        // No fallar la actualización si falla el cálculo
                    }
                }

                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Marcación actualizada exitosamente.' . ($calculationData ? ' Cálculos recalculados.' : ''),
                    'calculation' => $calculationData
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Error al actualizar la marcación.'
                ]);
            }

        } catch (Exception $e) {
            error_log("Error updating detail: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener marcación individual (AJAX)
     * Devuelve todos los campos relevantes para el modal de edición
     */
    public function getDetail($id)
    {
        try {
            $detail = $this->detailModel->getById($id);

            if (!$detail) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Marcación no encontrada.'
                ]);
            }

            // Formatear respuesta
            $employeeName = trim(($detail['firstname'] ?? '') . ' ' . ($detail['lastname'] ?? ''));
            $data = [
                'id' => (int)$detail['id'],
                'date' => $detail['date'],
                'employee' => [
                    'id' => (int)$detail['employee_id'],
                    'name' => $employeeName,
                    'code' => $detail['employee_code'] ?? ($detail['employee_number'] ?? null),
                ],
                // Marcaciones
                'time_in' => $detail['time_in'],
                'time_out' => $detail['time_out'],
                'lunch_out' => $detail['lunch_out'] ?? null,
                'lunch_in' => $detail['lunch_in'] ?? null,
                // Horarios programados
                'scheduled_time_in' => $detail['scheduled_time_in'] ?? null,
                'scheduled_time_out' => $detail['scheduled_time_out'] ?? null,
                'scheduled_lunch_out' => $detail['scheduled_lunch_out'] ?? null,
                'scheduled_lunch_in' => $detail['scheduled_lunch_in'] ?? null,
                // Métricas
                'hours_worked' => $detail['hours_worked'] ?? 0,
                'tardiness_minutes' => $detail['tardiness_minutes'] ?? 0,
                'is_late' => (int)($detail['is_late'] ?? 0),
                'lunch_duration_minutes' => $detail['lunch_duration_minutes'] ?? 0,
                'lunch_exceeded_minutes' => $detail['lunch_exceeded_minutes'] ?? 0,
                'status' => $detail['status'] ?? null,
                'notes' => $detail['notes'] ?? null,
            ];

            return $this->jsonResponse([
                'success' => true,
                'data' => $data
            ]);

        } catch (Exception $e) {
            error_log("Error getting detail: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Verificar si una marcación tiene horas extras aprobadas (AJAX)
     * Usado para mostrar alerta de confirmación antes de eliminar
     */
    public function checkOvertimeBeforeDelete($id)
    {
        try {
            $detail = $this->detailModel->getById($id);

            if (!$detail) {
                return $this->jsonResponse(['success' => false, 'message' => 'Marcación no encontrada.']);
            }

            $employeeId = (int)$detail['employee_id'];
            $date       = $detail['date'];

            $overtimeModel    = new OvertimeApproval();
            $approvedOvertime = $overtimeModel->getApprovedOvertimeHoursForDate($employeeId, $date);

            return $this->jsonResponse([
                'success'          => true,
                'has_approved'     => $approvedOvertime['has_approved'],
                'overtime_status'  => $approvedOvertime['overtime_status'],
                'overtime_25'      => $approvedOvertime['total_overtime_25'],
                'overtime_50'      => $approvedOvertime['total_overtime_50'],
                'total_hours'      => $approvedOvertime['total_hours'],
                'employee_name'    => trim($detail['firstname'] . ' ' . $detail['lastname']),
                'date'             => $date,
            ]);

        } catch (Exception $e) {
            error_log("Error checking overtime before delete: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Eliminar marcación (AJAX)
     */
    public function deleteDetail($id)
    {
        try {
            $detail = $this->detailModel->getById($id);

            if (!$detail) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Marcación no encontrada.'
                ]);
            }

            $employeeId = (int)$detail['employee_id'];
            $date       = $detail['date']; // attendance_header.attendance_date

            $result = $this->detailModel->delete($id);

            if ($result) {
                // Eliminar en cascada las horas extras del mismo empleado y día
                $overtimeModel = new OvertimeApproval();
                $overtimeModel->deleteByEmployeeAndDate($employeeId, $date);

                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Marcación eliminada exitosamente.'
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Error al eliminar la marcación.'
                ]);
            }

        } catch (Exception $e) {
            error_log("Error deleting detail: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Justificar ausencia (AJAX)
     */
    public function justifyAbsence($id)
    {
        try {
            $documentPath = null;

            // Manejar carga de archivo PDF
            if (isset($_FILES['justification_document']) && $_FILES['justification_document']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['justification_document'];

                // Validar tipo de archivo
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if ($mimeType !== 'application/pdf') {
                    return $this->jsonResponse([
                        'success' => false,
                        'message' => 'Solo se permiten archivos PDF.'
                    ]);
                }

                // Validar tamaño (1MB máximo)
                if ($file['size'] > 1048576) {
                    return $this->jsonResponse([
                        'success' => false,
                        'message' => 'El archivo no debe superar 1MB de tamaño.'
                    ]);
                }

                // Obtener directorio de justificaciones por tenant
                $uploadDir = \App\Core\TenantStorage::getJustificationDirectory();
                \App\Core\TenantStorage::ensureDirectory($uploadDir);

                // Generar nombre único para el archivo
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $fileName = 'justification_' . $id . '_' . time() . '.' . $extension;
                $destination = $uploadDir . $fileName;

                // Mover archivo
                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    return $this->jsonResponse([
                        'success' => false,
                        'message' => 'Error al guardar el archivo.'
                    ]);
                }

                // Construir path relativo para BD
                $tenantKey = \App\Core\TenantStorage::getTenantKey();
                $documentPath = 'storage/tenants/' . $tenantKey . '/justifications/' . $fileName;
            }

            $data = [
                'justification_type' => $_POST['justification_type'] ?? 'OTHER',
                'justification_notes' => $_POST['justification_notes'] ?? null,
                'justification_document' => $documentPath
            ];

            $result = $this->detailModel->justifyAbsence($id, $data);

            if ($result) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Ausencia justificada exitosamente.'
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Error al justificar ausencia.'
                ]);
            }

        } catch (Exception $e) {
            error_log("Error justifying absence: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener detalles de una justificación existente (AJAX)
     *
     * @param int $id ID del attendance_detail
     * @return array JSON response
     */
    public function getJustification($id)
    {
        try {
            // Obtener el registro de detalle con justificación
            $detail = $this->detailModel->getById($id);

            if (!$detail) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Registro no encontrado.'
                ]);
            }

            // Verificar que esté justificado
            if ($detail['status'] !== 'JUSTIFIED') {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Este registro no tiene una justificación.'
                ]);
            }

            // Preparar datos para el frontend
            $data = [
                'justification_type' => $detail['justification_type'],
                'justification_notes' => $detail['justification_notes'],
                'justification_document' => $detail['justification_document'],
                'employee_name' => $detail['firstname'] . ' ' . $detail['lastname'],
                'employee_code' => $detail['employee_number'] ?? $detail['employee_code'],
                'date' => date('d/m/Y', strtotime($detail['date']))
            ];

            return $this->jsonResponse([
                'success' => true,
                'data' => $data
            ]);

        } catch (Exception $e) {
            error_log("Error getting justification: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Actualizar una justificación existente (AJAX)
     *
     * @param int $id ID del attendance_detail
     * @return array JSON response
     */
    public function updateJustification($id)
    {
        try {
            // Obtener el registro actual
            $detail = $this->detailModel->getById($id);

            if (!$detail) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Registro no encontrado.'
                ]);
            }

            $documentPath = $detail['justification_document']; // Mantener el documento actual por defecto
            $removeDocument = isset($_POST['remove_document']) && $_POST['remove_document'] == '1';

            // Si se solicita eliminar el documento actual
            if ($removeDocument) {
                // Eliminar archivo físico si existe
                if ($documentPath && file_exists(__DIR__ . '/../../' . $documentPath)) {
                    unlink(__DIR__ . '/../../' . $documentPath);
                }
                $documentPath = null;
            }

            // Manejar carga de nuevo archivo PDF
            if (isset($_FILES['justification_document']) && $_FILES['justification_document']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['justification_document'];

                // Validar tipo de archivo
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if ($mimeType !== 'application/pdf') {
                    return $this->jsonResponse([
                        'success' => false,
                        'message' => 'Solo se permiten archivos PDF.'
                    ]);
                }

                // Validar tamaño (1MB máximo)
                if ($file['size'] > 1048576) {
                    return $this->jsonResponse([
                        'success' => false,
                        'message' => 'El archivo no debe superar 1MB de tamaño.'
                    ]);
                }

                // Eliminar documento anterior si existe y se está reemplazando
                if ($documentPath && file_exists(__DIR__ . '/../../' . $documentPath)) {
                    unlink(__DIR__ . '/../../' . $documentPath);
                }

                // Obtener directorio de justificaciones por tenant
                $uploadDir = \App\Core\TenantStorage::getJustificationDirectory();
                \App\Core\TenantStorage::ensureDirectory($uploadDir);

                // Generar nombre único para el archivo
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $fileName = 'justification_' . $id . '_' . time() . '.' . $extension;
                $destination = $uploadDir . $fileName;

                // Mover archivo
                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    return $this->jsonResponse([
                        'success' => false,
                        'message' => 'Error al guardar el archivo.'
                    ]);
                }

                // Construir path relativo para BD
                $tenantKey = \App\Core\TenantStorage::getTenantKey();
                $documentPath = 'storage/tenants/' . $tenantKey . '/justifications/' . $fileName;
            }

            // Preparar datos para actualizar
            $data = [
                'justification_type' => $_POST['justification_type'] ?? $detail['justification_type'],
                'justification_notes' => $_POST['justification_notes'] ?? $detail['justification_notes'],
                'justification_document' => $documentPath
            ];

            // Actualizar el registro
            $result = $this->detailModel->update($id, $data);

            // También actualizar en attendance_absence_log si existe
            $this->db->prepare("
                UPDATE attendance_absence_log
                SET justification_type = ?,
                    justification_notes = ?,
                    justification_document = ?,
                    updated_at = NOW()
                WHERE employee_id = ?
                    AND absence_date = ?
            ")->execute([
                $data['justification_type'],
                $data['justification_notes'],
                $data['justification_document'],
                $detail['employee_id'],
                $detail['date']
            ]);

            if ($result) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Justificación actualizada exitosamente.'
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Error al actualizar justificación.'
                ]);
            }

        } catch (Exception $e) {
            error_log("Error updating justification: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Exportar día a Excel
     */
    public function exportExcel($date)
    {
        try {
            // TODO: Implementar exportación a Excel
            $this->setFlashMessage('Exportación a Excel - En desarrollo', 'info');
            return $this->redirect('/panel/attendance/detail/' . $date);

        } catch (Exception $e) {
            error_log("Error exporting to Excel: " . $e->getMessage());
            $this->setFlashMessage('Error al exportar: ' . $e->getMessage(), 'error');
            return $this->redirect('/panel/attendance/detail/' . $date);
        }
    }

    /**
     * Generar PDF del día
     */
    public function exportPDF($date)
    {
        try {
            // TODO: Implementar generación de PDF
            $this->setFlashMessage('Generación de PDF - En desarrollo', 'info');
            return $this->redirect('/panel/attendance/detail/' . $date);

        } catch (Exception $e) {
            error_log("Error generating PDF: " . $e->getMessage());
            $this->setFlashMessage('Error al generar PDF: ' . $e->getMessage(), 'error');
            return $this->redirect('/panel/attendance/detail/' . $date);
        }
    }

    // ========================================
    // MÉTODOS DE INTEGRACIÓN CON CALCULADORES
    // ========================================

    /**
     * Calcular o recalcular métricas de una asistencia (AJAX)
     * Usa AttendanceCalculator para generar todas las métricas
     *
     * @param int $detailId ID del registro en attendance_detail
     * @return array JSON response
     */
    public function calculateAttendance($detailId)
    {
        try {
            $detail = $this->detailModel->getById($detailId);

            if (!$detail) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Marcación no encontrada.'
                ]);
            }

            // Verificar si la fecha es un feriado pagado
            $isPaidHoliday = false;
            $dayInfo = null;
            try {
                $calendar = new \App\Models\BusinessCalendar();
                $dayInfo = $calendar->getDayInfo($detail['date']);

                if ($dayInfo) {
                    $isPaidHoliday = ($dayInfo['day_type'] === 'FERIADO' && isset($dayInfo['is_paid_holiday']) && $dayInfo['is_paid_holiday'] == 1);
                }
            } catch (\Exception $e) {
                error_log("Error checking business calendar in calculateAttendance: " . $e->getMessage());
            }

            // Si es feriado pagado, generar cálculo manual sin usar AttendanceCalculator
            if ($isPaidHoliday) {
                // Obtener horario del empleado
                $scheduleId = $detail['schedule_id'] ?? null;
                $timeIn = $detail['time_in'] ?? '08:00:00';
                $timeOut = $detail['time_out'] ?? '17:00:00';
                $lunchOut = null;
                $lunchIn = null;
                $lunchMinutes = 0;

                if ($scheduleId) {
                    $sql = "SELECT time_in, time_out, salida_almuerzo, entrada_almuerzo FROM schedules WHERE id = ?";
                    $stmt = $this->employeeModel->db->prepare($sql);
                    $stmt->execute([$scheduleId]);
                    $schedule = $stmt->fetch(\PDO::FETCH_ASSOC);

                    if ($schedule) {
                        $timeIn = $schedule['time_in'];
                        $timeOut = $schedule['time_out'];
                        $lunchOut = $schedule['salida_almuerzo'] ?? null;
                        $lunchIn = $schedule['entrada_almuerzo'] ?? null;

                        // Calcular minutos de almuerzo
                        if ($lunchOut && $lunchIn) {
                            $lunchStart = new \DateTime($lunchOut);
                            $lunchEnd = new \DateTime($lunchIn);
                            $lunchInterval = $lunchStart->diff($lunchEnd);
                            $lunchMinutes = ($lunchInterval->h * 60) + $lunchInterval->i;
                        }
                    }
                }

                // Calcular horas netas (descontando almuerzo)
                $timeInObj = new \DateTime($timeIn);
                $timeOutObj = new \DateTime($timeOut);
                $interval = $timeInObj->diff($timeOutObj);
                $totalMinutes = ($interval->h * 60) + $interval->i;
                $netMinutes = $totalMinutes - $lunchMinutes;
                $holidayHours = round($netMinutes / 60, 2);

                // Crear cálculo manual para feriado pagado
                $calculation = [
                    'attendance_detail_id' => $detail['id'],
                    'employee_id' => $detail['employee_id'],
                    'date' => $detail['date'],
                    'schedule_id' => $scheduleId,
                    'time_in' => $timeIn,
                    'time_out' => $timeOut,
                    'lunch_out' => $lunchOut ? date('Y-m-d', strtotime($detail['date'])) . ' ' . $lunchOut : null,
                    'lunch_in' => $lunchIn ? date('Y-m-d', strtotime($detail['date'])) . ' ' . $lunchIn : null,
                    'scheduled_time_in' => $timeIn,
                    'scheduled_time_out' => $timeOut,
                    'scheduled_lunch_out' => $lunchOut,
                    'scheduled_lunch_in' => $lunchIn,
                    'total_hours' => $holidayHours,
                    'regular_hours' => 0,
                    'overtime_hours' => 0,
                    'overtime_25_hours' => 0,
                    'overtime_50_hours' => 0,
                    'night_hours' => 0,
                    'holiday_hours' => $holidayHours,
                    'tardiness_minutes' => 0,
                    'is_late' => 0,
                    'early_departure_minutes' => 0,
                    'is_absent' => 0,
                    'absence_type' => null,
                    'is_working_day' => 0,
                    'is_holiday' => 1,
                    'is_weekend' => 0,
                    'day_type' => 'FERIADO',
                    'lunch_time_minutes' => $lunchMinutes,
                    'lunch_exceeded_minutes' => 0,
                    'is_perfect_attendance' => 1,
                    'punctuality_score' => 100,
                    'notes' => 'Feriado Pagado - ' . ($dayInfo['description'] ?? 'Día Feriado'),
                    'calculation_version' => 'v1.0',
                    'calculated_at' => date('Y-m-d H:i:s')
                ];

                error_log("PAID HOLIDAY CALCULATE: Generando cálculo manual para feriado pagado - employee_id={$detail['employee_id']}, date={$detail['date']}, holiday_hours={$holidayHours}");

            } else {
                // Procesamiento normal para días NO feriados pagados
                // Preparar datos para el calculador
                $attendanceData = [
                    'id' => $detail['id'],
                    'employee_id' => $detail['employee_id'],
                    'date' => $detail['date'],
                    'time_in' => $detail['time_in'],
                    'time_out' => $detail['time_out'],
                    'lunch_out' => $detail['lunch_out'] ?? null,
                    'lunch_in' => $detail['lunch_in'] ?? null
                ];

                // Calcular métricas usando el calculador normal
                $calculation = $this->attendanceCalculator->calculate($attendanceData);
            }

            // Guardar en BD
            $calculationId = $this->attendanceCalculator->saveCalculation($calculation);

            // Actualizar estado en attendance_detail basándose en el cálculo
            $newStatus = 'PRESENT'; // Por defecto
            if ($calculation['is_absent'] == 1) {
                $newStatus = 'ABSENT';
            } elseif (empty($calculation['time_out'])) {
                $newStatus = 'INCOMPLETE';
            }

            $this->detailModel->update($detailId, [
                'status' => $newStatus,
                'hours_worked' => $calculation['total_hours'],
                'is_late' => $calculation['is_late'],
                'tardiness_minutes' => $calculation['tardiness_minutes']
            ]);

            // Devolver TODOS los datos necesarios para el modal
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Cálculos procesados exitosamente.',
                'data' => [
                    // ID del cálculo
                    'calculation_id' => $calculationId,

                    // Marcaciones reales (de attendance_detail)
                    'time_in' => $calculation['time_in'],
                    'time_out' => $calculation['time_out'],
                    'lunch_out' => $calculation['lunch_out'] ?? null,
                    'lunch_in' => $calculation['lunch_in'] ?? null,

                    // Horarios programados
                    'scheduled_time_in' => $calculation['scheduled_time_in'],
                    'scheduled_time_out' => $calculation['scheduled_time_out'],
                    'scheduled_lunch_out' => $calculation['scheduled_lunch_out'] ?? null,
                    'scheduled_lunch_in' => $calculation['scheduled_lunch_in'] ?? null,

                    // Horas trabajadas y calculadas
                    'total_hours' => $calculation['total_hours'],
                    'regular_hours' => $calculation['regular_hours'],
                    'overtime_hours' => $calculation['overtime_hours'],
                    'overtime_25_hours' => $calculation['overtime_25_hours'],
                    'overtime_50_hours' => $calculation['overtime_50_hours'],
                    'night_hours' => $calculation['night_hours'],
                    'holiday_hours' => $calculation['holiday_hours'],

                    // Tardanzas y puntualidad
                    'tardiness_minutes' => $calculation['tardiness_minutes'],
                    'is_late' => $calculation['is_late'],
                    'early_departure_minutes' => $calculation['early_departure_minutes'],
                    'lunch_time_minutes' => $calculation['lunch_time_minutes'],
                    'lunch_exceeded_minutes' => $calculation['lunch_exceeded_minutes'] ?? 0,
                    'punctuality_score' => $calculation['punctuality_score'],
                    'is_perfect_attendance' => $calculation['is_perfect_attendance'],

                    // Tipo de día y estado
                    'is_working_day' => $calculation['is_working_day'],
                    'is_holiday' => $calculation['is_holiday'],
                    'is_weekend' => $calculation['is_weekend'],
                    'day_type' => $calculation['day_type'],
                    'is_absent' => $calculation['is_absent'],

                    // Notas y metadata
                    'notes' => $calculation['notes'],
                    'calculation_version' => $calculation['calculation_version'],
                    'calculated_at' => $calculation['calculated_at']
                ]
            ]);

        } catch (Exception $e) {
            error_log("Error calculating attendance: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error al calcular asistencia: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Detectar ausencias de un período (AJAX)
     * Usa AbsenceDetector para identificar empleados sin marcación
     *
     * @return array JSON response
     */
    public function detectAbsences()
    {
        try {
            $startDate = $_POST['start_date'] ?? null;
            $endDate = $_POST['end_date'] ?? null;
            $employeeId = $_POST['employee_id'] ?? null;
            $saveToDb = isset($_POST['save_to_db']) && $_POST['save_to_db'] === '1';

            if (!$startDate || !$endDate) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Debe especificar fecha de inicio y fin.'
                ]);
            }

            // Detectar ausencias
            if ($employeeId) {
                // Un solo empleado
                $result = $this->absenceDetector->detectAndSaveAbsences(
                    $employeeId,
                    $startDate,
                    $endDate,
                    $saveToDb
                );

                return $this->jsonResponse([
                    'success' => true,
                    'message' => "Ausencias detectadas: {$result['detected']}, guardadas: {$result['saved']}",
                    'data' => $result
                ]);
            } else {
                // Todos los empleados activos
                $employees = $this->employeeModel->all();
                $activeEmployees = array_filter($employees, function($emp) {
                    return $emp['situacion_id'] == 1;
                });
                $employeeIds = array_column($activeEmployees, 'id');

                $result = $this->absenceDetector->detectAndSaveBulk(
                    $employeeIds,
                    $startDate,
                    $endDate,
                    $saveToDb
                );

                return $this->jsonResponse([
                    'success' => true,
                    'message' => "Procesados {$result['employees_processed']} empleados. Ausencias detectadas: {$result['total_detected']}, guardadas: {$result['total_saved']}",
                    'data' => $result
                ]);
            }

        } catch (Exception $e) {
            error_log("Error detecting absences: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error al detectar ausencias: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Procesar cálculos en batch para un día completo (AJAX)
     * Calcula métricas para todas las marcaciones de una fecha
     *
     * @return array JSON response
     */
    public function processCalculations()
    {
        try {
            $date = $_POST['date'] ?? null;

            if (!$date) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Debe especificar una fecha.'
                ]);
            }

            // Obtener header del día
            $header = $this->headerModel->getByDate($date);

            if (!$header) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'No hay marcaciones para esta fecha.'
                ]);
            }

            // Obtener todas las marcaciones del día
            $details = $this->detailModel->getByHeader($header['id']);

            if (empty($details)) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'No hay registros de asistencia para procesar.'
                ]);
            }

            // Preparar datos para calculador
            $attendances = [];
            foreach ($details as $detail) {
                $attendances[] = [
                    'id' => $detail['id'],
                    'employee_id' => $detail['employee_id'],
                    'date' => $date,
                    'time_in' => $detail['time_in'],
                    'time_out' => $detail['time_out'],
                    'lunch_out' => $detail['lunch_out'] ?? null,
                    'lunch_in' => $detail['lunch_in'] ?? null
                ];
            }

            // Procesar en batch
            $result = $this->attendanceCalculator->calculateAndSaveBulk($attendances, true);

            // Actualizar estados en attendance_detail basándose en los cálculos
            foreach ($result['calculations'] as $calculation) {
                $newStatus = 'PRESENT'; // Por defecto
                if ($calculation['is_absent'] == 1) {
                    $newStatus = 'ABSENT';
                } elseif (empty($calculation['time_out'])) {
                    $newStatus = 'INCOMPLETE';
                }

                $this->detailModel->update($calculation['attendance_detail_id'], [
                    'status' => $newStatus,
                    'hours_worked' => $calculation['total_hours'],
                    'is_late' => $calculation['is_late'],
                    'tardiness_minutes' => $calculation['tardiness_minutes']
                ]);
            }

            return $this->jsonResponse([
                'success' => true,
                'message' => "Procesadas {$result['stats']['total_processed']} marcaciones. Guardadas: {$result['stats']['saved']}, Errores: {$result['stats']['errors']}",
                'data' => $result['stats']
            ]);

        } catch (Exception $e) {
            error_log("Error processing calculations: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error al procesar cálculos: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener reporte de ausencias de un empleado (AJAX)
     *
     * @return array JSON response
     */
    public function getAbsenceReport()
    {
        try {
            $employeeId = $_GET['employee_id'] ?? null;
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;

            if (!$employeeId || !$startDate || !$endDate) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Parámetros incompletos.'
                ]);
            }

            $employee = $this->employeeModel->find($employeeId);

            if (!$employee) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Empleado no encontrado.'
                ]);
            }

            // Obtener estadísticas de ausencias
            $stats = $this->absenceDetector->countAbsences($employeeId, $startDate, $endDate);
            $absenteeismRate = $this->absenceDetector->calculateAbsenteeismRate($employeeId, $startDate, $endDate);

            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'employee' => [
                        'id' => $employee['id'],
                        'name' => $employee['firstname'] . ' ' . $employee['lastname'],
                        'code' => $employee['employee_id']
                    ],
                    'period' => [
                        'start' => $startDate,
                        'end' => $endDate
                    ],
                    'absences' => $stats,
                    'absenteeism_rate' => $absenteeismRate
                ]
            ]);

        } catch (Exception $e) {
            error_log("Error getting absence report: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener ausencias pendientes de justificación (AJAX)
     *
     * @return array JSON response
     */
    public function getPendingAbsences()
    {
        try {
            $pendingAbsences = $this->absenceDetector->getPendingAbsences();

            return $this->jsonResponse([
                'success' => true,
                'total' => count($pendingAbsences),
                'data' => $pendingAbsences
            ]);

        } catch (Exception $e) {
            error_log("Error getting pending absences: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Justificar una ausencia desde vista de ausencias pendientes (AJAX)
     *
     * @param int $absenceId ID del registro en attendance_absence_log
     * @return array JSON response
     */
    public function justifyAbsenceFromLog($absenceId)
    {
        try {
            $justificationType = $_POST['justification_type'] ?? null;
            $justificationNotes = $_POST['justification_notes'] ?? null;
            $justificationDocument = $_POST['justification_document'] ?? null;

            if (!$justificationType) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Debe especificar el tipo de justificación.'
                ]);
            }

            // Justificar la ausencia
            $result = $this->absenceDetector->justifyAbsence(
                $absenceId,
                $justificationType,
                $justificationNotes,
                $justificationDocument
            );

            if ($result) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Ausencia justificada exitosamente.'
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'No se pudo justificar la ausencia.'
                ]);
            }

        } catch (Exception $e) {
            error_log("Error justifying absence: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error al justificar ausencia: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Procesar rango de fechas completo (AJAX)
     * Itera sobre todas las fechas del rango y procesa cada día
     * Similar a processDay() pero para múltiples fechas
     *
     * @return array JSON response
     */
    public function processRange()
    {
        try {
            $startDate = $_POST['start_date'] ?? null;
            $endDate = $_POST['end_date'] ?? null;

            if (!$startDate || !$endDate) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Debe especificar fecha de inicio y fin.'
                ]);
            }

            // Validar que start_date sea menor o igual a end_date
            if (strtotime($startDate) > strtotime($endDate)) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'La fecha de inicio debe ser menor o igual a la fecha de fin.'
                ]);
            }

            // Opciones de procesamiento desde el formulario
            $processRecords = isset($_POST['process_records']) ? (int)$_POST['process_records'] : 1;
            $detectAbsences = isset($_POST['detect_absences']) ? (int)$_POST['detect_absences'] : 1;
            $markOmissions = isset($_POST['mark_omissions']) ? (int)$_POST['mark_omissions'] : 1;
            $recalculate = isset($_POST['recalculate']) ? (int)$_POST['recalculate'] : 1;

            // Estadísticas consolidadas del rango
            $rangeStats = [
                'days_processed' => 0,
                'days_with_errors' => 0,
                'total_records_found' => 0,
                'total_details_updated' => 0,
                'total_absences_detected' => 0,
                'total_absences_created' => 0,
                'total_omissions_detected' => 0,
                'total_omissions_marked' => 0,
                'total_calculations_processed' => 0,
                'total_calculations_saved' => 0,
                'total_calculations_errors' => 0,
                'errors' => []
            ];

            // Iterar sobre cada día del rango
            $currentDate = $startDate;
            while (strtotime($currentDate) <= strtotime($endDate)) {
                try {
                    // Simular $_POST para processDay
                    $_POST['date'] = $currentDate;
                    $_POST['process_records'] = $processRecords;
                    $_POST['detect_absences'] = $detectAbsences;
                    $_POST['mark_omissions'] = $markOmissions;
                    $_POST['recalculate'] = $recalculate;

                    // Procesar el día
                    $dayResult = $this->processSingleDay($currentDate, $processRecords, $detectAbsences, $markOmissions, $recalculate);

                    if ($dayResult['success']) {
                        $rangeStats['days_processed']++;

                        // Sumar estadísticas del día
                        $rangeStats['total_records_found'] += $dayResult['data']['records_found'] ?? 0;
                        $rangeStats['total_details_updated'] += $dayResult['data']['details_updated'] ?? 0;
                        $rangeStats['total_absences_detected'] += $dayResult['data']['absences_detected'] ?? 0;
                        $rangeStats['total_absences_created'] += $dayResult['data']['absences_created'] ?? 0;
                        $rangeStats['total_omissions_detected'] += $dayResult['data']['omissions_detected'] ?? 0;
                        $rangeStats['total_omissions_marked'] += $dayResult['data']['omissions_marked'] ?? 0;
                        $rangeStats['total_calculations_processed'] += $dayResult['data']['calculations_processed'] ?? 0;
                        $rangeStats['total_calculations_saved'] += $dayResult['data']['calculations_saved'] ?? 0;
                        $rangeStats['total_calculations_errors'] += $dayResult['data']['calculations_errors'] ?? 0;
                    } else {
                        $rangeStats['days_with_errors']++;
                        $rangeStats['errors'][] = [
                            'date' => $currentDate,
                            'message' => $dayResult['message'] ?? 'Error desconocido'
                        ];
                        error_log("Error processing date {$currentDate}: " . ($dayResult['message'] ?? 'Unknown error'));
                    }

                } catch (Exception $dayError) {
                    $rangeStats['days_with_errors']++;
                    $rangeStats['errors'][] = [
                        'date' => $currentDate,
                        'message' => $dayError->getMessage()
                    ];
                    error_log("Exception processing date {$currentDate}: " . $dayError->getMessage());
                }

                // Avanzar al siguiente día
                $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
            }

            // Preparar mensaje de respuesta
            $message = "Procesamiento completado. {$rangeStats['days_processed']} días procesados";
            if ($rangeStats['days_with_errors'] > 0) {
                $message .= " ({$rangeStats['days_with_errors']} con errores)";
            }

            return $this->jsonResponse([
                'success' => true,
                'message' => $message,
                'data' => $rangeStats
            ]);

        } catch (Exception $e) {
            error_log("Error in processRange: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error al procesar el rango: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Método interno para procesar un solo día
     * Extrae la lógica de processDay() para reutilización
     *
     * @param string $date Fecha en formato Y-m-d
     * @param int $processRecords 1 para procesar records, 0 para omitir
     * @param int $detectAbsences 1 para detectar ausencias, 0 para omitir
     * @param int $markOmissions 1 para marcar omisiones, 0 para omitir
     * @param int $recalculate 1 para recalcular métricas, 0 para omitir
     * @return array Resultado del procesamiento
     */
    private function processSingleDay($date, $processRecords = 1, $detectAbsences = 1, $markOmissions = 1, $recalculate = 1)
    {
        try {
            if (!$date) {
                return [
                    'success' => false,
                    'message' => 'Debe especificar una fecha.'
                ];
            }

            // Cargar info del calendario empresarial y overrides personales.
            // Importante: NO se hace early-return en domingos/no-laborales,
            // porque puede haber basura previa (ABSENT auto-generados antes de
            // que el calendario empresarial estuviera inicializado) que debe
            // limpiarse al reprocesar. La decisión de crear/no crear ABSENT
            // se toma más adelante por empleado.
            $dailyScheduleModel = new \App\Models\EmployeeDailySchedule();
            $employeesWithPersonalOverride = $dailyScheduleModel->getEmployeeIdsWithOverrideForDate($date);
            $hasPersonalOverrides = !empty($employeesWithPersonalOverride);

            $dayInfo = null;
            try {
                $calendar = new \App\Models\BusinessCalendar();
                $dayInfo = $calendar->getDayInfo($date);
            } catch (\Exception $e) {
                error_log("Error checking business calendar for {$date}: " . $e->getMessage());
            }

            // Si el día es no-laboral (domingo/feriado/NO_LABORAL) y nadie tiene
            // override personal: limpiar basura previa y salir.
            $isNonWorkingNoOverride = !$hasPersonalOverrides && (
                ($dayInfo && $dayInfo['day_type'] !== 'LABORAL') ||
                (!$dayInfo && (int)date('N', strtotime($date)) === 7)
            );

            if ($isNonWorkingNoOverride) {
                $cleanedCount = 0;
                $existingHeader = $this->headerModel->getByDate($date);
                if ($existingHeader) {
                    $cleanedCount = $this->detailModel->deleteAutoAbsencesByHeader($existingHeader['id']);

                    // Recalcular stats del header tras la limpieza
                    $finalDetails = $this->detailModel->getByHeader($existingHeader['id']);
                    $totalOnTime = 0; $totalLate = 0; $totalAbsent = 0;
                    foreach ($finalDetails as $d) {
                        switch ($d['status']) {
                            case 'PRESENT': ($d['is_late'] ? $totalLate++ : $totalOnTime++); break;
                            case 'LATE':    $totalLate++;   break;
                            case 'ABSENT':  $totalAbsent++; break;
                        }
                    }
                    $this->headerModel->update($existingHeader['id'], [
                        'total_records'   => count($finalDetails),
                        'total_employees' => count($finalDetails),
                        'total_on_time'   => $totalOnTime,
                        'total_late'      => $totalLate,
                        'total_absent'    => $totalAbsent,
                        'is_processed'    => 1,
                        'processed_at'    => date('Y-m-d H:i:s'),
                    ]);
                }

                $reason = ($dayInfo && $dayInfo['day_type'] !== 'LABORAL')
                    ? ($dayInfo['day_type'] === 'FERIADO' ? 'Feriado' : 'Día no laboral')
                    : 'Domingo';

                return [
                    'success' => true,
                    'message' => "Día no laboral sin overrides personales. Limpieza: {$cleanedCount} ausencias automáticas removidas.",
                    'data' => [
                        'records_found' => 0,
                        'details_updated' => 0,
                        'absences_detected' => 0,
                        'absences_created' => 0,
                        'absences_cleaned' => $cleanedCount,
                        'omissions_detected' => 0,
                        'omissions_marked' => 0,
                        'calculations_processed' => 0,
                        'calculations_saved' => 0,
                        'calculations_errors' => 0,
                        'total_employees' => 0,
                        'skipped' => true,
                        'reason' => $reason
                    ]
                ];
            }

            // Estadísticas del procesamiento
            $stats = [
                'records_found' => 0,
                'details_updated' => 0,
                'absences_detected' => 0,
                'absences_created' => 0,
                'omissions_detected' => 0,
                'omissions_marked' => 0,
                'calculations_processed' => 0,
                'calculations_saved' => 0,
                'calculations_errors' => 0,
                'total_employees' => 0
            ];

            // 1. Obtener o crear header del día
            $header = $this->headerModel->getByDate($date);

            if (!$header) {
                // Crear header si no existe
                $headerId = $this->headerModel->create([
                    'attendance_date' => $date,
                    'device_id' => null,
                    'synced_from' => 'MANUAL',
                    'total_records' => 0,
                    'total_employees' => 0
                ]);

                if (!$headerId) {
                    return [
                        'success' => false,
                        'message' => 'Error al crear cabecera del día.'
                    ];
                }

                $header = $this->headerModel->getById($headerId);
            }

            // Obtener set de empleados activos que marcan asistencia
            $markingEmployeeIds = $this->employeeModel->getActiveMarkingEmployeeIds();

            // 2. Procesar registros attendance_records → attendance_detail con RecordsProcessor
            if ($processRecords) {
                $procStats = $this->recordsProcessor->processDay($date);
                $stats['records_found'] = $procStats['groups_processed'] ?? 0;
                $stats['details_updated'] += ($procStats['details_updated'] ?? 0);
                $stats['details_updated'] += ($procStats['details_created'] ?? 0);
            }

            // Reconstruir índice de empleados con marcación desde details actuales
            $existingDetails = $this->detailModel->getByHeader($header['id']);
            $employeesWithAttendance = [];
            foreach ($existingDetails as $d) {
                $employeesWithAttendance[$d['employee_id']] = true;
            }

            // 3. Empleados activos a considerar: SOLO los que marcan asistencia
            $activeEmployees = $this->employeeModel->getActiveMarkingEmployees();
            $activeEmployees = array_values($activeEmployees);
            $stats['total_employees'] = count($activeEmployees);

            if (empty($activeEmployees)) {
                return [
                    'success' => false,
                    'message' => 'No hay empleados activos en el sistema.'
                ];
            }

            // 4. Obtener detalles existentes después de actualizar desde records
            $existingDetails = $this->detailModel->getByHeader($header['id']);
            $employeesWithAttendance = [];

            foreach ($existingDetails as $detail) {
                $employeesWithAttendance[$detail['employee_id']] = true;

                // Sincronizar horario programado del detalle con el horario ACTUAL del empleado
                try {
                    // Obtener schedule_id actual del empleado
                    $empSql = "SELECT schedule_id FROM employees WHERE id = ?";
                    $empStmt = $this->employeeModel->db->prepare($empSql);
                    $empStmt->execute([$detail['employee_id']]);
                    $empRow = $empStmt->fetch(\PDO::FETCH_ASSOC);

                    $currentScheduleId = $empRow['schedule_id'] ?? null;

                    if (!empty($currentScheduleId)) {
                        // Cargar horario actual
                        $schSql = "SELECT time_in, time_out FROM schedules WHERE id = ?";
                        $schStmt = $this->employeeModel->db->prepare($schSql);
                        $schStmt->execute([$currentScheduleId]);
                        $sch = $schStmt->fetch(\PDO::FETCH_ASSOC);

                        if ($sch) {
                            $needsUpdate = false;
                            $updateData = [];

                            // Si el schedule_id cambió o está vacío en el detalle, actualizar
                            if (empty($detail['schedule_id']) || (int)$detail['schedule_id'] !== (int)$currentScheduleId) {
                                $updateData['schedule_id'] = $currentScheduleId;
                                $needsUpdate = true;
                            }

                            // Si los horarios programados difieren o están vacíos, actualizar
                            if (empty($detail['scheduled_time_in']) || $detail['scheduled_time_in'] !== $sch['time_in']) {
                                $updateData['scheduled_time_in'] = $sch['time_in'];
                                $needsUpdate = true;
                            }
                            if (empty($detail['scheduled_time_out']) || $detail['scheduled_time_out'] !== $sch['time_out']) {
                                $updateData['scheduled_time_out'] = $sch['time_out'];
                                $needsUpdate = true;
                            }

                            if ($needsUpdate) {
                                $this->detailModel->update($detail['id'], $updateData);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    error_log("processDay: error syncing scheduled times for detail {$detail['id']}: " . $e->getMessage());
                }
            }

            // Verificar si el día es laboral según el calendario empresarial
            $isWorkingDay = true;
            $isPaidHoliday = false;
            try {
                $calendar = new \App\Models\BusinessCalendar();
                $dayInfo = $calendar->getDayInfo($date);

                if ($dayInfo) {
                    $isWorkingDay = ($dayInfo['day_type'] === 'LABORAL');
                    $isPaidHoliday = ($dayInfo['day_type'] === 'FERIADO' && isset($dayInfo['is_paid_holiday']) && $dayInfo['is_paid_holiday'] == 1);
                } else {
                    $dayOfWeek = date('N', strtotime($date));
                    $isWorkingDay = ($dayOfWeek >= 1 && $dayOfWeek <= 5);
                }
            } catch (\Exception $e) {
                error_log("Error checking business calendar: " . $e->getMessage());
                $dayOfWeek = date('N', strtotime($date));
                $isWorkingDay = ($dayOfWeek >= 1 && $dayOfWeek <= 5);
            }

            // Si el día no es laboral pero hay empleados con override personal,
            // esos empleados se tratan como día laboral para detección de ausencias.
            // $employeesWithPersonalOverride ya fue cargado al inicio del método.

            // 4.5. Generar registros automáticos para FERIADOS PAGADOS
            // Si el día es feriado pagado, generar registros con holiday_hours
            if ($isPaidHoliday) {
                error_log("PAID HOLIDAY PROCESSING: Iniciando procesamiento de feriado pagado para fecha {$date}");

                // Si se está recalculando, eliminar todos los registros existentes para este día
                // para regenerarlos correctamente como feriado pagado
                if ($recalculate && !empty($existingDetails)) {
                    error_log("PAID HOLIDAY: Eliminando " . count($existingDetails) . " registros existentes para regenerar como feriado pagado");

                    foreach ($existingDetails as $detail) {
                        // Eliminar cálculo asociado si existe
                        $calculationModel = new \App\Models\AttendanceCalculation();
                        $existingCalc = $calculationModel->findByAttendanceDetailId($detail['id']);
                        if ($existingCalc) {
                            $calculationModel->delete($existingCalc['id']);
                        }

                        // Eliminar detalle
                        $this->detailModel->delete($detail['id']);
                    }

                    // Limpiar array de empleados con asistencia para regenerar todos
                    $employeesWithAttendance = [];
                }

                $paidHolidayCount = 0;

                foreach ($activeEmployees as $employee) {
                    // Verificar si ya existe un registro para este empleado
                    if (isset($employeesWithAttendance[$employee['id']])) {
                        continue; // Ya tiene marcación, no crear registro automático
                    }

                    try {
                        // Obtener horario del empleado incluyendo almuerzo
                        $scheduleId = $employee['schedule_id'] ?? null;
                        $timeIn = '08:00:00';
                        $timeOut = '17:00:00';
                        $lunchOut = null;
                        $lunchIn = null;
                        $lunchMinutes = 0;

                        if ($scheduleId) {
                            $sql = "SELECT time_in, time_out, salida_almuerzo, entrada_almuerzo FROM schedules WHERE id = ?";
                            $stmt = $this->employeeModel->db->prepare($sql);
                            $stmt->execute([$scheduleId]);
                            $schedule = $stmt->fetch(\PDO::FETCH_ASSOC);

                            if ($schedule) {
                                $timeIn = $schedule['time_in'];
                                $timeOut = $schedule['time_out'];
                                $lunchOut = $schedule['salida_almuerzo'] ?? null;
                                $lunchIn = $schedule['entrada_almuerzo'] ?? null;

                                // Calcular minutos de almuerzo si están definidos
                                if ($lunchOut && $lunchIn) {
                                    $lunchStart = new \DateTime($lunchOut);
                                    $lunchEnd = new \DateTime($lunchIn);
                                    $lunchInterval = $lunchStart->diff($lunchEnd);
                                    $lunchMinutes = ($lunchInterval->h * 60) + $lunchInterval->i;
                                }
                            }
                        }

                        // Crear registro de asistencia (hours_worked = 0, se guardan en holiday_hours)
                        $paidHolidayData = [
                            'header_id' => $header['id'],
                            'employee_id' => $employee['id'],
                            'schedule_id' => $scheduleId,
                            'time_in' => $timeIn,
                            'time_out' => $timeOut,
                            'lunch_out' => $lunchOut ? date('Y-m-d', strtotime($date)) . ' ' . $lunchOut : null,
                            'lunch_in' => $lunchIn ? date('Y-m-d', strtotime($date)) . ' ' . $lunchIn : null,
                            'status' => 'PRESENT',
                            'is_late' => 0,
                            'tardiness_minutes' => 0,
                            'hours_worked' => 0, // Las horas se guardan en holiday_hours (attendance_calculations)
                            'notes' => 'Feriado Pagado - ' . ($dayInfo['description'] ?? 'Día Feriado')
                        ];

                        $detailId = $this->detailModel->create($paidHolidayData);

                        if ($detailId) {
                            // Calcular horas netas (descontando almuerzo)
                            $timeInObj = new \DateTime($timeIn);
                            $timeOutObj = new \DateTime($timeOut);
                            $interval = $timeInObj->diff($timeOutObj);
                            $totalMinutes = ($interval->h * 60) + $interval->i;

                            // Descontar almuerzo
                            $netMinutes = $totalMinutes - $lunchMinutes;
                            $holidayHours = round($netMinutes / 60, 2);

                            $calculationData = [
                                'attendance_detail_id' => $detailId,
                                'employee_id' => $employee['id'],
                                'date' => $date,
                                'schedule_id' => $scheduleId,
                                'time_in' => $timeIn,
                                'time_out' => $timeOut,
                                'scheduled_time_in' => $timeIn,
                                'scheduled_time_out' => $timeOut,
                                'total_hours' => $holidayHours,
                                'regular_hours' => 0,
                                'overtime_hours' => 0,
                                'overtime_25_hours' => 0,
                                'overtime_50_hours' => 0,
                                'night_hours' => 0,
                                'holiday_hours' => $holidayHours,
                                'tardiness_minutes' => 0,
                                'is_late' => 0,
                                'early_departure_minutes' => 0,
                                'is_absent' => 0,
                                'absence_type' => null,
                                'is_working_day' => 0,
                                'is_holiday' => 1,
                                'is_weekend' => 0,
                                'day_type' => 'FERIADO',
                                'lunch_time_minutes' => $lunchMinutes,
                                'is_perfect_attendance' => 1,
                                'punctuality_score' => 100,
                                'notes' => 'Feriado Pagado - ' . ($dayInfo['description'] ?? 'Día Feriado'),
                                'calculation_version' => 'v1.0',
                                'calculated_at' => date('Y-m-d H:i:s')
                            ];

                            $calculationModel = new \App\Models\AttendanceCalculation();
                            $calculationModel->create($calculationData);

                            $paidHolidayCount++;
                            error_log("Feriado pagado generado para empleado {$employee['id']} en fecha {$date} - {$holidayHours} horas en holiday_hours");
                        }
                    } catch (Exception $e) {
                        error_log("ERROR processDay - Error creando feriado pagado para empleado {$employee['id']}: " . $e->getMessage());
                    }
                }

                error_log("PAID HOLIDAY: Generados {$paidHolidayCount} registros de feriado pagado para fecha {$date}");

                // Actualizar estadísticas del header y retornar
                // En feriados pagados NO se procesan omisiones ni se recalcula nada más
                $finalDetails = $this->detailModel->getByHeader($header['id']);
                $this->headerModel->update($header['id'], [
                    'total_employees' => count($finalDetails),
                    'total_present' => count($finalDetails),
                    'total_late' => 0,
                    'total_absent' => 0,
                    'total_incomplete' => 0
                ]);

                return [
                    'success' => true,
                    'message' => "Feriado pagado procesado: {$paidHolidayCount} registros generados",
                    'data' => [
                        'records_found' => 0,
                        'details_updated' => 0,
                        'absences_detected' => 0,
                        'absences_created' => 0,
                        'omissions_detected' => 0,
                        'omissions_marked' => 0,
                        'calculations_processed' => 0,
                        'calculations_saved' => 0,
                        'calculations_errors' => 0,
                        'total_employees' => count($finalDetails),
                        'paid_holidays_generated' => $paidHolidayCount
                    ]
                ];
            }
            // 5. Detectar ausencias por empleado/día.
            // Por cada empleado activo sin marcación:
            //   a) Limpia su detail ABSENT auto-generado previo (si quedó basura
            //      porque antes el calendario empresarial no estaba inicializado).
            //   b) Decide si debe crear ABSENT según la regla:
            //      - tiene override personal ese día → sí
            //      - el día es LABORAL en business_calendar → sí
            //      - cualquier otro caso (domingo, FERIADO, NO_LABORAL) → no
            elseif ($detectAbsences) {
                foreach ($activeEmployees as $employee) {
                    if (isset($employeesWithAttendance[$employee['id']])) {
                        continue; // ya tiene marcación
                    }

                    $hasOverride = in_array((int)$employee['id'], $employeesWithPersonalOverride);

                    try {
                        // (a) Limpieza idempotente del ABSENT auto previo
                        $this->detailModel->deleteAutoAbsenceByEmployeeAndHeader($header['id'], $employee['id']);

                        // (b) ¿Aplica ausencia para este empleado en este día?
                        if (!\App\Models\BusinessCalendar::shouldMarkAbsence($dayInfo ?? null, $date, $hasOverride)) {
                            continue;
                        }

                        $stats['absences_detected']++;

                        // Resolver schedule (override personal si aplica, sino el base)
                        $scheduleId = $employee['schedule_id'] ?? null;
                        if ($hasOverride) {
                            $overrideRecord = $dailyScheduleModel->getForDate((int)$employee['id'], $date);
                            if ($overrideRecord) {
                                $scheduleId = $overrideRecord['schedule_id'];
                            }
                        }

                        $absenceData = [
                            'header_id'         => $header['id'],
                            'employee_id'       => $employee['id'],
                            'schedule_id'       => $scheduleId,
                            'time_in'           => null,
                            'time_out'          => null,
                            'status'            => 'ABSENT',
                            'is_late'           => 0,
                            'tardiness_minutes' => 0,
                            'hours_worked'      => 0,
                            'notes'             => 'Ausencia detectada automáticamente - Sin marcación'
                        ];

                        $detailId = $this->detailModel->create($absenceData);

                        if ($detailId) {
                            $stats['absences_created']++;
                        }
                    } catch (Exception $e) {
                        error_log("ERROR processDay - Error processing absence for employee {$employee['id']}: " . $e->getMessage());
                    }
                }
            }

            // Recargar detalles después de crear ausencias
            $existingDetails = $this->detailModel->getByHeader($header['id']);

            // 6. Detectar marcaciones INCOMPLETAS y marcarlas como OMISIÓN
            if ($markOmissions) {
                foreach ($existingDetails as $detail) {
                    $hasTimeIn = !empty($detail['time_in']);
                    $hasTimeOut = !empty($detail['time_out']);

                    if (($hasTimeIn && !$hasTimeOut) || (!$hasTimeIn && $hasTimeOut)) {
                        $stats['omissions_detected']++;

                        try {
                            $this->detailModel->update($detail['id'], [
                                'status' => 'INCOMPLETE',
                                'notes' => 'Omisión de marcación: ' . ($hasTimeIn ? 'Falta salida' : 'Falta entrada')
                            ]);

                            $stats['omissions_marked']++;
                        } catch (Exception $e) {
                            error_log("Error marking omission for detail {$detail['id']}: " . $e->getMessage());
                        }
                    }
                }
            }

            // 7. Calcular métricas para marcaciones COMPLETAS
            // Excluir registros de feriados pagados (ya tienen su cálculo manual)
            $completeAttendances = [];
            foreach ($existingDetails as $detail) {
                // Saltar si es un registro de feriado pagado (ya tiene cálculo manual)
                $isFeriado = (strpos($detail['notes'] ?? '', 'Feriado Pagado') !== false);

                if ($isFeriado) {
                    error_log("SKIPPING recalculation for paid holiday record: employee_id={$detail['employee_id']}, date={$date}");
                    continue;
                }

                if (!empty($detail['time_in']) && !empty($detail['time_out'])) {
                    $completeAttendances[] = [
                        'id' => $detail['id'],
                        'employee_id' => $detail['employee_id'],
                        'date' => $date,
                        'time_in' => $detail['time_in'],
                        'time_out' => $detail['time_out'],
                        'lunch_out' => $detail['lunch_out'] ?? null,
                        'lunch_in' => $detail['lunch_in'] ?? null
                    ];
                }
            }

            if ($recalculate && !empty($completeAttendances)) {
                $calcResult = $this->attendanceCalculator->calculateAndSaveBulk($completeAttendances, true);

                $stats['calculations_processed'] = $calcResult['stats']['total_processed'];
                $stats['calculations_saved'] = $calcResult['stats']['saved'];
                $stats['calculations_errors'] = $calcResult['stats']['errors'];

                // Actualizar estados en attendance_detail
                foreach ($calcResult['calculations'] as $calculation) {
                    $newStatus = 'PRESENT';
                    if ($calculation['is_absent'] == 1) {
                        $newStatus = 'ABSENT';
                    } elseif (empty($calculation['time_out'])) {
                        $newStatus = 'INCOMPLETE';
                    }

                    $this->detailModel->update($calculation['attendance_detail_id'], [
                        'status' => $newStatus,
                        'hours_worked' => $calculation['total_hours'],
                        'is_late' => $calculation['is_late'],
                        'tardiness_minutes' => $calculation['tardiness_minutes']
                    ]);
                }
            }

            // 8. Actualizar estadísticas del header
            $finalDetails = $this->detailModel->getByHeader($header['id']);

            $totalOnTime = 0;
            $totalLate = 0;
            $totalAbsent = 0;

            foreach ($finalDetails as $detail) {
                switch ($detail['status']) {
                    case 'PRESENT':
                        if ($detail['is_late']) {
                            $totalLate++;
                        } else {
                            $totalOnTime++;
                        }
                        break;
                    case 'LATE':
                        $totalLate++;
                        break;
                    case 'ABSENT':
                        $totalAbsent++;
                        break;
                }
            }

            $this->headerModel->update($header['id'], [
                'total_records' => count($finalDetails),
                'total_employees' => count($finalDetails),
                'total_on_time' => $totalOnTime,
                'total_late' => $totalLate,
                'total_absent' => $totalAbsent,
                'is_processed' => 1,
                'processed_at' => date('Y-m-d H:i:s')
            ]);

            return [
                'success' => true,
                'message' => 'Procesamiento completado exitosamente.',
                'data' => $stats
            ];

        } catch (Exception $e) {
            error_log("Error processing single day {$date}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al procesar el día: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Procesar día completo: detectar ausencias, omisiones y calcular métricas (AJAX)
     * Proceso integral que:
     * 1. Busca registros en attendance_records y reconstruye attendance_detail
     * 2. Detecta empleados sin marcación y crea registros de AUSENCIA
     * 3. Detecta marcaciones incompletas (solo entrada o salida) y las marca como OMISIÓN
     * 4. Calcula métricas para marcaciones completas
     *
     * @return array JSON response
     */
    public function processDay()
    {
        $date = $_POST['date'] ?? null;

        // Opciones de procesamiento desde el formulario
        $processRecords = isset($_POST['process_records']) ? (int)$_POST['process_records'] : 1;
        $detectAbsences = isset($_POST['detect_absences']) ? (int)$_POST['detect_absences'] : 1;
        $markOmissions = isset($_POST['mark_omissions']) ? (int)$_POST['mark_omissions'] : 1;
        $recalculate = isset($_POST['recalculate']) ? (int)$_POST['recalculate'] : 1;

        // Llamar al método reutilizable
        $result = $this->processSingleDay($date, $processRecords, $detectAbsences, $markOmissions, $recalculate);

        // Retornar JSON response
        return $this->jsonResponse($result);
    }

    // ========================================
    // MÉTODOS DE REPORTES DETALLADOS
    // ========================================

    /**
     * Vista principal de reportes de asistencias
     * Permite seleccionar tipo de reporte y período
     */
    public function reports()
    {
        // Obtener tipos de planilla para filtro
        $sql = "SELECT id, descripcion FROM tipos_planilla WHERE activo = 1 ORDER BY descripcion";
        $stmt = $this->employeeModel->db->prepare($sql);
        $stmt->execute();
        $tiposPlanilla = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $data = [
            'title' => 'Reportes de Asistencias',
            'page_title' => 'Generador de Reportes',
            'tipos_planilla' => $tiposPlanilla,
            'csrf_token' => Security::generateToken()
        ];

        $this->render('admin/attendance/reports/index', $data);
    }

    /**
     * Generar reporte de ausencias (AJAX o vista)
     */
    public function absencesReport()
    {
        try {
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $tipoPlanillaId = $_GET['tipo_planilla_id'] ?? null;
            $format = $_GET['format'] ?? 'view'; // view, json, excel, pdf

            if (!$startDate || !$endDate) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Debe especificar fecha de inicio y fin.'
                ]);
            }

            // Generar reporte
            require_once __DIR__ . '/../Services/Attendance/ReportsGenerator.php';
            $generator = new \App\Services\Attendance\ReportsGenerator();
            $report = $generator->generateDetailedAbsencesReport($startDate, $endDate, $tipoPlanillaId);

            if (isset($report['error'])) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => $report['message']
                ]);
            }

            // Responder según formato solicitado
            switch ($format) {
                case 'json':
                    return $this->jsonResponse([
                        'success' => true,
                        'data' => $report
                    ]);

                case 'view':
                    // Renderizar vista
                    $data = [
                        'title' => 'Reporte de Ausencias',
                        'page_title' => 'Reporte Detallado de Ausencias',
                        'report' => $report,
                        'csrf_token' => Security::generateToken()
                    ];
                    $this->render('admin/attendance/reports/absences', $data);
                    break;

                case 'excel':
                    // Exportar a Excel
                    require_once __DIR__ . '/../Services/Attendance/ExcelExporter.php';
                    $exporter = new \App\Services\Attendance\ExcelExporter();
                    $exporter->exportAbsencesReport($report, 'Reporte_Ausencias');
                    exit; // El exporter ya envía los headers y el archivo
                    break;

                case 'pdf':
                    // TODO: Implementar exportación PDF
                    $this->setFlashMessage('Exportación a PDF - En desarrollo', 'info');
                    $this->redirect('/panel/attendance/reports');
                    break;

                default:
                    return $this->jsonResponse([
                        'success' => false,
                        'message' => 'Formato no soportado'
                    ]);
            }

        } catch (Exception $e) {
            error_log("Error generating absences report: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error al generar reporte: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Generar reporte de tardanzas (AJAX o vista)
     */
    public function tardinessReport()
    {
        try {
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $tipoPlanillaId = $_GET['tipo_planilla_id'] ?? null;
            $minMinutes = $_GET['min_minutes'] ?? 1;
            $format = $_GET['format'] ?? 'view'; // view, json, excel, pdf

            if (!$startDate || !$endDate) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Debe especificar fecha de inicio y fin.'
                ]);
            }

            // Generar reporte
            require_once __DIR__ . '/../Services/Attendance/ReportsGenerator.php';
            $generator = new \App\Services\Attendance\ReportsGenerator();
            $report = $generator->generateDetailedTardinessReport($startDate, $endDate, $tipoPlanillaId, $minMinutes);

            if (isset($report['error'])) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => $report['message']
                ]);
            }

            // Responder según formato solicitado
            switch ($format) {
                case 'json':
                    return $this->jsonResponse([
                        'success' => true,
                        'data' => $report
                    ]);

                case 'view':
                    // Renderizar vista
                    $data = [
                        'title' => 'Reporte de Tardanzas',
                        'page_title' => 'Reporte Detallado de Tardanzas',
                        'report' => $report,
                        'csrf_token' => Security::generateToken()
                    ];
                    $this->render('admin/attendance/reports/tardiness', $data);
                    break;

                case 'excel':
                    // Exportar a Excel
                    require_once __DIR__ . '/../Services/Attendance/ExcelExporter.php';
                    $exporter = new \App\Services\Attendance\ExcelExporter();
                    $exporter->exportTardinessReport($report, 'Reporte_Tardanzas');
                    exit; // El exporter ya envía los headers y el archivo
                    break;

                case 'pdf':
                    // TODO: Implementar exportación PDF
                    $this->setFlashMessage('Exportación a PDF - En desarrollo', 'info');
                    $this->redirect('/panel/attendance/reports');
                    break;

                default:
                    return $this->jsonResponse([
                        'success' => false,
                        'message' => 'Formato no soportado'
                    ]);
            }

        } catch (Exception $e) {
            error_log("Error generating tardiness report: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error al generar reporte: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Generar reporte combinado de ausencias y tardanzas (AJAX o vista)
     */
    public function combinedReport()
    {
        try {
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $tipoPlanillaId = $_GET['tipo_planilla_id'] ?? null;
            $format = $_GET['format'] ?? 'view'; // view, json, excel, pdf

            if (!$startDate || !$endDate) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Debe especificar fecha de inicio y fin.'
                ]);
            }

            // Generar reporte
            require_once __DIR__ . '/../Services/Attendance/ReportsGenerator.php';
            $generator = new \App\Services\Attendance\ReportsGenerator();
            $report = $generator->generateCombinedAttendanceReport($startDate, $endDate, $tipoPlanillaId);

            if (isset($report['error'])) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => $report['message']
                ]);
            }

            // Responder según formato solicitado
            switch ($format) {
                case 'json':
                    return $this->jsonResponse([
                        'success' => true,
                        'data' => $report
                    ]);

                case 'view':
                    // Renderizar vista
                    $data = [
                        'title' => 'Reporte Combinado de Asistencias',
                        'page_title' => 'Reporte Combinado: Ausencias y Tardanzas',
                        'report' => $report,
                        'csrf_token' => Security::generateToken()
                    ];
                    $this->render('admin/attendance/reports/combined', $data);
                    break;

                case 'excel':
                    // Exportar a Excel
                    require_once __DIR__ . '/../Services/Attendance/ExcelExporter.php';
                    $exporter = new \App\Services\Attendance\ExcelExporter();
                    $exporter->exportCombinedReport($report, 'Reporte_Combinado');
                    exit; // El exporter ya envía los headers y el archivo
                    break;

                case 'pdf':
                    // TODO: Implementar exportación PDF
                    $this->setFlashMessage('Exportación a PDF - En desarrollo', 'info');
                    $this->redirect('/panel/attendance/reports');
                    break;

                default:
                    return $this->jsonResponse([
                        'success' => false,
                        'message' => 'Formato no soportado'
                    ]);
            }

        } catch (Exception $e) {
            error_log("Error generating combined report: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error al generar reporte: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Generar reporte de marcaciones/punches (AJAX o vista)
     */
    public function punchesReport()
    {
        try {
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $tipoPlanillaId = $_GET['tipo_planilla_id'] ?? null;
            $format = $_GET['format'] ?? 'view'; // view, json, excel, pdf

            if (!$startDate || !$endDate) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Debe especificar fecha de inicio y fin.'
                ]);
            }

            // Generar reporte
            require_once __DIR__ . '/../Services/Attendance/ReportsGenerator.php';
            $generator = new \App\Services\Attendance\ReportsGenerator();
            $report = $generator->generateDetailedPunchesReport($startDate, $endDate, $tipoPlanillaId);

            if (isset($report['error'])) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => $report['message']
                ]);
            }

            // Responder según formato solicitado
            switch ($format) {
                case 'json':
                    return $this->jsonResponse([
                        'success' => true,
                        'data' => $report
                    ]);

                case 'view':
                    // Renderizar vista
                    $data = [
                        'title' => 'Reporte de Marcaciones',
                        'page_title' => 'Reporte Detallado de Marcaciones',
                        'report' => $report,
                        'csrf_token' => Security::generateToken()
                    ];
                    $this->render('admin/attendance/reports/punches', $data);
                    break;

                case 'excel':
                    // Exportar a Excel
                    require_once __DIR__ . '/../Services/Attendance/ExcelExporter.php';
                    $exporter = new \App\Services\Attendance\ExcelExporter();
                    $exporter->exportPunchesReport($report, 'Reporte_Marcaciones');
                    exit; // El exporter ya envía los headers y el archivo
                    break;

                case 'pdf':
                    // TODO: Implementar exportación PDF
                    $this->setFlashMessage('Exportación a PDF - En desarrollo', 'info');
                    $this->redirect('/panel/attendance/reports');
                    break;

                default:
                    return $this->jsonResponse([
                        'success' => false,
                        'message' => 'Formato no soportado'
                    ]);
            }

        } catch (Exception $e) {
            error_log("Error generating punches report: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error al generar reporte: ' . $e->getMessage()
            ]);
        }
    }

    // ========================================
    // MÉTODOS DE ALERTAS LEGALES
    // ========================================

    /**
     * Vista dashboard de alertas
     * Dashboard visual con estadísticas y gestión de alertas
     */
    public function alertsDashboard()
    {
        try {
            // Importar AlertsSystem
            require_once __DIR__ . '/../Services/Attendance/AlertsSystem.php';
            $alertsSystem = new \App\Services\Attendance\AlertsSystem();

            // Obtener resumen global
            $summary = $alertsSystem->getGlobalAlertsSummary();

            // Obtener alertas críticas
            $criticalAlerts = $alertsSystem->getCriticalAlerts(10);

            // Obtener todas las alertas activas con filtros
            $severity = $_GET['severity'] ?? null;
            $alertType = $_GET['alert_type'] ?? null;
            $employeeId = $_GET['employee_id'] ?? null;

            // Query base para alertas activas
            $sql = "SELECT aa.*,
                           CONCAT(e.firstname, ' ', e.lastname) as employee_name,
                           e.employee_id as employee_code,
                           d.name as department_name
                    FROM attendance_alerts aa
                    INNER JOIN employees e ON aa.employee_id = e.id
                    LEFT JOIN departments d ON e.department_id = d.id
                    WHERE aa.status IN ('PENDING', 'ACKNOWLEDGED')
                    AND aa.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";

            $params = [];

            if ($severity) {
                $sql .= " AND aa.severity = ?";
                $params[] = $severity;
            }

            if ($alertType) {
                $sql .= " AND aa.alert_type = ?";
                $params[] = $alertType;
            }

            if ($employeeId) {
                $sql .= " AND aa.employee_id = ?";
                $params[] = $employeeId;
            }

            $sql .= " ORDER BY
                      FIELD(aa.severity, 'CRITICAL', 'WARNING', 'INFO'),
                      FIELD(aa.status, 'PENDING', 'ACKNOWLEDGED'),
                      aa.date DESC
                      LIMIT 100";

            $stmt = $this->employeeModel->db->prepare($sql);
            $stmt->execute($params);
            $alerts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Decodificar metadata JSON
            foreach ($alerts as &$alert) {
                if (!empty($alert['metadata'])) {
                    $alert['metadata'] = json_decode($alert['metadata'], true);
                }
            }

            // Obtener estadísticas por tipo de alerta
            $sqlTypes = "SELECT alert_type,
                                COUNT(*) as count,
                                SUM(CASE WHEN severity = 'CRITICAL' THEN 1 ELSE 0 END) as critical_count
                         FROM attendance_alerts
                         WHERE status IN ('PENDING', 'ACKNOWLEDGED')
                         AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                         GROUP BY alert_type
                         ORDER BY count DESC
                         LIMIT 10";
            $stmtTypes = $this->employeeModel->db->prepare($sqlTypes);
            $stmtTypes->execute();
            $alertsByType = $stmtTypes->fetchAll(\PDO::FETCH_ASSOC);

            // Obtener empleados para filtro
            $employees = $this->employeeModel->all();
            $activeEmployees = array_filter($employees, function($emp) {
                return $emp['situacion_id'] == 1;
            });

            $data = [
                'title' => 'Dashboard de Alertas',
                'page_title' => 'Dashboard de Alertas Legales',
                'summary' => $summary,
                'critical_alerts' => $criticalAlerts,
                'alerts' => $alerts,
                'alerts_by_type' => $alertsByType,
                'employees' => $activeEmployees,
                'filters' => [
                    'severity' => $severity,
                    'alert_type' => $alertType,
                    'employee_id' => $employeeId
                ],
                'csrf_token' => Security::generateToken()
            ];

            $this->render('admin/attendance/alerts/dashboard', $data);

        } catch (Exception $e) {
            error_log("Error in alertsDashboard: " . $e->getMessage());
            $this->setFlashMessage('Error al cargar dashboard de alertas: ' . $e->getMessage(), 'error');
            $this->redirect('/panel/attendance');
        }
    }

    /**
     * Reconocer una alerta (AJAX)
     */
    public function acknowledgeAlert($alertId)
    {
        try {
            require_once __DIR__ . '/../Services/Attendance/AlertsSystem.php';
            $alertsSystem = new \App\Services\Attendance\AlertsSystem();

            $notes = $_POST['notes'] ?? null;
            $userId = $_SESSION['user_id'] ?? 1;

            $result = $alertsSystem->acknowledgeAlert($alertId, $userId, $notes);

            if ($result) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Alerta reconocida exitosamente.'
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'No se pudo reconocer la alerta.'
                ]);
            }

        } catch (Exception $e) {
            error_log("Error acknowledging alert: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Resolver una alerta (AJAX)
     */
    public function resolveAlert($alertId)
    {
        try {
            require_once __DIR__ . '/../Services/Attendance/AlertsSystem.php';
            $alertsSystem = new \App\Services\Attendance\AlertsSystem();

            $notes = $_POST['notes'] ?? null;

            $result = $alertsSystem->resolveAlert($alertId, $notes);

            if ($result) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Alerta resuelta exitosamente.'
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'No se pudo resolver la alerta.'
                ]);
            }

        } catch (Exception $e) {
            error_log("Error resolving alert: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Descartar una alerta (AJAX)
     */
    public function dismissAlert($alertId)
    {
        try {
            require_once __DIR__ . '/../Services/Attendance/AlertsSystem.php';
            $alertsSystem = new \App\Services\Attendance\AlertsSystem();

            $reason = $_POST['reason'] ?? null;

            $result = $alertsSystem->dismissAlert($alertId, $reason);

            if ($result) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Alerta descartada exitosamente.'
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'No se pudo descartar la alerta.'
                ]);
            }

        } catch (Exception $e) {
            error_log("Error dismissing alert: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    // ========================================
    // MÉTODOS DE ATTENDANCE_RECORDS (Nueva Capa Intermedia)
    // ========================================

    /**
     * Obtener estadísticas de attendance_records sin procesar (AJAX)
     * @return array JSON response
     */
    public function recordsStats()
    {
        try {
            $dateFrom = $_GET['date_from'] ?? null;
            $dateTo = $_GET['date_to'] ?? null;

            // Estadísticas generales
            $totalUnprocessed = $this->recordModel->count(['is_processed' => 0, 'is_duplicate' => 0]);
            $totalDuplicates = $this->recordModel->count(['is_duplicate' => 1]);

            // Estadísticas por fecha
            $statsByDate = $this->recordModel->getStatsByDate($dateFrom, $dateTo);

            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'total_unprocessed' => $totalUnprocessed,
                    'total_duplicates' => $totalDuplicates,
                    'stats_by_date' => $statsByDate
                ]
            ]);

        } catch (Exception $e) {
            error_log("Error getting records stats: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Procesar records a details (AJAX)
     * Consolida marcaciones de attendance_records → attendance_detail
     * @return array JSON response
     */
    public function processRecords()
    {
        try {
            $dateFrom = $_POST['date_from'] ?? null;
            $dateTo = $_POST['date_to'] ?? null;

            if (!$dateFrom || !$dateTo) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Debe especificar fecha de inicio y fin.'
                ]);
            }

            // Procesar records usando RecordsProcessor
            $result = $this->recordsProcessor->processToDetails($dateFrom, $dateTo);

            return $this->jsonResponse([
                'success' => true,
                'message' => "Procesamiento completado. Grupos: {$result['groups_processed']}, Creados: {$result['details_created']}, Actualizados: {$result['details_updated']}, Errores: {$result['errors']}",
                'data' => $result
            ]);

        } catch (Exception $e) {
            error_log("Error processing records: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error al procesar records: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Procesar records pendientes hasta hoy (AJAX)
     * @return array JSON response
     */
    public function processRecordsUpToToday()
    {
        try {
            $result = $this->recordsProcessor->processUpToDate();

            return $this->jsonResponse([
                'success' => true,
                'message' => "Procesamiento completado. Grupos: {$result['groups_processed']}, Creados: {$result['details_created']}, Actualizados: {$result['details_updated']}",
                'data' => $result
            ]);

        } catch (Exception $e) {
            error_log("Error processing records up to today: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Reprocesar un día completo (AJAX)
     * Elimina details existentes y vuelve a procesar desde records
     * @return array JSON response
     */
    public function reprocessDayRecords()
    {
        try {
            $date = $_POST['date'] ?? null;

            if (!$date) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Debe especificar una fecha.'
                ]);
            }

            // Reprocesar día
            $result = $this->recordsProcessor->reprocessDay($date);

            return $this->jsonResponse([
                'success' => true,
                'message' => "Día reprocesado exitosamente. Creados: {$result['details_created']}, Actualizados: {$result['details_updated']}",
                'data' => $result
            ]);

        } catch (Exception $e) {
            error_log("Error reprocessing day: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error al reprocesar día: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Ver registros duplicados (AJAX)
     * @return array JSON response
     */
    public function viewDuplicateRecords()
    {
        try {
            $duplicates = $this->recordModel->getDuplicates();

            return $this->jsonResponse([
                'success' => true,
                'total' => count($duplicates),
                'data' => $duplicates
            ]);

        } catch (Exception $e) {
            error_log("Error viewing duplicates: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Detectar duplicados manualmente (AJAX)
     * @return array JSON response
     */
    public function detectDuplicates()
    {
        try {
            $duplicatesFound = $this->recordModel->detectDuplicates();

            return $this->jsonResponse([
                'success' => true,
                'message' => "Se detectaron {$duplicatesFound} duplicados.",
                'duplicates_found' => $duplicatesFound
            ]);

        } catch (Exception $e) {
            error_log("Error detecting duplicates: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Ver registros sin procesar agrupados (AJAX)
     * @return array JSON response
     */
    public function viewUnprocessedRecords()
    {
        try {
            $dateFrom = $_GET['date_from'] ?? null;
            $dateTo = $_GET['date_to'] ?? null;

            $groupedRecords = $this->recordModel->getGroupedByEmployeeAndDate($dateFrom, $dateTo);

            return $this->jsonResponse([
                'success' => true,
                'total_groups' => count($groupedRecords),
                'data' => $groupedRecords
            ]);

        } catch (Exception $e) {
            error_log("Error viewing unprocessed records: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    protected function requireAuth()
    {
        AuthMiddleware::requireAuth();
    }
}
