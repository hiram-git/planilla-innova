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
use App\Models\Employee;
use App\Services\Attendance\Calculators\AttendanceCalculator;
use App\Services\Attendance\Calculators\AbsenceDetector;
use App\Services\Attendance\Calculators\WorkScheduleResolver;
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

    // Calculadores
    private $attendanceCalculator;
    private $absenceDetector;
    private $scheduleResolver;

    public function __construct()
    {
        $this->requireAuth();
        $this->attendanceModel = new Attendance();
        $this->headerModel = new AttendanceHeader();
        $this->detailModel = new AttendanceDetail();
        $this->syncLogModel = new AttendanceSyncLog();
        $this->deviceModel = new AttendanceDevice();
        $this->employeeModel = new Employee();

        // Inicializar calculadores
        $this->attendanceCalculator = new AttendanceCalculator();
        $this->absenceDetector = new AbsenceDetector();
        $this->scheduleResolver = new WorkScheduleResolver();
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

        $data = [
            'title' => 'Sincronización de Marcaciones',
            'page_title' => 'Control de Sincronización',
            'api_devices' => $apiDevices,
            'file_devices' => $fileDevices,
            'recent_syncs' => $recentSyncs,
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
     */
    public function syncNow()
    {
        try {
            // TODO: Implementar lógica de sincronización con API
            // Por ahora retornar mensaje de desarrollo

            return $this->jsonResponse([
                'success' => false,
                'message' => 'Sincronización manual - En desarrollo. Próximamente se integrará con AttendanceSyncService.'
            ]);

        } catch (Exception $e) {
            error_log("Error in syncNow: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
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
                'notes' => $_POST['notes'] ?? null
            ];

            $result = $this->detailModel->update($id, $data);

            if ($result) {
                // Recalcular automáticamente si tiene time_in y time_out
                $calculationData = null;
                if ($data['time_in'] && $data['time_out']) {
                    try {
                        // Obtener el detalle actualizado
                        $updatedDetail = $this->detailModel->getById($id);

                        // Preparar datos para calculador
                        $attendanceData = [
                            'id' => $updatedDetail['id'],
                            'employee_id' => $updatedDetail['employee_id'],
                            'date' => $updatedDetail['date'],
                            'time_in' => $updatedDetail['time_in'],
                            'time_out' => $updatedDetail['time_out']
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

    /**
     * Helper method para obtener detalle de marcación por ID (usado internamente)
     */
    private function getById($id)
    {
        $sql = "SELECT d.*, h.attendance_date, e.firstname, e.lastname
                FROM attendance_detail d
                INNER JOIN attendance_header h ON d.header_id = h.id
                INNER JOIN employees e ON d.employee_id = e.id
                WHERE d.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
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

    protected function requireAuth()
    {
        AuthMiddleware::requireAuth();
    }
}
