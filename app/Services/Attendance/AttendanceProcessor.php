<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceCalculation;
use App\Models\Employee;
use App\Services\Attendance\Calculators\AttendanceCalculator;
use App\Services\Attendance\Calculators\AbsenceDetector;

/**
 * AttendanceProcessor
 *
 * Orquestador principal del procesamiento de asistencias.
 * Coordina cálculos, detección de ausencias y almacenamiento de resultados.
 *
 * @package App\Services\Attendance
 * @version 1.0
 */
class AttendanceProcessor
{
    private $attendanceModel;
    private $calculationModel;
    private $employeeModel;
    private $calculator;
    private $absenceDetector;

    public function __construct()
    {
        $this->attendanceModel = new Attendance();
        $this->calculationModel = new AttendanceCalculation();
        $this->employeeModel = new Employee();
        $this->calculator = new AttendanceCalculator();
        $this->absenceDetector = new AbsenceDetector();
    }

    /**
     * Procesar asistencias de un empleado en un rango de fechas
     *
     * @param int $employeeId
     * @param string $startDate
     * @param string $endDate
     * @return array Resultado del procesamiento
     */
    public function processEmployee($employeeId, $startDate, $endDate)
    {
        $processed = 0;
        $errors = 0;
        $skipped = 0;

        // Obtener asistencias del empleado
        $attendances = $this->attendanceModel->getAttendanceByDateRange(
            $startDate,
            $endDate,
            $employeeId
        );

        foreach ($attendances as $attendance) {
            try {
                // Calcular métricas
                $calculation = $this->calculator->calculate($attendance);

                // Guardar cálculo
                $saved = $this->calculationModel->saveCalculation($calculation);

                if ($saved) {
                    $processed++;
                } else {
                    $errors++;
                }

            } catch (\Exception $e) {
                error_log("Error processing attendance {$attendance['id']}: " . $e->getMessage());
                $errors++;
            }
        }

        return [
            'success' => true,
            'employee_id' => $employeeId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'processed' => $processed,
            'errors' => $errors,
            'skipped' => $skipped,
            'total' => count($attendances)
        ];
    }

    /**
     * Procesar asistencias de múltiples empleados
     *
     * @param array $employeeIds Array de IDs de empleados
     * @param string $startDate
     * @param string $endDate
     * @return array Resultado del procesamiento
     */
    public function processBulk($employeeIds, $startDate, $endDate)
    {
        $results = [];
        $totalProcessed = 0;
        $totalErrors = 0;

        foreach ($employeeIds as $employeeId) {
            $result = $this->processEmployee($employeeId, $startDate, $endDate);
            $results[] = $result;
            $totalProcessed += $result['processed'];
            $totalErrors += $result['errors'];
        }

        return [
            'success' => true,
            'total_employees' => count($employeeIds),
            'total_processed' => $totalProcessed,
            'total_errors' => $totalErrors,
            'details' => $results
        ];
    }

    /**
     * Procesar todas las asistencias de un día específico
     *
     * @param string $date Fecha en formato Y-m-d
     * @return array
     */
    public function processDate($date)
    {
        $attendances = $this->attendanceModel->getAttendanceByDateRange($date, $date);

        $processed = 0;
        $errors = 0;

        foreach ($attendances as $attendance) {
            try {
                $calculation = $this->calculator->calculate($attendance);
                $saved = $this->calculationModel->saveCalculation($calculation);

                if ($saved) {
                    $processed++;
                } else {
                    $errors++;
                }

            } catch (\Exception $e) {
                error_log("Error processing attendance {$attendance['id']}: " . $e->getMessage());
                $errors++;
            }
        }

        return [
            'success' => true,
            'date' => $date,
            'processed' => $processed,
            'errors' => $errors,
            'total' => count($attendances)
        ];
    }

