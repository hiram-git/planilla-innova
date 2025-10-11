<?php

namespace App\Services\Attendance\Calculators;

use App\Models\Attendance;
use App\Models\BusinessCalendar;
use DateTime;

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

    public function __construct()
    {
        $this->scheduleResolver = new WorkScheduleResolver();
        $this->overtimeCalculator = new OvertimeCalculator();
        $this->attendanceModel = new Attendance();
        $this->businessCalendar = new BusinessCalendar();
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

        // Obtener horario del empleado para ese día
        $schedule = $this->scheduleResolver->getScheduleForEmployeeOnDate($employeeId, $date);

        // Verificar tipo de día (laboral, feriado, etc.)
        $dayInfo = $this->overtimeCalculator->checkHolidayStatus($date);

        // Si no hay salida, no podemos calcular horas
        if (empty($timeOut)) {
            return $this->getPartialCalculation($attendance, $schedule, $dayInfo);
        }

        // Calcular breakdown completo de horas
        $hoursBreakdown = $this->overtimeCalculator->calculateCompleteBreakdown(
            $timeIn,
            $timeOut,
            $date,
            $schedule ? $this->scheduleResolver->calculateExpectedWorkHours($schedule) : 8
        );

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
            'attendance_id' => $attendance['id'],
            'employee_id' => $employeeId,
            'date' => $date,
            'schedule_id' => $schedule['schedule_id'] ?? null,

            // Marcaciones
            'time_in' => $this->extractTime($timeIn),
            'time_out' => $this->extractTime($timeOut),
            'scheduled_time_in' => $schedule['time_in'] ?? null,
            'scheduled_time_out' => $schedule['time_out'] ?? null,

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
            'lunch_time_minutes' => 60,
            'is_perfect_attendance' => $isPerfectAttendance ? 1 : 0,
            'punctuality_score' => $punctualityScore,

            // Notas
            'notes' => $this->generateNotes($isLate, $tardinessMinutes, $hoursBreakdown),
            'calculation_details' => json_encode([
                'schedule' => $schedule,
                'day_info' => $dayInfo,
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
            'attendance_id' => null,
            'employee_id' => null,
            'date' => null,
            'schedule_id' => null,
            'time_in' => null,
            'time_out' => null,
            'scheduled_time_in' => null,
            'scheduled_time_out' => null,
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
            'lunch_time_minutes' => 60,
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
        $tardinessMinutes = 0;
        $isLate = false;

        if ($schedule && $timeIn) {
            $tardinessMinutes = $this->scheduleResolver->calculateTardinessMinutes(
                $timeIn,
                $schedule['time_in']
            );
            $isLate = $tardinessMinutes > 0;
        }

        return [
            'attendance_id' => $attendance['id'],
            'employee_id' => $attendance['employee_id'],
            'date' => $attendance['date'],
            'schedule_id' => $schedule['schedule_id'] ?? null,
            'time_in' => $this->extractTime($timeIn),
            'time_out' => null,
            'scheduled_time_in' => $schedule['time_in'] ?? null,
            'scheduled_time_out' => $schedule['time_out'] ?? null,
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
            'lunch_time_minutes' => 60,
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
}
