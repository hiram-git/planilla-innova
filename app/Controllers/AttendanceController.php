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

            $data = [
                'time_in' => $_POST['time_in'] ?? null,
                'time_out' => $_POST['time_out'] ?? null,
                'lunch_out' => $_POST['lunch_out'] ?? null,
                'lunch_in' => $_POST['lunch_in'] ?? null,
                'notes' => $_POST['notes'] ?? null
            ];

            $result = $this->detailModel->update($id, $data);

            if ($result) {
                // Recalcular automáticamente si tiene time_in y time_out
                $calculationData = null;
                // Recalcular si hay cambios en horas de entrada/salida o almuerzo
                if (($data['time_in'] && $data['time_out']) || ($data['lunch_out'] || $data['lunch_in'])) {
                    try {
                        // Obtener el detalle actualizado
                        $updatedDetail = $this->detailModel->getById($id);

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

            $result = $this->detailModel->delete($id);

            if ($result) {
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
            $data = [
                'justification_type' => $_POST['justification_type'] ?? 'OTHER',
                'justification_notes' => $_POST['justification_notes'] ?? null,
                'justification_document' => $_POST['justification_document'] ?? null
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

            // Preparar datos para el calculador
            // El calculador espera un formato de registro de attendance
            $attendanceData = [
                'id' => $detail['id'],
                'employee_id' => $detail['employee_id'],
                'date' => $detail['date'],
                'time_in' => $detail['time_in'],
                'time_out' => $detail['time_out']
            ];

            // Calcular métricas
            $calculation = $this->attendanceCalculator->calculate($attendanceData);

            // Guardar en BD
            $calculationId = $this->attendanceCalculator->saveCalculation($calculation);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Cálculos procesados exitosamente.',
                'data' => [
                    'calculation_id' => $calculationId,
                    'total_hours' => $calculation['total_hours'],
                    'overtime_hours' => $calculation['overtime_hours'],
                    'is_late' => $calculation['is_late'],
                    'tardiness_minutes' => $calculation['tardiness_minutes'],
                    'punctuality_score' => $calculation['punctuality_score'],
                    'is_perfect_attendance' => $calculation['is_perfect_attendance']
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
                    'time_out' => $detail['time_out']
                ];
            }

            // Procesar en batch
            $result = $this->attendanceCalculator->calculateAndSaveBulk($attendances, true);

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
        try {
            $date = $_POST['date'] ?? null;
            $tipoPlanillaId = $_POST['tipo_planilla_id'] ?? null; // Solo para logging, no para filtrar

            if (!$date) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Debe especificar una fecha.'
                ]);
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
                    return $this->jsonResponse([
                        'success' => false,
                        'message' => 'Error al crear cabecera del día.'
                    ]);
                }

                $header = $this->headerModel->getById($headerId);
            }

            // Obtener set de empleados activos que marcan asistencia
            $markingEmployeeIds = $this->employeeModel->getActiveMarkingEmployeeIds();
            $allowedIds = array_flip($markingEmployeeIds); // set para lookup O(1)

            // Flags del checklist (UI)
            $processRecords = isset($_POST['process_records']) ? (int)$_POST['process_records'] : 1;
            $detectAbsences = isset($_POST['detect_absences']) ? (int)$_POST['detect_absences'] : 1;
            $markOmissions = isset($_POST['mark_omissions']) ? (int)$_POST['mark_omissions'] : 1;
            $recalculate = isset($_POST['recalculate']) ? (int)$_POST['recalculate'] : 1;

            // 2. Procesar registros attendance_records → attendance_detail con RecordsProcessor (marca records como procesados)
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
            // (El filtro de tipo de planilla sigue aplicando solo en la vista)
            $activeEmployees = $this->employeeModel->getActiveMarkingEmployees();
            $activeEmployees = array_values($activeEmployees); // Reindexar
            $stats['total_employees'] = count($activeEmployees);

            error_log("DEBUG processDay - Procesando TODOS los empleados activos (sin filtro tipo planilla)");
            error_log("DEBUG processDay - Total empleados activos: " . count($activeEmployees));
            error_log("DEBUG processDay - IDs empleados activos: " . implode(', ', array_column($activeEmployees, 'id')));

            if (empty($activeEmployees)) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'No hay empleados activos en el sistema.'
                ]);
            }

            // 4. Obtener detalles existentes después de actualizar desde records
            $existingDetails = $this->detailModel->getByHeader($header['id']);
            $employeesWithAttendance = [];

            error_log("DEBUG processDay - Detalles existentes después de actualizar desde records: " . count($existingDetails));

            foreach ($existingDetails as $detail) {
                $employeesWithAttendance[$detail['employee_id']] = true;

                // Actualizar horarios programados si están NULL
                if (empty($detail['scheduled_time_in']) || empty($detail['scheduled_time_out'])) {
                    if (!empty($detail['schedule_id'])) {
                        $sql = "SELECT time_in, time_out FROM schedules WHERE id = ?";
                        $stmt = $this->employeeModel->db->prepare($sql);
                        $stmt->execute([$detail['schedule_id']]);
                        $schedule = $stmt->fetch(\PDO::FETCH_ASSOC);

                        if ($schedule) {
                            $this->detailModel->update($detail['id'], [
                                'scheduled_time_in' => $schedule['time_in'],
                                'scheduled_time_out' => $schedule['time_out']
                            ]);
                            error_log("Updated scheduled times for detail ID {$detail['id']}");
                        }
                    }
                }
            }

            // 5. PASO 2: Detectar empleados SIN marcación y crear registros de AUSENCIA
            error_log("DEBUG processDay - Empleados con marcación: " . implode(', ', array_keys($employeesWithAttendance)));

            if ($detectAbsences) {
                foreach ($activeEmployees as $employee) {
                    if (!isset($employeesWithAttendance[$employee['id']])) {
                        $stats['absences_detected']++;
                        error_log("DEBUG processDay - Empleado SIN marcación detectado: ID={$employee['id']}, Nombre={$employee['firstname']} {$employee['lastname']}");

                    // Empleado sin marcación - crear registro de ausencia (si no existe)
                    try {
                        $absenceData = [
                            'header_id' => $header['id'],
                            'employee_id' => $employee['id'],
                            'schedule_id' => $employee['schedule_id'] ?? null,
                            'time_in' => null,
                            'time_out' => null,
                            'status' => 'ABSENT',
                            'is_late' => 0,
                            'tardiness_minutes' => 0,
                            'hours_worked' => 0,
                            'notes' => 'Ausencia detectada automáticamente - Sin marcación'
                        ];

                        error_log("DEBUG processDay - Intentando crear registro de ausencia para empleado {$employee['id']}");
                        $detailId = $this->detailModel->create($absenceData);

                        if ($detailId) {
                            $stats['absences_created']++;
                            error_log("DEBUG processDay - Registro de ausencia creado exitosamente: detail_id=$detailId");

                            // Registrar ausencia en el log
                            $this->absenceDetector->saveAbsence([
                                'employee_id' => $employee['id'],
                                'date' => $date,
                                'absence_type' => 'UNJUSTIFIED',
                                'attendance_detail_id' => $detailId,
                                'detected_at' => date('Y-m-d H:i:s')
                            ]);
                        } else {
                            error_log("DEBUG processDay - FALLO: create() retornó false para empleado {$employee['id']} - Posible violación UNIQUE (header_id, employee_id)");
                        }
                    } catch (Exception $e) {
                        error_log("ERROR processDay - Error creating absence for employee {$employee['id']}: " . $e->getMessage());
                        error_log("ERROR processDay - Stack trace: " . $e->getTraceAsString());
                    }
                    } else {
                        error_log("DEBUG processDay - Empleado CON marcación: ID={$employee['id']}");
                    }
                }
            }

            // Recargar detalles después de crear ausencias
            $existingDetails = $this->detailModel->getByHeader($header['id']);

            // 6. PASO 2: Detectar marcaciones INCOMPLETAS y marcarlas como OMISIÓN
            if ($markOmissions) {
                foreach ($existingDetails as $detail) {
                    $hasTimeIn = !empty($detail['time_in']);
                    $hasTimeOut = !empty($detail['time_out']);

                    // Marcación incompleta: solo entrada O solo salida
                    if (($hasTimeIn && !$hasTimeOut) || (!$hasTimeIn && $hasTimeOut)) {
                        $stats['omissions_detected']++;

                        try {
                            // Actualizar estado a INCOMPLETE (OMISIÓN)
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

            // 6. PASO 3: Calcular métricas para marcaciones COMPLETAS
            $completeAttendances = [];
            foreach ($existingDetails as $detail) {
                // Solo procesar si tiene entrada Y salida
                if (!empty($detail['time_in']) && !empty($detail['time_out'])) {
                    $completeAttendances[] = [
                        'id' => $detail['id'],
                        'employee_id' => $detail['employee_id'],
                        'date' => $date,
                        'time_in' => $detail['time_in'],
                        'time_out' => $detail['time_out']
                    ];
                }
            }

            if ($recalculate && !empty($completeAttendances)) {
                $calcResult = $this->attendanceCalculator->calculateAndSaveBulk($completeAttendances, true);

                $stats['calculations_processed'] = $calcResult['stats']['total_processed'];
                $stats['calculations_saved'] = $calcResult['stats']['saved'];
                $stats['calculations_errors'] = $calcResult['stats']['errors'];
            }

            // 7. Actualizar estadísticas del header
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

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Procesamiento completado exitosamente.',
                'data' => $stats
            ]);

        } catch (Exception $e) {
            error_log("Error processing day: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error al procesar el día: ' . $e->getMessage()
            ]);
        }
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