    /**
     * Procesar un rango completo de fechas para todos los empleados
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function processPeriod($startDate, $endDate)
    {
        $attendances = $this->attendanceModel->getAttendanceByDateRange($startDate, $endDate);

        $processed = 0;
        $errors = 0;

        foreach ($attendances as $attendance) {
            try {
                $calculation = $this->calculator->calculate($attendance);
                $saved = $this->calculationModel->saveCalculation($calculation);

                if ($saved) {
                    $processed++;
                } else {
                    $errors++;
                }

            } catch (\Exception $e) {
                error_log("Error processing attendance {$attendance['id']}: " . $e->getMessage());
                $errors++;
            }
        }

        return [
            'success' => true,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'processed' => $processed,
            'errors' => $errors,
            'total' => count($attendances)
        ];
    }

    /**
     * Procesar y detectar ausencias de un empleado
     *
     * @param int $employeeId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function processWithAbsences($employeeId, $startDate, $endDate)
    {
        // Procesar asistencias normales
        $attendanceResult = $this->processEmployee($employeeId, $startDate, $endDate);

        // Detectar ausencias
        $absences = $this->absenceDetector->detectAbsences($employeeId, $startDate, $endDate);

        return [
            'success' => true,
            'employee_id' => $employeeId,
            'attendance_processed' => $attendanceResult['processed'],
            'absences_detected' => count($absences),
            'absences' => $absences,
            'details' => $attendanceResult
        ];
    }

    /**
     * Procesar mes completo con detección de ausencias
     *
     * @param int $year
     * @param int $month
     * @return array
     */
    public function processMonth($year, $month)
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        // Procesar asistencias
        $attendanceResult = $this->processPeriod($startDate, $endDate);

        // Detectar ausencias de todos los empleados
        $absences = $this->absenceDetector->detectAllAbsences($startDate, $endDate);
        $totalAbsences = array_sum(array_map('count', $absences));

        return [
            'success' => true,
            'year' => $year,
            'month' => $month,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'attendance_processed' => $attendanceResult['processed'],
            'total_absences' => $totalAbsences,
            'employees_with_absences' => count($absences),
            'absences_by_employee' => $absences,
            'details' => $attendanceResult
        ];
    }

    /**
     * Reprocesar asistencias (eliminar cálculos antiguos y recalcular)
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function reprocessPeriod($startDate, $endDate)
    {
        // Eliminar cálculos existentes del período
        $deleted = $this->calculationModel->deletePeriodCalculations($startDate, $endDate);

        // Procesar nuevamente
        $result = $this->processPeriod($startDate, $endDate);

        $result['reprocessed'] = true;
        $result['old_calculations_deleted'] = $deleted;

        return $result;
    }

    /**
     * Obtener estadísticas del procesamiento de un período
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getProcessingStats($startDate, $endDate)
    {
        // Contar asistencias totales
        $totalAttendances = count($this->attendanceModel->getAttendanceByDateRange($startDate, $endDate));

        // Contar cálculos procesados
        $calculations = $this->calculationModel->getByDateRange($startDate, $endDate);
        $totalCalculations = count($calculations);

        // Obtener totales del período
        $totals = $this->calculationModel->getPeriodTotals($startDate, $endDate);

        // Detectar ausencias
        $absences = $this->absenceDetector->detectAllAbsences($startDate, $endDate);
        $totalAbsences = array_sum(array_map('count', $absences));

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'attendance' => [
                'total_records' => $totalAttendances,
                'calculated' => $totalCalculations,
                'pending' => $totalAttendances - $totalCalculations
            ],
            'absences' => [
                'total' => $totalAbsences,
                'employees_affected' => count($absences)
            ],
            'totals' => $totals
        ];
    }

    /**
     * Procesar asistencias del día actual
     *
     * @return array
     */
    public function processToday()
    {
        $today = date('Y-m-d');
        return $this->processDate($today);
    }

    /**
     * Verificar si un período ya fue procesado
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function isPeriodProcessed($startDate, $endDate)
    {
        $attendances = $this->attendanceModel->getAttendanceByDateRange($startDate, $endDate);
        $calculations = $this->calculationModel->getByDateRange($startDate, $endDate);

        $totalAttendances = count($attendances);
        $totalCalculations = count($calculations);

        $isProcessed = $totalAttendances > 0 && $totalAttendances == $totalCalculations;

        return [
            'is_processed' => $isProcessed,
            'total_attendances' => $totalAttendances,
            'total_calculations' => $totalCalculations,
            'pending' => $totalAttendances - $totalCalculations,
            'completion_rate' => $totalAttendances > 0
                ? round(($totalCalculations / $totalAttendances) * 100, 2)
                : 0
        ];
    }
}
