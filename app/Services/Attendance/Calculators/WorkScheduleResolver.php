<?php

namespace App\Services\Attendance\Calculators;

use App\Models\Employee;
use App\Models\EmployeeDailySchedule;
use App\Models\Schedule;
use DateTime;

/**
 * WorkScheduleResolver
 *
 * Resuelve el horario aplicable a un empleado en una fecha específica.
 * Considera:
 * - Horario base del empleado
 * - Excepciones por fecha (futuro: horarios especiales)
 * - Días no laborables
 *
 * @package App\Services\Attendance\Calculators
 * @version 1.0
 */
class WorkScheduleResolver
{
    private $employeeModel;
    private $scheduleModel;
    private $dailyScheduleModel;

    public function __construct()
    {
        $this->employeeModel      = new Employee();
        $this->scheduleModel      = new Schedule();
        $this->dailyScheduleModel = new EmployeeDailySchedule();
    }

    /**
     * Obtener horario aplicable para un empleado en una fecha específica
     *
     * @param int $employeeId ID del empleado
     * @param string $date Fecha en formato Y-m-d
     * @return array|null Array con información del horario o null si no aplica
     */
    public function getScheduleForEmployeeOnDate($employeeId, $date)
    {
        // 1. Verificar si existe override personal para este empleado y fecha
        $override = $this->dailyScheduleModel->getForDate($employeeId, $date);

        if ($override) {
            // Usar el horario del calendario personal (override)
            return [
                'schedule_id'                => $override['schedule_id'],
                'codigo'                     => $override['codigo'] ?? null,
                'nombre'                     => $override['nombre'] ?? null,
                'time_in'                    => $override['time_in'],
                'time_out'                   => $override['time_out'],
                'salida_almuerzo'            => $override['salida_almuerzo'] ?? null,
                'entrada_almuerzo'           => $override['entrada_almuerzo'] ?? null,
                'time_in_tolerance_before'   => $override['time_in_tolerance_before'] ?? 0,
                'time_in_tolerance_after'    => $override['time_in_tolerance_after'] ?? 0,
                'time_out_tolerance_before'  => $override['time_out_tolerance_before'] ?? 0,
                'time_out_tolerance_after'   => $override['time_out_tolerance_after'] ?? 0,
                'lunch_out_tolerance_before' => $override['lunch_out_tolerance_before'] ?? 0,
                'lunch_out_tolerance_after'  => $override['lunch_out_tolerance_after'] ?? 0,
                'lunch_in_tolerance_before'  => $override['lunch_in_tolerance_before'] ?? 0,
                'lunch_in_tolerance_after'   => $override['lunch_in_tolerance_after'] ?? 0,
                'lunch_flexible'             => (int)($override['lunch_flexible'] ?? 0),
                'lunch_flexible_minutes'     => (int)($override['lunch_flexible_minutes'] ?? 0),
                'date'                       => $date,
                'employee_id'               => $employeeId,
                'is_personal_override'       => true,  // flag: ignorar día no laboral del calendario empresarial
            ];
        }

        // 2. Sin override: usar horario base del empleado
        $employee = $this->employeeModel->find($employeeId);
        if (!$employee || empty($employee['schedule_id'])) {
            return null;
        }

        $schedule = $this->scheduleModel->find($employee['schedule_id']);
        if (!$schedule) {
            return null;
        }

        return [
            'schedule_id'                => $schedule['id'],
            'codigo'                     => $schedule['codigo'] ?? null,
            'nombre'                     => $schedule['nombre'] ?? null,
            'time_in'                    => $schedule['time_in'],
            'time_out'                   => $schedule['time_out'],
            'salida_almuerzo'            => $schedule['salida_almuerzo'] ?? null,
            'entrada_almuerzo'           => $schedule['entrada_almuerzo'] ?? null,
            'time_in_tolerance_before'   => $schedule['time_in_tolerance_before'] ?? 0,
            'time_in_tolerance_after'    => $schedule['time_in_tolerance_after'] ?? 0,
            'time_out_tolerance_before'  => $schedule['time_out_tolerance_before'] ?? 0,
            'time_out_tolerance_after'   => $schedule['time_out_tolerance_after'] ?? 0,
            'lunch_out_tolerance_before' => $schedule['lunch_out_tolerance_before'] ?? 0,
            'lunch_out_tolerance_after'  => $schedule['lunch_out_tolerance_after'] ?? 0,
            'lunch_in_tolerance_before'  => $schedule['lunch_in_tolerance_before'] ?? 0,
            'lunch_in_tolerance_after'   => $schedule['lunch_in_tolerance_after'] ?? 0,
            'lunch_flexible'             => (int)($schedule['lunch_flexible'] ?? 0),
            'lunch_flexible_minutes'     => (int)($schedule['lunch_flexible_minutes'] ?? 0),
            'date'                       => $date,
            'employee_id'               => $employeeId,
            'is_personal_override'       => false,
        ];
    }

