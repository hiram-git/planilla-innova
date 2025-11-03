<?php

namespace App\Services\Attendance\Calculators;

use App\Core\Database;
use App\Models\Attendance;
use App\Models\BusinessCalendar;
use App\Services\Attendance\AlertsSystem;
use DateTime;
use Exception;

/**
 * AttendanceCalculator
 *
 * Motor principal de cálculos avanzados de asistencias.
 * Orquesta WorkScheduleResolver, OvertimeCalculator y otros calculadores
 * para generar métricas completas de una asistencia.
 *
 * @package App\Services\Attendance\Calculators
 * @version 1.0
 */
class AttendanceCalculator
{
    private $scheduleResolver;
    private $overtimeCalculator;
    private $attendanceModel;
    private $businessCalendar;
    private $complianceChecker;
    private $alertsSystem;
    private $db;

    public function __construct()
    {
        $this->scheduleResolver = new WorkScheduleResolver();
        $this->overtimeCalculator = new OvertimeCalculator();
        $this->attendanceModel = new Attendance();
        $this->businessCalendar = new BusinessCalendar();
        $this->complianceChecker = new LegalComplianceChecker();
        $this->alertsSystem = new AlertsSystem();
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Calcular todas las métricas de una asistencia
     *
     * @param array $attendance Registro de asistencia desde BD
     * @return array Cálculos completos con todas las métricas
     */
    public function calculate($attendance)
    {
        if (!$attendance || empty($attendance['employee_id'])) {
            return $this->getEmptyCalculation();
        }

        $employeeId = $attendance['employee_id'];
        $date = $attendance['date'];
        $timeIn = $attendance['time_in'];
        $timeOut = $attendance['time_out'];
        $lunchOut = $attendance['lunch_out'] ?? null;
        $lunchIn = $attendance['lunch_in'] ?? null;

        // Obtener horario del empleado para ese día
        $schedule = $this->scheduleResolver->getScheduleForEmployeeOnDate($employeeId, $date);

        // Verificar tipo de día (laboral, feriado, etc.)
        $dayInfo = $this->overtimeCalculator->checkHolidayStatus($date);

        // Si no hay salida, no podemos calcular horas
        if (empty($timeOut)) {
            return $this->getPartialCalculation($attendance, $schedule, $dayInfo);
        }

        // Calcular duración real del almuerzo
        $lunchTimeMinutes = $this->calculateLunchDuration($lunchOut, $lunchIn);

        // Calcular minutos de exceso en el almuerzo
        $lunchExceededMinutes = 0;
        if ($schedule) {
            $lunchExceededMinutes = $this->calculateLunchExceeded(
                $lunchTimeMinutes,
                $schedule['salida_almuerzo'] ?? null,
                $schedule['entrada_almuerzo'] ?? null
            );
        }

        // Calcular breakdown completo de horas
        $hoursBreakdown = $this->overtimeCalculator->calculateCompleteBreakdown(
            $timeIn,
            $timeOut,
            $date,
            $schedule ? $this->scheduleResolver->calculateExpectedWorkHours($schedule) : 8
        );

        // Restar tiempo de almuerzo de las horas trabajadas
        if ($lunchTimeMinutes > 0) {
            $lunchHours = $lunchTimeMinutes / 60;
            $hoursBreakdown['total_hours'] = max(0, $hoursBreakdown['total_hours'] - $lunchHours);
            $hoursBreakdown['regular_hours'] = max(0, $hoursBreakdown['regular_hours'] - $lunchHours);
        }

        // Calcular tardanzas si tiene horario asignado
        $tardinessMinutes = 0;
        $isLate = false;
        if ($schedule) {
            $tardinessMinutes = $this->scheduleResolver->calculateTardinessMinutes(
                $timeIn,
                $schedule['time_in']
            );
            $isLate = $tardinessMinutes > 0;
        }

        // Calcular salida anticipada
        $earlyDepartureMinutes = 0;
        if ($schedule) {
            $earlyDepartureMinutes = $this->scheduleResolver->calculateEarlyDepartureMinutes(
                $timeOut,
                $schedule['time_out']
            );
        }

        // Calcular score de puntualidad (0-100)
        $punctualityScore = $this->calculatePunctualityScore(
            $tardinessMinutes,
            $earlyDepartureMinutes,
            $hoursBreakdown['total_hours']
        );

        // Determinar si es asistencia perfecta
        $isPerfectAttendance = $this->isPerfectAttendance(
            $isLate,
            $earlyDepartureMinutes,
            $hoursBreakdown['total_hours'],
            $schedule
        );

        // Construir resultado completo
        return [
            'attendance_detail_id' => $attendance['id'],
            'employee_id' => $employeeId,
            'date' => $date,
            'schedule_id' => $schedule['schedule_id'] ?? null,

            // Marcaciones
            'time_in' => $this->extractTime($timeIn),
            'time_out' => $this->extractTime($timeOut),
            'lunch_out' => $this->extractTime($lunchOut),
            'lunch_in' => $this->extractTime($lunchIn),
            'scheduled_time_in' => $schedule['time_in'] ?? null,
            'scheduled_time_out' => $schedule['time_out'] ?? null,
            'scheduled_lunch_out' => $schedule['salida_almuerzo'] ?? null,
            'scheduled_lunch_in' => $schedule['entrada_almuerzo'] ?? null,

            // Horas trabajadas
            'total_hours' => $hoursBreakdown['total_hours'],
            'regular_hours' => $hoursBreakdown['regular_hours'],
            'overtime_hours' => $hoursBreakdown['overtime_hours'],
            'overtime_25_hours' => $hoursBreakdown['overtime_25_hours'],
            'overtime_50_hours' => $hoursBreakdown['overtime_50_hours'],
            'night_hours' => $hoursBreakdown['night_hours'],
            'holiday_hours' => $hoursBreakdown['holiday_hours'],

            // Tardanzas y ausencias
            'tardiness_minutes' => $tardinessMinutes,
            'is_late' => $isLate ? 1 : 0,
            'early_departure_minutes' => $earlyDepartureMinutes,
            'is_absent' => 0,
            'absence_type' => null,

            // Tipo de día
            'is_working_day' => $dayInfo['day_type'] === 'LABORAL' ? 1 : 0,
            'is_holiday' => $dayInfo['is_holiday'] ? 1 : 0,
            'is_weekend' => $this->isWeekend($date) ? 1 : 0,
            'day_type' => $dayInfo['day_type'],

            // Métricas adicionales
            'lunch_time_minutes' => $lunchTimeMinutes,
            'lunch_exceeded_minutes' => $lunchExceededMinutes,
            'is_perfect_attendance' => $isPerfectAttendance ? 1 : 0,
            'punctuality_score' => $punctualityScore,

            // Notas
            'notes' => $this->generateNotes($isLate, $tardinessMinutes, $hoursBreakdown),
            'calculation_details' => json_encode([
                'schedule' => $schedule,
                'day_info' => $dayInfo,
                'lunch_info' => [
                    'duration_minutes' => $lunchTimeMinutes,
                    'exceeded_minutes' => $lunchExceededMinutes
                ],
                'calculation_timestamp' => date('Y-m-d H:i:s')
            ]),

            'calculation_version' => 'v1.0',
            'calculated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Calcular métricas para múltiples asistencias
     *
     * @param array $attendances Array de registros de asistencia
     * @return array Array de cálculos
     */
    public function calculateBulk($attendances)
    {
        $calculations = [];

        foreach ($attendances as $attendance) {
            $calculations[] = $this->calculate($attendance);
        }

        return $calculations;
    }

    /**
     * Calcular score de puntualidad (0-100)
     * 100 = Perfecto, 0 = Muy malo
     *
     * @param int $tardinessMinutes
     * @param int $earlyDepartureMinutes
     * @param float $totalHours
     * @return float
     */
    private function calculatePunctualityScore($tardinessMinutes, $earlyDepartureMinutes, $totalHours)
    {
        $score = 100;

        // Penalizar tardanzas: -10 puntos por cada 5 minutos
        $tardinessPenalty = ($tardinessMinutes / 5) * 10;
        $score -= $tardinessPenalty;

        // Penalizar salidas anticipadas: -5 puntos por cada 5 minutos
        $earlyDeparturePenalty = ($earlyDepartureMinutes / 5) * 5;
        $score -= $earlyDeparturePenalty;

        // Bonificar si trabajó horas extras
        if ($totalHours > 8) {
            $score += 5;
        }

        // Limitar entre 0 y 100
        return round(max(0, min(100, $score)), 2);
    }

    /**
     * Determinar si es asistencia perfecta
     *
     * @param bool $isLate
     * @param int $earlyDepartureMinutes
     * @param float $totalHours
     * @param array|null $schedule
     * @return bool
     */
    private function isPerfectAttendance($isLate, $earlyDepartureMinutes, $totalHours, $schedule)
    {
        // No llegó tarde
        if ($isLate) {
            return false;
        }

        // No salió antes
        if ($earlyDepartureMinutes > 0) {
            return false;
        }

        // Trabajó al menos las horas esperadas
        $expectedHours = $schedule ? $this->scheduleResolver->calculateExpectedWorkHours($schedule) : 8;
        if ($totalHours < $expectedHours) {
            return false;
        }

        return true;
    }

    /**
     * Generar notas descriptivas del cálculo
     *
     * @param bool $isLate
     * @param int $tardinessMinutes
     * @param array $hoursBreakdown
     * @return string
     */
    private function generateNotes($isLate, $tardinessMinutes, $hoursBreakdown)
    {
        $notes = [];

        if ($isLate) {
            $notes[] = "Llegó tarde: {$tardinessMinutes} minutos";
        }

        if ($hoursBreakdown['overtime_hours'] > 0) {
            $notes[] = "Horas extras: {$hoursBreakdown['overtime_hours']}h";
        }

        if ($hoursBreakdown['night_hours'] > 0) {
            $notes[] = "Jornada nocturna: {$hoursBreakdown['night_hours']}h";
        }

        if ($hoursBreakdown['holiday_hours'] > 0) {
            $notes[] = "Trabajó en feriado: {$hoursBreakdown['holiday_hours']}h";
        }

        return implode(' | ', $notes);
    }

    /**
     * Calcular duración del almuerzo en minutos
     *
     * @param string|null $lunchOut Hora de salida a almuerzo
     * @param string|null $lunchIn Hora de entrada después de almuerzo
     * @return int Duración en minutos
     */
    private function calculateLunchDuration($lunchOut, $lunchIn)
    {
        if (empty($lunchOut) || empty($lunchIn)) {
            return 0;
        }

        try {
            $out = new DateTime($lunchOut);
            $in = new DateTime($lunchIn);
            $interval = $out->diff($in);

            return ($interval->h * 60) + $interval->i;
        } catch (Exception $e) {
            error_log("Error calculating lunch duration: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calcular minutos de exceso en el período de almuerzo
     *
     * @param int $actualLunchMinutes Duración real del almuerzo
     * @param string|null $scheduledLunchOut Hora programada de salida a almuerzo
     * @param string|null $scheduledLunchIn Hora programada de entrada después de almuerzo
     * @return int Minutos de exceso (0 si no hay exceso)
     */
    private function calculateLunchExceeded($actualLunchMinutes, $scheduledLunchOut, $scheduledLunchIn)
    {
        if (empty($scheduledLunchOut) || empty($scheduledLunchIn) || $actualLunchMinutes <= 0) {
            return 0;
        }

        try {
            $out = new DateTime($scheduledLunchOut);
            $in = new DateTime($scheduledLunchIn);
            $scheduledMinutes = ($in->getTimestamp() - $out->getTimestamp()) / 60;

            $exceeded = $actualLunchMinutes - $scheduledMinutes;
            return max(0, (int)$exceeded);
        } catch (Exception $e) {
            error_log("Error calculating lunch exceeded: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Extraer solo la hora de un timestamp completo
     *
     * @param string $datetime
     * @return string|null
     */
    private function extractTime($datetime)
    {
        if (empty($datetime)) {
            return null;
        }

        try {
            $dt = new DateTime($datetime);
            return $dt->format('H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Verificar si una fecha es fin de semana
     *
     * @param string $date
     * @return bool
     */
    private function isWeekend($date)
    {
        $dayOfWeek = date('N', strtotime($date));
        return $dayOfWeek >= 6;
    }

    /**
     * Retornar cálculo vacío para casos sin datos
     *
     * @return array
     */
    private function getEmptyCalculation()
    {
        return [
            'attendance_detail_id' => null,
            'employee_id' => null,
            'date' => null,
            'schedule_id' => null,
            'time_in' => null,
            'time_out' => null,
            'lunch_out' => null,
            'lunch_in' => null,
            'scheduled_time_in' => null,
            'scheduled_time_out' => null,
            'scheduled_lunch_out' => null,
            'scheduled_lunch_in' => null,
            'total_hours' => 0,
            'regular_hours' => 0,
            'overtime_hours' => 0,
            'overtime_25_hours' => 0,
            'overtime_50_hours' => 0,
            'night_hours' => 0,
            'holiday_hours' => 0,
            'tardiness_minutes' => 0,
            'is_late' => 0,
            'early_departure_minutes' => 0,
            'is_absent' => 0,
            'absence_type' => null,
            'is_working_day' => 0,
            'is_holiday' => 0,
            'is_weekend' => 0,
            'day_type' => 'UNKNOWN',
            'lunch_time_minutes' => 0,
            'lunch_exceeded_minutes' => 0,
            'is_perfect_attendance' => 0,
            'punctuality_score' => 0,
            'notes' => '',
            'calculation_details' => null,
            'calculation_version' => 'v1.0'
        ];
    }

    /**
     * Retornar cálculo parcial para casos sin hora de salida
     *
     * @param array $attendance
     * @param array|null $schedule
     * @param array $dayInfo
     * @return array
     */
    private function getPartialCalculation($attendance, $schedule, $dayInfo)
    {
        $timeIn = $attendance['time_in'];
        $lunchOut = $attendance['lunch_out'] ?? null;
        $lunchIn = $attendance['lunch_in'] ?? null;
        $tardinessMinutes = 0;
        $isLate = false;

        if ($schedule && $timeIn) {
            $tardinessMinutes = $this->scheduleResolver->calculateTardinessMinutes(
                $timeIn,
                $schedule['time_in']
            );
            $isLate = $tardinessMinutes > 0;
        }

        // Calcular duración de almuerzo incluso sin hora de salida
        $lunchTimeMinutes = $this->calculateLunchDuration($lunchOut, $lunchIn);

        return [
            'attendance_detail_id' => $attendance['id'],
            'employee_id' => $attendance['employee_id'],
            'date' => $attendance['date'],
            'schedule_id' => $schedule['schedule_id'] ?? null,
            'time_in' => $this->extractTime($timeIn),
            'time_out' => null,
            'lunch_out' => $this->extractTime($lunchOut),
            'lunch_in' => $this->extractTime($lunchIn),
            'scheduled_time_in' => $schedule['time_in'] ?? null,
            'scheduled_time_out' => $schedule['time_out'] ?? null,
            'scheduled_lunch_out' => $schedule['salida_almuerzo'] ?? null,
            'scheduled_lunch_in' => $schedule['entrada_almuerzo'] ?? null,
            'total_hours' => 0,
            'regular_hours' => 0,
            'overtime_hours' => 0,
            'overtime_25_hours' => 0,
            'overtime_50_hours' => 0,
            'night_hours' => 0,
            'holiday_hours' => 0,
            'tardiness_minutes' => $tardinessMinutes,
            'is_late' => $isLate ? 1 : 0,
            'early_departure_minutes' => 0,
            'is_absent' => 0,
            'absence_type' => null,
            'is_working_day' => $dayInfo['day_type'] === 'LABORAL' ? 1 : 0,
            'is_holiday' => $dayInfo['is_holiday'] ? 1 : 0,
            'is_weekend' => $this->isWeekend($attendance['date']) ? 1 : 0,
            'day_type' => $dayInfo['day_type'],
            'lunch_time_minutes' => $lunchTimeMinutes,
            'lunch_exceeded_minutes' => 0,
            'is_perfect_attendance' => 0,
            'punctuality_score' => 0,
            'notes' => 'Sin hora de salida registrada',
            'calculation_details' => json_encode(['status' => 'incomplete', 'reason' => 'no_time_out']),
            'calculation_version' => 'v1.0',
            'calculated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Recalcular una asistencia existente
     *
     * @param int $attendanceId
     * @return array|null
     */
    public function recalculate($attendanceId)
    {
        $attendance = $this->attendanceModel->find($attendanceId);
        if (!$attendance) {
            return null;
        }

        return $this->calculate($attendance);
    }

    /**
     * Guardar o actualizar cálculo en la tabla attendance_calculations
     *
     * @param array $calculation Array con todos los datos calculados
     * @return int ID del registro insertado/actualizado
     * @throws Exception Si falla la operación
     */
    public function saveCalculation(array $calculation): int
    {
        try {
            // Verificar si ya existe un cálculo para esta marcación
            $existing = $this->getExistingCalculation($calculation['attendance_detail_id']);

            if ($existing) {
                // Actualizar (recalcular)
                return $this->updateCalculation($existing['id'], $calculation);
            } else {
                // Insertar nuevo
                return $this->insertCalculation($calculation);
            }

        } catch (Exception $e) {
            error_log("Error guardando cálculo de asistencia: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Guardar cálculo y retornar cálculo completo con ID
     *
     * @param array $calculation Array del cálculo
     * @return array Cálculo con el ID agregado
     */
    public function calculateAndSave($attendance): array
    {
        // Calcular
        $calculation = $this->calculate($attendance);

        // Guardar en BD
        $calculationId = $this->saveCalculation($calculation);

        // Agregar ID al resultado
        $calculation['calculation_id'] = $calculationId;

        return $calculation;
    }

    /**
     * Inserta un nuevo cálculo en la BD
     *
     * @param array $calculation
     * @return int ID insertado
     */
    private function insertCalculation(array $calculation): int
    {
        $sql = "INSERT INTO attendance_calculations (
            attendance_detail_id, employee_id, schedule_id, date,
            time_in, time_out, scheduled_time_in, scheduled_time_out,
            total_hours, regular_hours, overtime_hours,
            overtime_25_hours, overtime_50_hours, night_hours, holiday_hours,
            tardiness_minutes, is_late, early_departure_minutes,
            is_absent, absence_type,
            is_working_day, is_holiday, is_weekend, day_type,
            is_perfect_attendance, punctuality_score,
            lunch_time_minutes, calculation_version, calculated_at,
            notes, calculation_details
        ) VALUES (
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?,
            ?, ?, ?, ?,
            ?, ?,
            ?, ?, ?,
            ?, ?
        )";

        $params = [
            $calculation['attendance_detail_id'],
            $calculation['employee_id'],
            $calculation['schedule_id'],
            $calculation['date'],
            $calculation['time_in'],
            $calculation['time_out'],
            $calculation['scheduled_time_in'],
            $calculation['scheduled_time_out'],
            $calculation['total_hours'],
            $calculation['regular_hours'],
            $calculation['overtime_hours'],
            $calculation['overtime_25_hours'],
            $calculation['overtime_50_hours'],
            $calculation['night_hours'],
            $calculation['holiday_hours'],
            $calculation['tardiness_minutes'],
            $calculation['is_late'],
            $calculation['early_departure_minutes'],
            $calculation['is_absent'],
            $calculation['absence_type'],
            $calculation['is_working_day'],
            $calculation['is_holiday'],
            $calculation['is_weekend'],
            $calculation['day_type'],
            $calculation['is_perfect_attendance'],
            $calculation['punctuality_score'],
            $calculation['lunch_time_minutes'],
            $calculation['calculation_version'],
            $calculation['calculated_at'],
            $calculation['notes'] ?? null,
            $calculation['calculation_details'] ?? null,
        ];

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualiza un cálculo existente (recalcular)
     *
     * @param int $id ID del registro a actualizar
     * @param array $calculation Datos del cálculo
     * @return int ID actualizado
     */
    private function updateCalculation(int $id, array $calculation): int
    {
        $sql = "UPDATE attendance_calculations SET
            time_in = ?, time_out = ?,
            scheduled_time_in = ?, scheduled_time_out = ?,
            total_hours = ?, regular_hours = ?, overtime_hours = ?,
            overtime_25_hours = ?, overtime_50_hours = ?,
            night_hours = ?, holiday_hours = ?,
            tardiness_minutes = ?, is_late = ?, early_departure_minutes = ?,
            is_absent = ?, absence_type = ?,
            is_working_day = ?, is_holiday = ?, is_weekend = ?, day_type = ?,
            is_perfect_attendance = ?, punctuality_score = ?,
            lunch_time_minutes = ?, calculation_version = ?,
            recalculated_at = ?,
            notes = ?, calculation_details = ?
        WHERE id = ?";

        $params = [
            $calculation['time_in'],
            $calculation['time_out'],
            $calculation['scheduled_time_in'],
            $calculation['scheduled_time_out'],
            $calculation['total_hours'],
            $calculation['regular_hours'],
            $calculation['overtime_hours'],
            $calculation['overtime_25_hours'],
            $calculation['overtime_50_hours'],
            $calculation['night_hours'],
            $calculation['holiday_hours'],
            $calculation['tardiness_minutes'],
            $calculation['is_late'],
            $calculation['early_departure_minutes'],
            $calculation['is_absent'],
            $calculation['absence_type'],
            $calculation['is_working_day'],
            $calculation['is_holiday'],
            $calculation['is_weekend'],
            $calculation['day_type'],
            $calculation['is_perfect_attendance'],
            $calculation['punctuality_score'],
            $calculation['lunch_time_minutes'],
            $calculation['calculation_version'],
            date('Y-m-d H:i:s'), // recalculated_at
            $calculation['notes'] ?? null,
            $calculation['calculation_details'] ?? null,
            $id,
        ];

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $id;
    }

    /**
     * Obtiene un cálculo existente por attendance_detail_id
     *
     * @param int $attendanceDetailId
     * @return array|null
     */
    private function getExistingCalculation(int $attendanceDetailId): ?array
    {
        $sql = "SELECT * FROM attendance_calculations WHERE attendance_detail_id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$attendanceDetailId]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Procesar y guardar cálculos para múltiples asistencias en batch
     *
     * @param array $attendances Array de registros de asistencia
     * @param bool $saveToDb Si debe guardar en BD (default: true)
     * @return array Array de cálculos con IDs
     */
    public function calculateAndSaveBulk(array $attendances, bool $saveToDb = true): array
    {
        $calculations = [];
        $savedCount = 0;
        $errors = [];

        foreach ($attendances as $attendance) {
            try {
                if ($saveToDb) {
                    $calculation = $this->calculateAndSave($attendance);
                    $savedCount++;
                } else {
                    $calculation = $this->calculate($attendance);
                }

                $calculations[] = $calculation;

            } catch (Exception $e) {
                $errors[] = [
                    'attendance_detail_id' => $attendance['id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];
                error_log("Error procesando asistencia ID {$attendance['id']}: " . $e->getMessage());
            }
        }

        // Log de resultados
        $totalProcessed = count($attendances);
        error_log("AttendanceCalculator Bulk: Procesados {$totalProcessed}, Guardados {$savedCount}, Errores: " . count($errors));

        return [
            'calculations' => $calculations,
            'stats' => [
                'total_processed' => $totalProcessed,
                'saved' => $savedCount,
                'errors' => count($errors),
            ],
            'errors' => $errors,
        ];
    }

    /**
     * Obtiene el cálculo guardado de una marcación
     *
     * @param int $attendanceDetailId
     * @return array|null
     */
    public function getCalculation(int $attendanceDetailId): ?array
    {
        return $this->getExistingCalculation($attendanceDetailId);
    }

    /**
     * Elimina un cálculo de la BD
     *
     * @param int $attendanceDetailId
     * @return bool
     */
    public function deleteCalculation(int $attendanceDetailId): bool
    {
        try {
            $sql = "DELETE FROM attendance_calculations WHERE attendance_detail_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$attendanceDetailId]);

            return $stmt->rowCount() > 0;

        } catch (Exception $e) {
            error_log("Error eliminando cálculo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene la configuración actual del calculador
     *
     * @return array Configuraciones
     */
    public function getConfig(): array
    {
        return [
            'version' => 'v1.0',
            'tardiness_tolerance' => 'Delegado a WorkScheduleResolver',
            'lunch_time' => 60,
            'working_day_classifier' => 'Integrado',
            'overtime_calculator' => 'Integrado',
            'schedule_resolver' => 'Integrado',
            'alerts_system' => 'Integrado',
        ];
    }

    /**
     * Verificar cumplimiento legal y generar alertas automáticas
     *
     * @param array $calculation Cálculo de asistencia
     * @param bool $generateAlerts Si debe generar alertas automáticamente (default: true)
     * @return array Resultado del chequeo de cumplimiento con estadísticas de alertas
     */
    public function checkLegalComplianceAndAlert(array $calculation, bool $generateAlerts = true): array
    {
        // Preparar datos para el LegalComplianceChecker
        $attendanceData = [
            'date' => $calculation['date'],
            'daily_hours' => $calculation['total_hours'],
            'total_hours' => $calculation['total_hours'],
            'lunch_minutes' => $calculation['lunch_time_minutes'],
            'time_in' => $calculation['time_in'],
            'time_out' => $calculation['time_out'],
            'night_hours' => $calculation['night_hours'],
            'hours_worked' => $calculation['total_hours'],
        ];

        // Ejecutar validación de cumplimiento legal
        $complianceResult = $this->complianceChecker->performFullCompliance($attendanceData);

        // Si hay violaciones o warnings y se solicita generar alertas
        if ($generateAlerts && ($complianceResult['total_violations'] > 0 || $complianceResult['total_warnings'] > 0)) {
            // Generar alertas automáticamente
            $alertsStats = $this->alertsSystem->generateAlertsFromCompliance(
                $calculation['employee_id'],
                $complianceResult
            );

            $complianceResult['alerts_generated'] = $alertsStats;

            error_log(sprintf(
                "AlertsSystem: Generadas %d alertas para empleado %d (Fecha: %s)",
                $alertsStats['total_generated'],
                $calculation['employee_id'],
                $calculation['date']
            ));
        } else {
            $complianceResult['alerts_generated'] = [
                'total_generated' => 0,
                'skipped_duplicates' => 0,
                'reason' => 'no_violations_or_warnings'
            ];
        }

        return $complianceResult;
    }

    /**
     * Calcular, guardar y generar alertas automáticas (flujo completo)
     *
     * @param array $attendance Registro de asistencia
     * @param bool $generateAlerts Si debe generar alertas (default: true)
     * @return array Cálculo completo con resultado de cumplimiento y alertas
     */
    public function calculateSaveAndAlert(array $attendance, bool $generateAlerts = true): array
    {
        // 1. Calcular asistencia
        $calculation = $this->calculate($attendance);

        // 2. Guardar en BD
        $calculationId = $this->saveCalculation($calculation);
        $calculation['calculation_id'] = $calculationId;

        // 3. Verificar cumplimiento legal y generar alertas
        $complianceResult = $this->checkLegalComplianceAndAlert($calculation, $generateAlerts);

        // Agregar información de cumplimiento al resultado
        $calculation['legal_compliance'] = [
            'overall_compliant' => $complianceResult['overall_compliant'],
            'total_violations' => $complianceResult['total_violations'],
            'total_warnings' => $complianceResult['total_warnings'],
            'risk_level' => $complianceResult['summary']['risk_level'] ?? 'NINGUNO',
            'alerts_generated' => $complianceResult['alerts_generated']['total_generated'] ?? 0,
        ];

        return $calculation;
    }

    /**
     * Procesar bulk con alertas automáticas
     *
     * @param array $attendances Array de asistencias
     * @param bool $saveToDb Si debe guardar en BD
     * @param bool $generateAlerts Si debe generar alertas
     * @return array Resultado con estadísticas
     */
    public function calculateSaveAndAlertBulk(array $attendances, bool $saveToDb = true, bool $generateAlerts = true): array
    {
        $calculations = [];
        $savedCount = 0;
        $errors = [];
        $totalAlerts = 0;
        $totalViolations = 0;

        foreach ($attendances as $attendance) {
            try {
                if ($saveToDb && $generateAlerts) {
                    // Flujo completo con alertas
                    $calculation = $this->calculateSaveAndAlert($attendance, $generateAlerts);
                    $savedCount++;
                    $totalAlerts += $calculation['legal_compliance']['alerts_generated'] ?? 0;
                    $totalViolations += $calculation['legal_compliance']['total_violations'] ?? 0;
                } elseif ($saveToDb) {
                    // Solo calcular y guardar, sin alertas
                    $calculation = $this->calculateAndSave($attendance);
                    $savedCount++;
                } else {
                    // Solo calcular, sin guardar ni alertas
                    $calculation = $this->calculate($attendance);
                }

                $calculations[] = $calculation;

            } catch (Exception $e) {
                $errors[] = [
                    'attendance_detail_id' => $attendance['id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];
                error_log("Error procesando asistencia ID {$attendance['id']}: " . $e->getMessage());
            }
        }

        // Log de resultados
        $totalProcessed = count($attendances);
        error_log(sprintf(
            "AttendanceCalculator Bulk: Procesados %d, Guardados %d, Alertas %d, Violaciones %d, Errores: %d",
            $totalProcessed,
            $savedCount,
            $totalAlerts,
            $totalViolations,
            count($errors)
        ));

        return [
            'calculations' => $calculations,
            'stats' => [
                'total_processed' => $totalProcessed,
                'saved' => $savedCount,
                'errors' => count($errors),
                'total_alerts_generated' => $totalAlerts,
                'total_violations' => $totalViolations,
            ],
            'errors' => $errors,
        ];
    }

    /**
     * Obtener alertas de un empleado
     *
     * @param int $employeeId ID del empleado
     * @param string|null $severity Filtrar por severidad
     * @return array Lista de alertas
     */
    public function getEmployeeAlerts(int $employeeId, ?string $severity = null): array
    {
        return $this->alertsSystem->getEmployeePendingAlerts($employeeId, $severity);
    }

    /**
     * Obtener estadísticas de alertas de un empleado
     *
     * @param int $employeeId ID del empleado
     * @param int $days Días hacia atrás
     * @return array Estadísticas
     */
    public function getEmployeeAlertStats(int $employeeId, int $days = 30): array
    {
        return $this->alertsSystem->getEmployeeAlertStats($employeeId, $days);
    }
}
