<?php

namespace App\Services;

use App\Core\Database;
use App\Models\OvertimeApproval;
use PDO;
use Exception;

/**
 * Servicio para Gestión de Aprobaciones de Horas Extras
 *
 * Funciones principales:
 * - Generar solicitudes automáticamente desde attendance_records
 * - Validar duplicados
 * - Calcular montos según tarifa horaria
 */
class OvertimeApprovalService
{
    private Database $db;
    private OvertimeApproval $model;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->model = new OvertimeApproval();
    }

    /**
     * Generar solicitudes de aprobación automáticamente para un período
     *
     * @param string $periodStart Fecha inicio período (Y-m-d)
     * @param string $periodEnd Fecha fin período (Y-m-d)
     * @param array|null $employeeIds IDs específicos de empleados (null = todos)
     * @return array Resultado con estadísticas de generación
     */
    public function generateApprovalsFromAttendance(string $periodStart, string $periodEnd, ?array $employeeIds = null): array
    {
        $result = [
            'success' => true,
            'created' => 0,
            'skipped' => 0,
            'errors' => 0,
            'details' => []
        ];

        try {
            $this->db->beginTransaction();

            // Obtener horas extras desde attendance_records
            $overtimeData = $this->getOvertimeFromAttendanceRecords($periodStart, $periodEnd, $employeeIds);

            foreach ($overtimeData as $data) {
                try {
                    // Verificar si ya existe una solicitud para este empleado/período
                    if ($this->approvalExists($data['employee_id'], $periodStart, $periodEnd)) {
                        $result['skipped']++;
                        $result['details'][] = [
                            'employee_id' => $data['employee_id'],
                            'employee_name' => $data['employee_name'],
                            'status' => 'skipped',
                            'reason' => 'Ya existe solicitud para este período'
                        ];
                        continue;
                    }

                    // Obtener tarifa horaria del empleado
                    $hourlyRate = $this->getEmployeeHourlyRate($data['employee_id']);

                    // Calcular montos
                    $total_25 = $data['overtime_25'];
                    $total_50 = $data['overtime_50'];
                    $totalHours = $total_25 + $total_50;

                    $amount_25 = $total_25 * $hourlyRate * 1.25;
                    $amount_50 = $total_50 * $hourlyRate * 1.50;
                    $totalAmount = $amount_25 + $amount_50;

                    // Crear solicitud
                    $approvalData = [
                        'employee_id' => $data['employee_id'],
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                        'total_overtime_25' => $total_25,
                        'total_overtime_50' => $total_50,
                        'total_hours' => $totalHours,
                        'hourly_rate' => $hourlyRate,
                        'total_amount' => $totalAmount,
                        'status' => 'PENDIENTE',
                        'approval_level' => 1
                    ];

                    $approvalId = $this->model->create($approvalData);

                    if ($approvalId) {
                        // Registrar en historial
                        $this->createHistoryEntry($approvalId, null, 'CREADO', null, 'PENDIENTE', 'Generado automáticamente desde asistencias');

                        $result['created']++;
                        $result['details'][] = [
                            'employee_id' => $data['employee_id'],
                            'employee_name' => $data['employee_name'],
                            'status' => 'created',
                            'approval_id' => $approvalId,
                            'total_hours' => $totalHours,
                            'total_amount' => $totalAmount
                        ];
                    }

                } catch (Exception $e) {
                    $result['errors']++;
                    $result['details'][] = [
                        'employee_id' => $data['employee_id'],
                        'employee_name' => $data['employee_name'] ?? 'Desconocido',
                        'status' => 'error',
                        'reason' => $e->getMessage()
                    ];
                }
            }

            $this->db->commit();

        } catch (Exception $e) {
            $this->db->rollBack();
            $result['success'] = false;
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Obtener horas extras desde attendance_records agrupadas por empleado
     *
     * @param string $periodStart
     * @param string $periodEnd
     * @param array|null $employeeIds
     * @return array
     */
    private function getOvertimeFromAttendanceRecords(string $periodStart, string $periodEnd, ?array $employeeIds = null): array
    {
        $sql = "
            SELECT
                ac.employee_id,
                CONCAT(e.firstname, ' ', e.lastname) AS employee_name,
                SUM(ac.overtime_25_hours) AS overtime_25,
                SUM(ac.overtime_50_hours) AS overtime_50
            FROM attendance_calculations ac
            INNER JOIN employees e ON ac.employee_id = e.id
            WHERE ac.date BETWEEN :period_start AND :period_end
              AND (ac.overtime_25_hours > 0 OR ac.overtime_50_hours > 0)
        ";

        $params = [
            ':period_start' => $periodStart,
            ':period_end' => $periodEnd
        ];

        if ($employeeIds !== null && !empty($employeeIds)) {
            $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
            $sql .= " AND ac.employee_id IN ($placeholders)";
            $params = array_merge($params, $employeeIds);
        }

        $sql .= " GROUP BY ac.employee_id, e.firstname, e.lastname";

        $stmt = $this->db->prepare($sql);

        // Bind named parameters
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $stmt->bindValue($key, $value);
            }
        }

        // Bind positional parameters (employee IDs)
        if ($employeeIds !== null && !empty($employeeIds)) {
            $namedParamsCount = 2; // :period_start y :period_end
            foreach ($employeeIds as $index => $id) {
                $stmt->bindValue($namedParamsCount + $index + 1, $id, PDO::PARAM_INT);
            }
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verificar si ya existe una solicitud para un empleado en un período
     *
     * @param int $employeeId
     * @param string $periodStart
     * @param string $periodEnd
     * @return bool
     */
    private function approvalExists(int $employeeId, string $periodStart, string $periodEnd): bool
    {
        $sql = "
            SELECT COUNT(*) as count
            FROM overtime_approvals
            WHERE employee_id = :employee_id
              AND period_start = :period_start
              AND period_end = :period_end
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':employee_id' => $employeeId,
            ':period_start' => $periodStart,
            ':period_end' => $periodEnd
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Obtener tarifa horaria de un empleado
     * Calcula: salario_mensual / 220 horas
     *
     * @param int $employeeId
     * @return float
     */
    private function getEmployeeHourlyRate(int $employeeId): float
    {
        // Verificar qué campo de salario existe
        $checkColumnSql = "SHOW COLUMNS FROM employees LIKE 'sueldo'";
        $stmt = $this->db->prepare($checkColumnSql);
        $stmt->execute();
        $hasSueldoField = $stmt->rowCount() > 0;
        $salaryField = $hasSueldoField ? 'sueldo' : 'salario';

        $sql = "
            SELECT {$salaryField} as salary
            FROM employees
            WHERE id = :employee_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':employee_id' => $employeeId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $monthlySalary = $result['salary'] ?? 0;
        return $monthlySalary > 0 ? round($monthlySalary / 220, 2) : 0;
    }

    /**
     * Crear entrada en historial de aprobaciones
     *
     * @param int $approvalId
     * @param int|null $userId
     * @param string $action
     * @param string|null $previousStatus
     * @param string $newStatus
     * @param string|null $comments
     * @return int
     */
    private function createHistoryEntry(
        int $approvalId,
        ?int $userId,
        string $action,
        ?string $previousStatus,
        string $newStatus,
        ?string $comments = null
    ): int {
        $sql = "
            INSERT INTO overtime_approval_history
            (approval_id, user_id, action, previous_status, new_status, comments, ip_address, user_agent)
            VALUES
            (:approval_id, :user_id, :action, :previous_status, :new_status, :comments, :ip_address, :user_agent)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':approval_id' => $approvalId,
            ':user_id' => $userId,
            ':action' => $action,
            ':previous_status' => $previousStatus,
            ':new_status' => $newStatus,
            ':comments' => $comments,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);

        return (int) $this->db->getConnection()->lastInsertId();
    }

    /**
     * Crear solicitud de aprobación manualmente
     *
     * @param array $data Datos de la solicitud
     * @param int|null $userId ID del usuario que crea la solicitud
     * @return array Resultado de la operación
     */
    public function createManualApproval(array $data, ?int $userId = null): array
    {
        try {
            $this->db->beginTransaction();

            // Validar que no exista solicitud duplicada
            if ($this->approvalExists($data['employee_id'], $data['period_start'], $data['period_end'])) {
                return [
                    'success' => false,
                    'error' => 'Ya existe una solicitud de aprobación para este empleado en el período especificado'
                ];
            }

            // Obtener tarifa horaria si no se proporcionó
            if (!isset($data['hourly_rate']) || $data['hourly_rate'] <= 0) {
                $data['hourly_rate'] = $this->getEmployeeHourlyRate($data['employee_id']);
            }

            // Calcular totales
            $total_25 = $data['total_overtime_25'] ?? 0;
            $total_50 = $data['total_overtime_50'] ?? 0;
            $data['total_hours'] = $total_25 + $total_50;

            $amount_25 = $total_25 * $data['hourly_rate'] * 1.25;
            $amount_50 = $total_50 * $data['hourly_rate'] * 1.50;
            $data['total_amount'] = $amount_25 + $amount_50;

            // Establecer valores por defecto
            $data['status'] = $data['status'] ?? 'PENDIENTE';
            $data['approval_level'] = $data['approval_level'] ?? 1;

            // Crear solicitud
            $approvalId = $this->model->create($data);

            if ($approvalId) {
                // Registrar en historial
                $this->createHistoryEntry(
                    $approvalId,
                    $userId,
                    'CREADO',
                    null,
                    $data['status'],
                    $data['comments'] ?? 'Creado manualmente'
                );

                $this->db->commit();

                return [
                    'success' => true,
                    'approval_id' => $approvalId,
                    'message' => 'Solicitud de aprobación creada exitosamente'
                ];
            }

            throw new Exception('Error al crear la solicitud de aprobación');

        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
