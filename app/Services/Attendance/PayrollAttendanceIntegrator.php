<?php

namespace App\Services\Attendance;

use App\Core\Database;
use PDO;
use PDOException;

/**
 * PayrollAttendanceIntegrator
 *
 * Clase coordinadora principal para integración asistencias → planillas
 * Orquesta el flujo completo: resumen → mapeo → inserción
 *
 * @version 1.0.0
 * @date 2025-10-20
 */
class PayrollAttendanceIntegrator
{
    private $db;
    private PeriodAttendanceSummary $summaryGenerator;
    private AttendanceConceptMapper $conceptMapper;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->summaryGenerator = new PeriodAttendanceSummary();
        $this->conceptMapper = new AttendanceConceptMapper();
    }

    /**
     * Procesar asistencias para una planilla completa
     *
     * @param int $planilla_id ID de la planilla
     * @param array $employee_ids Array de IDs de empleados a procesar
     * @param int $tipo_planilla_id ID del tipo de planilla
     * @return array Resultado del procesamiento con estadísticas
     */
    public function processPayrollAttendance(
        int $planilla_id,
        array $employee_ids,
        int $tipo_planilla_id
    ): array {
        $this->db->beginTransaction();

        try {
            // Obtener información de la planilla
            $payroll = $this->getPayrollInfo($planilla_id);

            if (!$payroll) {
                throw new \Exception("Planilla no encontrada: $planilla_id");
            }

            $period_start = $payroll['fecha_desde'];
            $period_end = $payroll['fecha_hasta'];

            // Estadísticas del procesamiento
            $stats = [
                'total_employees' => count($employee_ids),
                'processed' => 0,
                'summaries_created' => 0,
                'concepts_generated' => 0,
                'errors' => 0,
                'error_messages' => [],
                'employees_processed' => []
            ];

            // Procesar cada empleado
            foreach ($employee_ids as $employee_id) {
                try {
                    $result = $this->processEmployeeAttendance(
                        $planilla_id,
                        $employee_id,
                        $tipo_planilla_id,
                        $period_start,
                        $period_end
                    );

                    $stats['processed']++;
                    $stats['summaries_created'] += $result['summary_created'] ? 1 : 0;
                    $stats['concepts_generated'] += count($result['concepts']);
                    $stats['employees_processed'][] = [
                        'employee_id' => $employee_id,
                        'concepts_count' => count($result['concepts']),
                        'summary_id' => $result['summary_id'] ?? null
                    ];

                } catch (\Exception $e) {
                    $stats['errors']++;
                    $stats['error_messages'][] = "Empleado $employee_id: " . $e->getMessage();
                    error_log("Error procesando empleado $employee_id: " . $e->getMessage());
                }
            }

            $this->db->commit();

            return [
                'success' => true,
                'stats' => $stats
            ];

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Error en processPayrollAttendance: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'stats' => $stats ?? []
            ];
        }
    }

    /**
     * Procesar asistencias de un empleado específico
     *
     * @param int $planilla_id ID de la planilla
     * @param int $employee_id ID del empleado
     * @param int $tipo_planilla_id ID del tipo de planilla
     * @param string $period_start Fecha inicio
     * @param string $period_end Fecha fin
     * @return array Resultado con summary_id y conceptos generados
     */
    public function processEmployeeAttendance(
        int $planilla_id,
        int $employee_id,
        int $tipo_planilla_id,
        string $period_start,
        string $period_end
    ): array {
        // 1. Generar resumen de asistencias
        $summary = $this->summaryGenerator->generateSummary($employee_id, $period_start, $period_end);

        if ($summary === false) {
            throw new \Exception("No se pudo generar resumen de asistencias");
        }

        // 2. Guardar resumen en BD
        $summary_id = $this->saveSummary($planilla_id, $summary);

        // 3. Mapear asistencias a conceptos de planilla
        $concepts = $this->conceptMapper->mapAttendanceToConcepts(
            $summary,
            $employee_id,
            $tipo_planilla_id,
            null // situacion_id - puede obtenerse si es necesario
        );

        // 4. Guardar detalles granulares (opcional - para trazabilidad)
        $this->saveDetails($summary_id, $employee_id, $period_start, $period_end);

        return [
            'summary_created' => true,
            'summary_id' => $summary_id,
            'concepts' => $concepts,
            'summary' => $summary
        ];
    }

    /**
     * Obtener información de la planilla
     */
    private function getPayrollInfo(int $planilla_id): ?array
    {
        $sql = "SELECT * FROM planilla_cabecera WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$planilla_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Guardar resumen en payroll_attendance_summary
     *
     * @param int $planilla_id ID de la planilla
     * @param array $summary Datos del resumen
     * @return int ID del resumen insertado
     */
    private function saveSummary(int $planilla_id, array $summary): int
    {
        // Verificar si ya existe un resumen para esta planilla y empleado
        $existingId = $this->getSummaryId($planilla_id, $summary['employee_id']);

        if ($existingId) {
            // Actualizar existente
            $this->updateSummary($existingId, $summary);
            return $existingId;
        }

        // Insertar nuevo
        $sql = "INSERT INTO payroll_attendance_summary (
                    planilla_cabecera_id, employee_id, period_start, period_end,
                    total_hours_worked, regular_hours, overtime_hours_25, overtime_hours_50,
                    night_hours, holiday_hours, sunday_hours,
                    total_days_worked, total_absences, justified_absences,
                    unjustified_absences, pending_absences, perfect_attendance_days,
                    total_tardiness_minutes, tardiness_count, early_departures_count,
                    punctuality_score, total_lunch_time_minutes, lunch_violations,
                    total_regular_pay, total_overtime_pay, total_night_pay,
                    total_holiday_pay, total_deductions, total_bonuses, net_attendance_pay,
                    legal_compliant, legal_violations_count, legal_warnings_count,
                    legal_risk_level, legal_compliance_notes,
                    alerts_generated, critical_alerts, pending_alerts,
                    processing_status, processed_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $planilla_id,
            $summary['employee_id'],
            $summary['period_start'],
            $summary['period_end'],
            $summary['total_hours_worked'],
            $summary['regular_hours'],
            $summary['overtime_hours_25'],
            $summary['overtime_hours_50'],
            $summary['night_hours'],
            $summary['holiday_hours'],
            $summary['sunday_hours'],
            $summary['total_days_worked'],
            $summary['total_absences'],
            $summary['justified_absences'],
            $summary['unjustified_absences'],
            $summary['pending_absences'],
            $summary['perfect_attendance_days'],
            $summary['total_tardiness_minutes'],
            $summary['tardiness_count'],
            $summary['early_departures_count'],
            $summary['punctuality_score'],
            $summary['total_lunch_time_minutes'],
            $summary['lunch_violations'],
            $summary['total_regular_pay'],
            $summary['total_overtime_pay'],
            $summary['total_night_pay'],
            $summary['total_holiday_pay'],
            $summary['total_deductions'],
            $summary['total_bonuses'],
            $summary['net_attendance_pay'],
            $summary['legal_compliant'],
            $summary['legal_violations_count'],
            $summary['legal_warnings_count'],
            $summary['legal_risk_level'],
            $summary['legal_compliance_notes'],
            $summary['alerts_generated'],
            $summary['critical_alerts'],
            $summary['pending_alerts'],
            'COMPLETED'
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Verificar si ya existe un resumen para esta planilla y empleado
     */
    private function getSummaryId(int $planilla_id, int $employee_id): ?int
    {
        $sql = "SELECT id FROM payroll_attendance_summary
                WHERE planilla_cabecera_id = ? AND employee_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$planilla_id, $employee_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['id'] : null;
    }

    /**
     * Actualizar resumen existente
     */
    private function updateSummary(int $summary_id, array $summary): void
    {
        $sql = "UPDATE payroll_attendance_summary SET
                    total_hours_worked = ?, regular_hours = ?, overtime_hours_25 = ?,
                    overtime_hours_50 = ?, night_hours = ?, holiday_hours = ?,
                    sunday_hours = ?, total_days_worked = ?, total_absences = ?,
                    justified_absences = ?, unjustified_absences = ?, pending_absences = ?,
                    perfect_attendance_days = ?, total_tardiness_minutes = ?,
                    tardiness_count = ?, early_departures_count = ?, punctuality_score = ?,
                    total_lunch_time_minutes = ?, lunch_violations = ?,
                    legal_compliant = ?, legal_violations_count = ?, legal_warnings_count = ?,
                    legal_risk_level = ?, legal_compliance_notes = ?,
                    alerts_generated = ?, critical_alerts = ?, pending_alerts = ?,
                    processing_status = 'COMPLETED', processed_at = NOW(), updated_at = NOW()
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $summary['total_hours_worked'], $summary['regular_hours'],
            $summary['overtime_hours_25'], $summary['overtime_hours_50'],
            $summary['night_hours'], $summary['holiday_hours'],
            $summary['sunday_hours'], $summary['total_days_worked'],
            $summary['total_absences'], $summary['justified_absences'],
            $summary['unjustified_absences'], $summary['pending_absences'],
            $summary['perfect_attendance_days'], $summary['total_tardiness_minutes'],
            $summary['tardiness_count'], $summary['early_departures_count'],
            $summary['punctuality_score'], $summary['total_lunch_time_minutes'],
            $summary['lunch_violations'], $summary['legal_compliant'],
            $summary['legal_violations_count'], $summary['legal_warnings_count'],
            $summary['legal_risk_level'], $summary['legal_compliance_notes'],
            $summary['alerts_generated'], $summary['critical_alerts'],
            $summary['pending_alerts'], $summary_id
        ]);
    }

    /**
     * Guardar detalles granulares en payroll_attendance_details
     */
    private function saveDetails(int $summary_id, int $employee_id, string $period_start, string $period_end): void
    {
        // Obtener cálculos del período para guardar detalles
        $sql = "SELECT * FROM attendance_calculations
                WHERE employee_id = ? AND date BETWEEN ? AND ?
                ORDER BY date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employee_id, $period_start, $period_end]);
        $calculations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($calculations)) {
            return;
        }

        // Preparar statement de inserción
        $insertSql = "INSERT INTO payroll_attendance_details (
                        summary_id, attendance_id, calculation_id, attendance_date,
                        time_in, time_out, scheduled_in, scheduled_out,
                        hours_worked, overtime_hours, night_hours,
                        tardiness_minutes, early_departure_minutes, lunch_time_minutes,
                        status, is_perfect_attendance
                      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $insertStmt = $this->db->prepare($insertSql);

        foreach ($calculations as $calc) {
            $status = 'PRESENT';
            if (($calc['total_hours'] ?? 0) == 0) {
                $status = 'ABSENT';
            } elseif (($calc['total_hours'] ?? 0) < 8) {
                $status = 'PARTIAL';
            }

            $insertStmt->execute([
                $summary_id,
                $calc['attendance_id'] ?? null,
                $calc['id'],
                $calc['date'],
                $calc['time_in'],
                $calc['time_out'],
                $calc['scheduled_in'],
                $calc['scheduled_out'],
                $calc['total_hours'],
                ($calc['overtime_hours_25'] ?? 0) + ($calc['overtime_hours_50'] ?? 0),
                $calc['night_hours'] ?? 0,
                $calc['tardiness_minutes'] ?? 0,
                $calc['early_departure_minutes'] ?? 0,
                $calc['lunch_time_minutes'] ?? 0,
                $status,
                $calc['perfect_attendance'] ?? 0
            ]);
        }
    }

    /**
     * Obtener resumen de asistencias de una planilla
     *
     * @param int $planilla_id ID de la planilla
     * @return array Array de resúmenes por empleado
     */
    public function getPayrollAttendanceSummary(int $planilla_id): array
    {
        $sql = "SELECT pas.*, e.employee_id as employee_code, e.firstname, e.lastname
                FROM payroll_attendance_summary pas
                INNER JOIN employees e ON pas.employee_id = e.id
                WHERE pas.planilla_cabecera_id = ?
                ORDER BY e.lastname, e.firstname";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$planilla_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener detalles de asistencias de un empleado en una planilla
     *
     * @param int $planilla_id ID de la planilla
     * @param int $employee_id ID del empleado
     * @return array Array de detalles día por día
     */
    public function getEmployeeAttendanceDetails(int $planilla_id, int $employee_id): array
    {
        $sql = "SELECT pad.*
                FROM payroll_attendance_details pad
                INNER JOIN payroll_attendance_summary pas ON pad.summary_id = pas.id
                WHERE pas.planilla_cabecera_id = ? AND pas.employee_id = ?
                ORDER BY pad.attendance_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$planilla_id, $employee_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Eliminar datos de asistencias de una planilla (para reprocesamiento)
     *
     * @param int $planilla_id ID de la planilla
     * @return bool True si se eliminó correctamente
     */
    public function deletePayrollAttendanceData(int $planilla_id): bool
    {
        try {
            // Los detalles se eliminarán en cascada por FK constraint
            $sql = "DELETE FROM payroll_attendance_summary WHERE planilla_cabecera_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$planilla_id]);
            return true;

        } catch (PDOException $e) {
            error_log("Error eliminando datos de asistencias: " . $e->getMessage());
            return false;
        }
    }
}
