<?php

namespace App\Services\Attendance;

use App\Core\Database;
use App\Services\Attendance\Calculators\LegalComplianceChecker;

/**
 * AlertsSystem
 *
 * Sistema de alertas automáticas para cumplimiento legal en asistencias.
 * Genera, gestiona y procesa alertas basadas en violaciones de legislación panameña.
 *
 * Tipos de alertas:
 * - EXCESO_JORNADA_DIARIA/SEMANAL
 * - TIEMPO_COMIDA_INSUFICIENTE
 * - AUSENCIAS_INJUSTIFICADAS_GRAVES
 * - TARDANZAS_RECURRENTES
 * - DIAS_CONSECUTIVOS_SIN_DESCANSO
 * - Y más...
 *
 * Niveles de severidad:
 * - INFO: Información general (cerca del límite)
 * - WARNING: Advertencia (requiere atención)
 * - CRITICAL: Crítico (violación legal, acción inmediata)
 *
 * @package App\Services\Attendance
 * @version 1.0
 */
class AlertsSystem
{
    private Database $db;
    private LegalComplianceChecker $complianceChecker;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->complianceChecker = new LegalComplianceChecker();
    }

    /**
     * Crear una alerta nueva
     *
     * @param array $alertData Datos de la alerta
     * @return int|false ID de la alerta creada o false en error
     */
    public function createAlert(array $alertData): int|false
    {
        // Validar campos requeridos
        $required = ['employee_id', 'alert_type', 'severity', 'title', 'message', 'date'];
        foreach ($required as $field) {
            if (empty($alertData[$field])) {
                error_log("AlertsSystem: Campo requerido faltante: $field");
                return false;
            }
        }

        // Verificar si ya existe una alerta similar activa
        if ($this->isDuplicateAlert($alertData)) {
            error_log("AlertsSystem: Alerta duplicada detectada, omitiendo creación");
            return false;
        }

        // Preparar metadata como JSON
        $metadata = !empty($alertData['metadata']) ? json_encode($alertData['metadata']) : null;

        $query = "INSERT INTO attendance_alerts (
            employee_id,
            attendance_id,
            calculation_id,
            alert_type,
            severity,
            title,
            message,
            article_reference,
            metadata,
            date,
            period_start,
            period_end,
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDING')";

        $params = [
            $alertData['employee_id'],
            $alertData['attendance_id'] ?? null,
            $alertData['calculation_id'] ?? null,
            $alertData['alert_type'],
            $alertData['severity'],
            $alertData['title'],
            $alertData['message'],
            $alertData['article_reference'] ?? null,
            $metadata,
            $alertData['date'],
            $alertData['period_start'] ?? null,
            $alertData['period_end'] ?? null
        ];

        if ($this->db->query($query, $params)) {
            $alertId = $this->db->getConnection()->lastInsertId();
            error_log("AlertsSystem: Alerta creada con ID $alertId para empleado {$alertData['employee_id']}");
            return (int)$alertId;
        }

        return false;
    }

    /**
     * Verificar si una alerta similar ya existe y está activa
     *
     * @param array $alertData Datos de la alerta a verificar
     * @return bool True si existe duplicado
     */
    private function isDuplicateAlert(array $alertData): bool
    {
        $query = "SELECT id FROM attendance_alerts
                  WHERE employee_id = ?
                  AND alert_type = ?
                  AND date = ?
                  AND status IN ('PENDING', 'ACKNOWLEDGED')
                  LIMIT 1";

        $result = $this->db->query($query, [
            $alertData['employee_id'],
            $alertData['alert_type'],
            $alertData['date']
        ]);

        return !empty($result);
    }

    /**
     * Generar alertas a partir de resultados de LegalComplianceChecker
     *
     * @param int $employeeId ID del empleado
     * @param array $complianceResult Resultado de performFullCompliance()
     * @return array Estadísticas de alertas generadas
     */
    public function generateAlertsFromCompliance(int $employeeId, array $complianceResult): array
    {
        $stats = [
            'total_generated' => 0,
            'critical' => 0,
            'warning' => 0,
            'info' => 0,
            'skipped_duplicates' => 0
        ];

        if (empty($complianceResult['checks'])) {
            return $stats;
        }

        // Procesar cada tipo de check
        foreach ($complianceResult['checks'] as $checkType => $checkResult) {
            // Generar alertas solo si hay violaciones o warnings significativos
            if (!empty($checkResult['violations']) || !empty($checkResult['warnings'])) {
                $alerts = $this->createAlertsFromCheck($employeeId, $checkType, $checkResult);

                foreach ($alerts as $alert) {
                    $alertId = $this->createAlert($alert);

                    if ($alertId) {
                        $stats['total_generated']++;
                        $stats[strtolower($alert['severity'])]++;
                    } else {
                        $stats['skipped_duplicates']++;
                    }
                }
            }
        }

        return $stats;
    }

    /**
     * Crear alertas específicas desde un check de cumplimiento
     *
     * @param int $employeeId ID del empleado
     * @param string $checkType Tipo de check (daily_hours, weekly_hours, etc.)
     * @param array $checkResult Resultado del check
     * @return array Array de alertas a crear
     */
    private function createAlertsFromCheck(int $employeeId, string $checkType, array $checkResult): array
    {
        $alerts = [];

        switch ($checkType) {
            case 'daily_hours':
                if (!empty($checkResult['violations'])) {
                    $alerts[] = [
                        'employee_id' => $employeeId,
                        'alert_type' => 'EXCESO_JORNADA_DIARIA',
                        'severity' => 'CRITICAL',
                        'title' => 'Exceso crítico de jornada diaria',
                        'message' => implode('. ', $checkResult['violations']),
                        'article_reference' => $checkResult['article'],
                        'date' => $checkResult['date'],
                        'metadata' => [
                            'total_hours' => $checkResult['total_hours'],
                            'max_allowed' => $checkResult['max_allowed'],
                            'max_recommended' => $checkResult['max_recommended']
                        ]
                    ];
                } elseif (!empty($checkResult['warnings'])) {
                    $alerts[] = [
                        'employee_id' => $employeeId,
                        'alert_type' => 'EXCESO_JORNADA_DIARIA',
                        'severity' => 'WARNING',
                        'title' => 'Horas extras detectadas',
                        'message' => implode('. ', $checkResult['warnings']),
                        'article_reference' => $checkResult['article'],
                        'date' => $checkResult['date'],
                        'metadata' => [
                            'total_hours' => $checkResult['total_hours'],
                            'max_allowed' => $checkResult['max_allowed']
                        ]
                    ];
                }
                break;

            case 'weekly_hours':
                if (!empty($checkResult['violations'])) {
                    $alerts[] = [
                        'employee_id' => $employeeId,
                        'alert_type' => 'EXCESO_JORNADA_SEMANAL',
                        'severity' => 'CRITICAL',
                        'title' => 'Exceso de jornada semanal',
                        'message' => implode('. ', $checkResult['violations']),
                        'article_reference' => $checkResult['article'],
                        'date' => date('Y-m-d'),
                        'period_start' => explode(' a ', $checkResult['period'])[0] ?? null,
                        'period_end' => explode(' a ', $checkResult['period'])[1] ?? null,
                        'metadata' => [
                            'weekly_hours' => $checkResult['weekly_hours'],
                            'max_allowed' => $checkResult['max_allowed'],
                            'excess_hours' => $checkResult['excess_hours']
                        ]
                    ];
                }
                break;

            case 'lunch_time':
                if (!empty($checkResult['violations'])) {
                    $alerts[] = [
                        'employee_id' => $employeeId,
                        'alert_type' => 'TIEMPO_COMIDA_INSUFICIENTE',
                        'severity' => 'CRITICAL',
                        'title' => 'Tiempo de comida insuficiente',
                        'message' => implode('. ', $checkResult['violations']),
                        'article_reference' => $checkResult['article'],
                        'date' => date('Y-m-d'),
                        'metadata' => [
                            'lunch_minutes' => $checkResult['lunch_minutes'],
                            'min_required' => $checkResult['min_required']
                        ]
                    ];
                }
                break;

            case 'night_shift':
                if (!empty($checkResult['violations'])) {
                    $alerts[] = [
                        'employee_id' => $employeeId,
                        'alert_type' => 'EXCESO_JORNADA_NOCTURNA',
                        'severity' => 'CRITICAL',
                        'title' => 'Exceso de jornada nocturna',
                        'message' => implode('. ', $checkResult['violations']),
                        'article_reference' => $checkResult['article'],
                        'date' => date('Y-m-d'),
                        'metadata' => [
                            'night_hours' => $checkResult['night_hours'],
                            'max_night_hours' => $checkResult['max_night_hours']
                        ]
                    ];
                }
                break;

            case 'absences':
                if (!empty($checkResult['violations'])) {
                    $alerts[] = [
                        'employee_id' => $employeeId,
                        'alert_type' => 'AUSENCIAS_INJUSTIFICADAS_GRAVES',
                        'severity' => 'CRITICAL',
                        'title' => 'Falta grave: 3+ ausencias injustificadas',
                        'message' => implode('. ', $checkResult['violations']),
                        'article_reference' => $checkResult['article'],
                        'date' => date('Y-m-d'),
                        'period_start' => $checkResult['month'] . '-01',
                        'period_end' => date('Y-m-t', strtotime($checkResult['month'])),
                        'metadata' => [
                            'unjustified_absences' => $checkResult['unjustified_absences'],
                            'threshold' => $checkResult['threshold'],
                            'is_serious_offense' => $checkResult['is_serious_offense'],
                            'can_be_dismissed' => $checkResult['can_be_dismissed']
                        ]
                    ];
                } elseif (!empty($checkResult['warnings'])) {
                    $alerts[] = [
                        'employee_id' => $employeeId,
                        'alert_type' => 'AUSENCIAS_INJUSTIFICADAS_GRAVES',
                        'severity' => 'WARNING',
                        'title' => 'Advertencia de ausencias injustificadas',
                        'message' => implode('. ', $checkResult['warnings']),
                        'article_reference' => $checkResult['article'],
                        'date' => date('Y-m-d'),
                        'metadata' => [
                            'unjustified_absences' => $checkResult['unjustified_absences'],
                            'threshold' => $checkResult['threshold']
                        ]
                    ];
                }
                break;

            case 'rest_day':
                if (!empty($checkResult['warnings'])) {
                    $alerts[] = [
                        'employee_id' => $employeeId,
                        'alert_type' => 'TRABAJO_FERIADO_SIN_COMPENSACION',
                        'severity' => 'INFO',
                        'title' => 'Trabajo en día de descanso',
                        'message' => implode('. ', $checkResult['warnings']),
                        'article_reference' => $checkResult['article'],
                        'date' => $checkResult['date'],
                        'metadata' => [
                            'is_sunday' => $checkResult['is_sunday'],
                            'is_holiday' => $checkResult['is_holiday'],
                            'hours_worked' => $checkResult['hours_worked'],
                            'requires_surcharge' => $checkResult['requires_surcharge'],
                            'requires_compensatory_day' => $checkResult['requires_compensatory_day']
                        ]
                    ];
                }
                break;
        }

        return $alerts;
    }

    /**
     * Obtener alertas pendientes de un empleado
     *
     * @param int $employeeId ID del empleado
     * @param string|null $severity Filtrar por severidad (opcional)
     * @return array Lista de alertas
     */
    public function getEmployeePendingAlerts(int $employeeId, ?string $severity = null): array
    {
        $query = "SELECT * FROM attendance_alerts
                  WHERE employee_id = ?
                  AND status IN ('PENDING', 'ACKNOWLEDGED')";

        $params = [$employeeId];

        if ($severity) {
            $query .= " AND severity = ?";
            $params[] = $severity;
        }

        $query .= " ORDER BY
                    FIELD(severity, 'CRITICAL', 'WARNING', 'INFO'),
                    date DESC";

        $results = $this->db->query($query, $params);

        // Decodificar metadata JSON
        foreach ($results as &$alert) {
            if (!empty($alert['metadata'])) {
                $alert['metadata'] = json_decode($alert['metadata'], true);
            }
        }

        return $results;
    }

    /**
     * Obtener alertas críticas globales sin resolver
     *
     * @param int $limit Límite de resultados
     * @return array Lista de alertas críticas
     */
    public function getCriticalAlerts(int $limit = 50): array
    {
        $query = "SELECT * FROM v_critical_alerts
                  ORDER BY date DESC
                  LIMIT ?";

        return $this->db->query($query, [$limit]);
    }

    /**
     * Obtener estadísticas de alertas por empleado
     *
     * @param int $employeeId ID del empleado
     * @param int $days Días hacia atrás (default 30)
     * @return array Estadísticas
     */
    public function getEmployeeAlertStats(int $employeeId, int $days = 30): array
    {
        $query = "SELECT
                    COUNT(*) as total_alerts,
                    SUM(CASE WHEN severity = 'CRITICAL' THEN 1 ELSE 0 END) as critical_count,
                    SUM(CASE WHEN severity = 'WARNING' THEN 1 ELSE 0 END) as warning_count,
                    SUM(CASE WHEN severity = 'INFO' THEN 1 ELSE 0 END) as info_count,
                    SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'ACKNOWLEDGED' THEN 1 ELSE 0 END) as acknowledged_count,
                    SUM(CASE WHEN status = 'RESOLVED' THEN 1 ELSE 0 END) as resolved_count,
                    MAX(date) as last_alert_date
                  FROM attendance_alerts
                  WHERE employee_id = ?
                  AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)";

        $result = $this->db->query($query, [$employeeId, $days]);

        return $result[0] ?? [
            'total_alerts' => 0,
            'critical_count' => 0,
            'warning_count' => 0,
            'info_count' => 0,
            'pending_count' => 0,
            'acknowledged_count' => 0,
            'resolved_count' => 0,
            'last_alert_date' => null
        ];
    }

    /**
     * Reconocer una alerta (cambiar estado a ACKNOWLEDGED)
     *
     * @param int $alertId ID de la alerta
     * @param int $userId ID del usuario que reconoce
     * @param string|null $notes Notas opcionales
     * @return bool True si se actualizó correctamente
     */
    public function acknowledgeAlert(int $alertId, int $userId, ?string $notes = null): bool
    {
        $query = "UPDATE attendance_alerts
                  SET status = 'ACKNOWLEDGED',
                      acknowledged_by = ?,
                      acknowledged_at = NOW(),
                      notes = CONCAT(COALESCE(notes, ''), ?)
                  WHERE id = ?
                  AND status = 'PENDING'";

        $noteText = $notes ? "\n[" . date('Y-m-d H:i:s') . "] Reconocida por usuario $userId: $notes" : '';

        return $this->db->query($query, [$userId, $noteText, $alertId]) !== false;
    }

    /**
     * Resolver una alerta (cambiar estado a RESOLVED)
     *
     * @param int $alertId ID de la alerta
     * @param string|null $notes Notas de resolución
     * @return bool True si se actualizó correctamente
     */
    public function resolveAlert(int $alertId, ?string $notes = null): bool
    {
        $query = "UPDATE attendance_alerts
                  SET status = 'RESOLVED',
                      resolved_at = NOW(),
                      notes = CONCAT(COALESCE(notes, ''), ?)
                  WHERE id = ?
                  AND status IN ('PENDING', 'ACKNOWLEDGED')";

        $noteText = $notes ? "\n[" . date('Y-m-d H:i:s') . "] Resuelta: $notes" : '';

        return $this->db->query($query, [$noteText, $alertId]) !== false;
    }

    /**
     * Descartar una alerta (cambiar estado a DISMISSED)
     *
     * @param int $alertId ID de la alerta
     * @param string|null $reason Razón del descarte
     * @return bool True si se actualizó correctamente
     */
    public function dismissAlert(int $alertId, ?string $reason = null): bool
    {
        $query = "UPDATE attendance_alerts
                  SET status = 'DISMISSED',
                      notes = CONCAT(COALESCE(notes, ''), ?)
                  WHERE id = ?";

        $noteText = $reason ? "\n[" . date('Y-m-d H:i:s') . "] Descartada: $reason" : '';

        return $this->db->query($query, [$noteText, $alertId]) !== false;
    }

    /**
     * Obtener resumen global de alertas
     *
     * @return array Resumen estadístico
     */
    public function getGlobalAlertsSummary(): array
    {
        $query = "SELECT
                    COUNT(*) as total_alerts,
                    SUM(CASE WHEN severity = 'CRITICAL' THEN 1 ELSE 0 END) as critical_count,
                    SUM(CASE WHEN severity = 'WARNING' THEN 1 ELSE 0 END) as warning_count,
                    SUM(CASE WHEN severity = 'INFO' THEN 1 ELSE 0 END) as info_count,
                    SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending_count,
                    COUNT(DISTINCT employee_id) as affected_employees,
                    COUNT(DISTINCT alert_type) as different_alert_types
                  FROM attendance_alerts
                  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";

        $result = $this->db->query($query);

        return $result[0] ?? [];
    }

    /**
     * Limpiar alertas antiguas resueltas
     *
     * @param int $daysOld Días de antigüedad
     * @return int Número de alertas eliminadas
     */
    public function cleanupOldResolvedAlerts(int $daysOld = 90): int
    {
        $query = "DELETE FROM attendance_alerts
                  WHERE status = 'RESOLVED'
                  AND resolved_at < DATE_SUB(CURDATE(), INTERVAL ? DAY)";

        $this->db->query($query, [$daysOld]);

        return $this->db->getConnection()->rowCount();
    }

    /**
     * Obtener alertas por tipo
     *
     * @param string $alertType Tipo de alerta
     * @param int $limit Límite de resultados
     * @return array Lista de alertas
     */
    public function getAlertsByType(string $alertType, int $limit = 100): array
    {
        $query = "SELECT * FROM attendance_alerts
                  WHERE alert_type = ?
                  AND status IN ('PENDING', 'ACKNOWLEDGED')
                  ORDER BY date DESC
                  LIMIT ?";

        $results = $this->db->query($query, [$alertType, $limit]);

        // Decodificar metadata JSON
        foreach ($results as &$alert) {
            if (!empty($alert['metadata'])) {
                $alert['metadata'] = json_decode($alert['metadata'], true);
            }
        }

        return $results;
    }
}