    /**
     * Verificar si un empleado tiene horario asignado
     *
     * @param int $employeeId ID del empleado
     * @return bool
     */
    public function hasSchedule($employeeId)
    {
        $employee = $this->employeeModel->find($employeeId);
        return $employee && !empty($employee['schedule_id']);
    }

    /**
     * Obtener todos los horarios disponibles en el sistema
     *
     * @return array
     */
    public function getAllSchedules()
    {
        return $this->scheduleModel->findAll();
    }

    /**
     * Calcular minutos de almuerzo aplicando tolerancias configuradas.
     *
     * Regla:
     * - Se parte de la duración programada (scheduledDuration).
     * - Si la salida a almuerzo ocurre ANTES de (salida_programada - tol_before) se suma el exceso a la deducción.
     * - Si el retorno ocurre DESPUÉS de (entrada_programada + tol_after) se suma el exceso a la deducción.
     * - Si la salida ocurre DESPUÉS de (salida_programada + tol_after) se resta el exceso (acorta el almuerzo).
     * - Si el retorno ocurre ANTES de (entrada_programada - tol_before) se resta el exceso (acorta el almuerzo).
     *
     * @param string|null $actualOut     Timestamp real de salida a almuerzo (Y-m-d H:i:s o H:i:s)
     * @param string|null $actualIn      Timestamp real de retorno de almuerzo
     * @param string|null $scheduledOut  Hora programada de salida a almuerzo (H:i:s)
     * @param string|null $scheduledIn   Hora programada de retorno de almuerzo (H:i:s)
     * @param int $tolOutBefore          Minutos de tolerancia antes de salida a almuerzo
     * @param int $tolOutAfter           Minutos de tolerancia después de salida a almuerzo
     * @param int $tolInBefore           Minutos de tolerancia antes de entrada de almuerzo
     * @param int $tolInAfter            Minutos de tolerancia después de entrada de almuerzo
     * @param string|null $baseDate      Fecha base (Y-m-d) para normalizar timestamps sin fecha
     * @return array [ 'lunch_minutes' => int, 'exceeded_minutes' => int, 'scheduled_minutes' => int ]
     */
    public function calculateLunchWithTolerance(
        ?string $actualOut,
        ?string $actualIn,
        ?string $scheduledOut,
        ?string $scheduledIn,
        int $tolOutBefore = 0,
        int $tolOutAfter = 0,
        int $tolInBefore = 0,
        int $tolInAfter = 0,
        ?string $baseDate = null
    ): array {
        // Si no hay horario programado de almuerzo, no aplicar tolerancias
        if (empty($scheduledOut) || empty($scheduledIn)) {
            return [ 'lunch_minutes' => 0, 'exceeded_minutes' => 0, 'scheduled_minutes' => 0 ];
        }

        // Si faltan marcaciones reales, usar duración programada y sin exceso
        if (empty($actualOut) || empty($actualIn)) {
            $scheduledMinutes = $this->diffMinutes($scheduledOut, $scheduledIn);
            return [ 'lunch_minutes' => $scheduledMinutes, 'exceeded_minutes' => 0, 'scheduled_minutes' => $scheduledMinutes ];
        }

        $scheduledMinutes = $this->diffMinutes($scheduledOut, $scheduledIn);

        // Construir ventanas de tolerancia
        $winOutStart = $this->modifyTime($scheduledOut, -$tolOutBefore, $baseDate);
        $winOutEnd   = $this->modifyTime($scheduledOut, +$tolOutAfter, $baseDate);
        $winInStart  = $this->modifyTime($scheduledIn, -$tolInBefore, $baseDate);
        $winInEnd    = $this->modifyTime($scheduledIn, +$tolInAfter, $baseDate);

        // Normalizar formatos a DateTime completos si vienen en HH:MM:SS
        $actualOutDT = $this->toDateTime($actualOut, $baseDate);
        $actualInDT  = $this->toDateTime($actualIn, $baseDate);
        $winOutStartDT = $this->toDateTime($winOutStart, $baseDate);
        $winOutEndDT   = $this->toDateTime($winOutEnd, $baseDate);
        $winInStartDT  = $this->toDateTime($winInStart, $baseDate);
        $winInEndDT    = $this->toDateTime($winInEnd, $baseDate);
        $scheduledOutDT= $this->toDateTime($scheduledOut, $baseDate);
        $scheduledInDT = $this->toDateTime($scheduledIn, $baseDate);

        // Excesos positivos (extienden el almuerzo)
        $positiveExtra = 0;
        if ($actualOutDT < $winOutStartDT) {
            $positiveExtra += $this->diffMinutesDT($actualOutDT, $winOutStartDT);
        }
        if ($actualInDT > $winInEndDT) {
            $positiveExtra += $this->diffMinutesDT($winInEndDT, $actualInDT);
        }

        // Excesos negativos (acortan el almuerzo)
        $negativeExtra = 0;
        if ($actualOutDT > $winOutEndDT) {
            $negativeExtra += $this->diffMinutesDT($winOutEndDT, $actualOutDT);
        }
        if ($actualInDT < $winInStartDT) {
            $negativeExtra += $this->diffMinutesDT($actualInDT, $winInStartDT);
        }

        $lunchMinutes = max(0, $scheduledMinutes + $positiveExtra - $negativeExtra);
        $exceeded = $positiveExtra; // Exceso de almuerzo solo cuando se excede fuera de tolerancia

        return [
            'lunch_minutes' => (int)$lunchMinutes,
            'exceeded_minutes' => (int)$exceeded,
            'scheduled_minutes' => (int)$scheduledMinutes
        ];
    }

