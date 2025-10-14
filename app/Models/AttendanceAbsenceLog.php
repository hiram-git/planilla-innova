<?php

namespace App\Models;

use App\Core\Model;
use PDO;
use PDOException;

/**
 * AttendanceAbsenceLog Model
 *
 * Gestiona registros de ausencias detectadas (días sin marcación).
 * Almacena información de justificaciones, tipo de ausencia y resolución.
 *
 * @package App\Models
 * @version 1.0
 */
class AttendanceAbsenceLog extends Model
{
    public $table = 'attendance_absence_log';

    public $fillable = [
        'employee_id', 'absence_date', 'absence_type',
        'justified', 'justification_type', 'justification_document', 'justification_notes',
        'is_working_day', 'day_type',
        'detected_at', 'detection_method',
        'resolved', 'resolved_at', 'resolved_by'
    ];

    /**
     * Guardar o actualizar un log de ausencia
     * Si ya existe para ese employee_id + absence_date, actualiza
     *
     * @param array $absenceData Datos de la ausencia
     * @return int|bool ID del registro o false en error
     */
    public function saveAbsence($absenceData)
    {
        try {
            // Verificar si ya existe
            $existing = $this->findByEmployeeAndDate(
                $absenceData['employee_id'],
                $absenceData['absence_date']
            );

            if ($existing) {
                // Actualizar existente (no sobrescribir si ya fue resuelto)
                if (!$existing['resolved']) {
                    return $this->update($existing['id'], $absenceData);
                }
                return $existing['id'];
            }

            // Insertar nuevo
            return $this->create($absenceData);

        } catch (PDOException $e) {
            error_log("Error saving absence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Buscar ausencia por empleado y fecha
     *
     * @param int $employeeId
     * @param string $absenceDate
     * @return array|null
     */
    public function findByEmployeeAndDate($employeeId, $absenceDate)
    {
        try {
            $sql = "SELECT * FROM {$this->table}
                    WHERE employee_id = ? AND absence_date = ?
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $absenceDate]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        } catch (PDOException $e) {
            error_log("Error finding absence: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener ausencias de un empleado en un rango de fechas
     *
     * @param int $employeeId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getByEmployeeAndDateRange($employeeId, $startDate, $endDate)
    {
        try {
            $sql = "SELECT al.*, e.firstname, e.lastname, e.employee_id as emp_code
                    FROM {$this->table} al
                    LEFT JOIN employees e ON al.employee_id = e.id
                    WHERE al.employee_id = ?
                    AND al.absence_date BETWEEN ? AND ?
                    ORDER BY al.absence_date DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $startDate, $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error getting absences by employee: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener todas las ausencias en un rango de fechas
     *
     * @param string $startDate
     * @param string $endDate
     * @param bool $unresolvedOnly Solo ausencias sin resolver
     * @return array
     */
    public function getByDateRange($startDate, $endDate, $unresolvedOnly = false)
    {
        try {
            $sql = "SELECT al.*, e.firstname, e.lastname, e.employee_id as emp_code
                    FROM {$this->table} al
                    LEFT JOIN employees e ON al.employee_id = e.id
                    WHERE al.absence_date BETWEEN ? AND ?";

            $params = [$startDate, $endDate];

            if ($unresolvedOnly) {
                $sql .= " AND al.resolved = 0";
            }

            $sql .= " ORDER BY al.absence_date DESC, e.lastname, e.firstname";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error getting absences by date range: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Justificar una ausencia
     *
     * @param int $absenceId
     * @param array $justificationData Array con justification_type, justification_notes, justification_document
     * @return bool
     */
    public function justifyAbsence($absenceId, $justificationData)
    {
        try {
            $updateData = [
                'justified' => 1,
                'absence_type' => 'JUSTIFIED',
                'justification_type' => $justificationData['justification_type'] ?? 'OTHER',
                'justification_notes' => $justificationData['justification_notes'] ?? null,
                'justification_document' => $justificationData['justification_document'] ?? null
            ];

            return $this->update($absenceId, $updateData);

        } catch (PDOException $e) {
            error_log("Error justifying absence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marcar ausencia como resuelta
     *
     * @param int $absenceId
     * @param int $resolvedBy ID del usuario que resuelve
     * @return bool
     */
    public function resolveAbsence($absenceId, $resolvedBy)
    {
        try {
            $updateData = [
                'resolved' => 1,
                'resolved_at' => date('Y-m-d H:i:s'),
                'resolved_by' => $resolvedBy
            ];

            return $this->update($absenceId, $updateData);

        } catch (PDOException $e) {
            error_log("Error resolving absence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener ausencias sin justificar de un empleado
     *
     * @param int $employeeId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getUnjustifiedAbsences($employeeId, $startDate, $endDate)
    {
        try {
            $sql = "SELECT * FROM {$this->table}
                    WHERE employee_id = ?
                    AND absence_date BETWEEN ? AND ?
                    AND absence_type = 'UNJUSTIFIED'
                    ORDER BY absence_date DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $startDate, $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error getting unjustified absences: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar ausencias injustificadas de un empleado en un mes
     * (útil para verificar causal de despido: 3+ ausencias injustificadas = falta grave)
     *
     * @param int $employeeId
     * @param string $month Mes en formato Y-m
     * @return int
     */
    public function countMonthlyUnjustifiedAbsences($employeeId, $month)
    {
        try {
            $startDate = $month . '-01';
            $endDate = date('Y-m-t', strtotime($startDate));

            $sql = "SELECT COUNT(*) as total FROM {$this->table}
                    WHERE employee_id = ?
                    AND absence_date BETWEEN ? AND ?
                    AND absence_type = 'UNJUSTIFIED'";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $startDate, $endDate]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int)($result['total'] ?? 0);

        } catch (PDOException $e) {
            error_log("Error counting unjustified absences: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener empleados con ausencias recurrentes (3+ ausencias en el mes)
     *
     * @param string $month Mes en formato Y-m
     * @return array
     */
    public function getEmployeesWithRecurrentAbsences($month)
    {
        try {
            $startDate = $month . '-01';
            $endDate = date('Y-m-t', strtotime($startDate));

            $sql = "SELECT
                        al.employee_id,
                        e.firstname,
                        e.lastname,
                        e.employee_id as emp_code,
                        COUNT(*) as total_absences,
                        SUM(CASE WHEN al.absence_type = 'UNJUSTIFIED' THEN 1 ELSE 0 END) as unjustified_count,
                        SUM(CASE WHEN al.absence_type = 'JUSTIFIED' THEN 1 ELSE 0 END) as justified_count
                    FROM {$this->table} al
                    LEFT JOIN employees e ON al.employee_id = e.id
                    WHERE al.absence_date BETWEEN ? AND ?
                    GROUP BY al.employee_id
                    HAVING COUNT(*) >= 3
                    ORDER BY total_absences DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$startDate, $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error getting recurrent absences: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estadísticas de ausencias por tipo
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getAbsenceStatistics($startDate, $endDate)
    {
        try {
            $sql = "SELECT
                        COUNT(*) as total_absences,
                        COUNT(DISTINCT employee_id) as affected_employees,
                        SUM(CASE WHEN absence_type = 'JUSTIFIED' THEN 1 ELSE 0 END) as justified,
                        SUM(CASE WHEN absence_type = 'UNJUSTIFIED' THEN 1 ELSE 0 END) as unjustified,
                        SUM(CASE WHEN absence_type = 'PENDING' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN resolved = 1 THEN 1 ELSE 0 END) as resolved_count
                    FROM {$this->table}
                    WHERE absence_date BETWEEN ? AND ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$startDate, $endDate]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: [];

        } catch (PDOException $e) {
            error_log("Error getting absence statistics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Guardar múltiples ausencias detectadas (bulk insert)
     *
     * @param array $absences Array de ausencias
     * @return array Resultado con contadores
     */
    public function saveBulkAbsences($absences)
    {
        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($absences as $absence) {
            try {
                $existing = $this->findByEmployeeAndDate(
                    $absence['employee_id'],
                    $absence['absence_date']
                );

                if ($existing) {
                    // Si ya existe y no está resuelto, actualizar
                    if (!$existing['resolved']) {
                        $this->update($existing['id'], $absence);
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } else {
                    // Insertar nuevo
                    $this->create($absence);
                    $inserted++;
                }

            } catch (PDOException $e) {
                error_log("Error in bulk absence save: " . $e->getMessage());
                $errors++;
            }
        }

        return [
            'success' => true,
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'total' => count($absences)
        ];
    }

    /**
     * Eliminar ausencias de un período (útil para reprocesamiento)
     *
     * @param string $startDate
     * @param string $endDate
     * @return bool
     */
    public function deletePeriodAbsences($startDate, $endDate)
    {
        try {
            $sql = "DELETE FROM {$this->table}
                    WHERE absence_date BETWEEN ? AND ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$startDate, $endDate]);

        } catch (PDOException $e) {
            error_log("Error deleting period absences: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener ausencias del día actual
     *
     * @return array
     */
    public function getTodayAbsences()
    {
        $today = date('Y-m-d');
        return $this->getByDateRange($today, $today);
    }

    /**
     * Verificar si un empleado tiene ausencias críticas (3+ injustificadas en mes)
     * Código de Trabajo Art. 213: causal de despido
     *
     * @param int $employeeId
     * @param string $month Mes en formato Y-m (opcional, default mes actual)
     * @return array
     */
    public function checkCriticalAbsences($employeeId, $month = null)
    {
        if (!$month) {
            $month = date('Y-m');
        }

        $count = $this->countMonthlyUnjustifiedAbsences($employeeId, $month);
        $isCritical = $count >= 3;

        return [
            'is_critical' => $isCritical,
            'unjustified_count' => $count,
            'threshold' => 3,
            'month' => $month,
            'warning' => $isCritical ? 'CAUSAL DE DESPIDO: 3+ ausencias injustificadas (Art. 213 Código de Trabajo)' : null
        ];
    }
}