    // ==========================
    // Helpers internos de tiempo
    // ==========================
    private function toDateTime(string $timeOrDate, ?string $baseDate = null): \DateTime
    {
        // Si viene solo hora, usar fecha de hoy
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $timeOrDate)) {
            $timeOrDate = ($baseDate ?: date('Y-m-d')) . ' ' . $timeOrDate;
        }
        return new \DateTime($timeOrDate);
    }

    private function modifyTime(string $baseTime, int $minutes, ?string $baseDate = null): string
    {
        $dt = $this->toDateTime($baseTime, $baseDate);
        $op = $minutes >= 0 ? "+{$minutes} minutes" : "{$minutes} minutes";
        $dt->modify($op);
        return $dt->format('H:i:s');
    }

    private function diffMinutes(string $timeA, string $timeB): int
    {
        $a = $this->toDateTime($timeA);
        $b = $this->toDateTime($timeB);
        $diff = abs($b->getTimestamp() - $a->getTimestamp());
        return (int) round($diff / 60);
    }

    private function diffMinutesDT(\DateTime $a, \DateTime $b): int
    {
        $diff = abs($b->getTimestamp() - $a->getTimestamp());
        return (int) round($diff / 60);
    }

    /**
     * Calcular duración esperada de jornada en horas
     *
     * @param array $schedule Array con time_in y time_out
     * @param int $lunchMinutes Minutos de almuerzo (default 60)
     * @return float Horas esperadas de trabajo
     */
    public function calculateExpectedWorkHours($schedule, $lunchMinutes = 60)
    {
        if (!$schedule || empty($schedule['time_in']) || empty($schedule['time_out'])) {
            return 0;
        }

        $timeIn = new DateTime($schedule['time_in']);
        $timeOut = new DateTime($schedule['time_out']);

        // Si time_out es menor que time_in, asumimos que cruza medianoche
        if ($timeOut < $timeIn) {
            $timeOut->modify('+1 day');
        }

        $interval = $timeIn->diff($timeOut);
        $totalMinutes = ($interval->h * 60) + $interval->i;

        // Descontar almuerzo
        $workMinutes = $totalMinutes - $lunchMinutes;

        // Convertir a horas decimales
        return round($workMinutes / 60, 2);
    }

    /**
     * Verificar si una hora de entrada está dentro del horario permitido
     *
     * @param string $actualTimeIn Hora real de entrada (HH:MM:SS)
     * @param string $scheduledTimeIn Hora programada (HH:MM:SS)
     * @param int $toleranceMinutes Minutos de tolerancia
     * @return bool
     */
    public function isOnTime($actualTimeIn, $scheduledTimeIn, $toleranceMinutes = 5)
    {
        $actual = new DateTime($actualTimeIn);
        $scheduled = new DateTime($scheduledTimeIn);
        $scheduled->modify("+{$toleranceMinutes} minutes");

        return $actual <= $scheduled;
    }

    /**
     * Calcular minutos de tardanza (sin tolerancia)
     *
     * @param string $actualTimeIn Hora real de entrada (HH:MM:SS)
     * @param string $scheduledTimeIn Hora programada (HH:MM:SS)
     * @return int Minutos de tardanza (0 si llegó a tiempo)
     */
    public function calculateTardinessMinutes($actualTimeIn, $scheduledTimeIn)
    {
        $actual = new DateTime($actualTimeIn);
        $scheduled = new DateTime($scheduledTimeIn);

        if ($actual <= $scheduled) {
            return 0;
        }

        $interval = $scheduled->diff($actual);
        return ($interval->h * 60) + $interval->i;
    }

    /**
     * Calcular minutos de tardanza considerando tolerancia
     *
     * @param string $actualTimeIn Hora real de entrada (HH:MM:SS)
     * @param string $scheduledTimeIn Hora programada (HH:MM:SS)
     * @param int $toleranceAfter Minutos de tolerancia después de la hora programada
     * @return int Minutos de tardanza después de aplicar tolerancia (0 si está dentro de tolerancia)
     */
    public function calculateTardinessWithTolerance($actualTimeIn, $scheduledTimeIn, $toleranceAfter = 0)
    {
        $actual = new DateTime($actualTimeIn);
        $scheduled = new DateTime($scheduledTimeIn);

        // Agregar tolerancia a la hora programada
        $scheduledWithTolerance = clone $scheduled;
        $scheduledWithTolerance->modify("+{$toleranceAfter} minutes");

        // Si llegó antes o dentro de la tolerancia, no hay tardanza
        if ($actual <= $scheduledWithTolerance) {
            return 0;
        }

        // Calcular minutos de tardanza después de la tolerancia
        $interval = $scheduledWithTolerance->diff($actual);
        return ($interval->h * 60) + $interval->i;
    }

    /**
     * Calcular minutos de salida anticipada (sin tolerancia)
     *
     * @param string $actualTimeOut Hora real de salida (HH:MM:SS)
     * @param string $scheduledTimeOut Hora programada (HH:MM:SS)
     * @return int Minutos de salida anticipada (0 si salió a tiempo o después)
     */
    public function calculateEarlyDepartureMinutes($actualTimeOut, $scheduledTimeOut)
    {
        if (empty($actualTimeOut) || empty($scheduledTimeOut)) {
            return 0;
        }

        $actual = new DateTime($actualTimeOut);
        $scheduled = new DateTime($scheduledTimeOut);

        if ($actual >= $scheduled) {
            return 0;
        }

        $interval = $actual->diff($scheduled);
        return ($interval->h * 60) + $interval->i;
    }

    /**
     * Calcular minutos de salida anticipada considerando tolerancia
     *
     * @param string $actualTimeOut Hora real de salida (HH:MM:SS)
     * @param string $scheduledTimeOut Hora programada (HH:MM:SS)
     * @param int $toleranceBefore Minutos de tolerancia antes de la hora programada de salida
     * @return int Minutos de salida anticipada después de aplicar tolerancia (0 si está dentro de tolerancia)
     */
    public function calculateEarlyDepartureWithTolerance($actualTimeOut, $scheduledTimeOut, $toleranceBefore = 0)
    {
        if (empty($actualTimeOut) || empty($scheduledTimeOut)) {
            return 0;
        }

        $actual = new DateTime($actualTimeOut);
        $scheduled = new DateTime($scheduledTimeOut);

        // Restar tolerancia a la hora programada (puede salir X minutos antes)
        $scheduledWithTolerance = clone $scheduled;
        $scheduledWithTolerance->modify("-{$toleranceBefore} minutes");

        // Si salió después o dentro de la tolerancia, no hay salida anticipada
        if ($actual >= $scheduledWithTolerance) {
            return 0;
        }

        // Calcular minutos de salida anticipada después de aplicar tolerancia
        $interval = $actual->diff($scheduledWithTolerance);
        return ($interval->h * 60) + $interval->i;
    }

    /**
     * Obtener información del horario de un empleado
     * Alias conveniente para getScheduleForEmployeeOnDate
     *
     * @param int $employeeId
     * @return array|null
     */
    public function getEmployeeSchedule($employeeId)
    {
        $employee = $this->employeeModel->find($employeeId);
        if (!$employee || empty($employee['schedule_id'])) {
            return null;
        }

        return $this->scheduleModel->find($employee['schedule_id']);
    }

    /**
     * Verificar si un horario es de jornada nocturna
     * Jornada nocturna: 6:00 PM - 6:00 AM (Código de Trabajo Art. 38)
     *
     * @param array $schedule Array con time_in y time_out
     * @return bool
     */
    public function isNightShift($schedule)
    {
        if (!$schedule || empty($schedule['time_in'])) {
            return false;
        }

        $timeIn = new DateTime($schedule['time_in']);
        $hour = (int)$timeIn->format('H');

        // Jornada nocturna: entrada entre 18:00 y 06:00
        return $hour >= 18 || $hour < 6;
    }

    /**
     * Obtener resumen del horario en formato legible
     *
     * @param array $schedule
     * @return string
     */
    public function getScheduleSummary($schedule)
    {
        if (!$schedule) {
            return 'Sin horario asignado';
        }

        $timeIn = date('g:i A', strtotime($schedule['time_in']));
        $timeOut = date('g:i A', strtotime($schedule['time_out']));

        $nombre = $schedule['nombre'] ?? 'Horario';
        $codigo = $schedule['codigo'] ?? '';

        return "{$nombre} ({$codigo}): {$timeIn} - {$timeOut}";
    }
}
