<?php

namespace App\Services\Attendance\Calculators;

use App\Models\Employee;
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

    public function __construct()
    {
        $this->employeeModel = new Employee();
        $this->scheduleModel = new Schedule();
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
        // Obtener empleado
        $employee = $this->employeeModel->find($employeeId);
        if (!$employee) {
            return null;
        }

        // Verificar si el empleado tiene horario asignado
        if (empty($employee['schedule_id'])) {
            return null;
        }

        // Obtener horario del empleado
        $schedule = $this->scheduleModel->find($employee['schedule_id']);
        if (!$schedule) {
            return null;
        }

        // Preparar resultado con información completa incluyendo tolerancias
        return [
            'schedule_id' => $schedule['id'],
            'codigo' => $schedule['codigo'] ?? null,
            'nombre' => $schedule['nombre'] ?? null,
            'time_in' => $schedule['time_in'],
            'time_out' => $schedule['time_out'],
            'salida_almuerzo' => $schedule['salida_almuerzo'] ?? null,
            'entrada_almuerzo' => $schedule['entrada_almuerzo'] ?? null,
            // Tolerancias de entrada
            'time_in_tolerance_before' => $schedule['time_in_tolerance_before'] ?? 0,
            'time_in_tolerance_after' => $schedule['time_in_tolerance_after'] ?? 0,
            // Tolerancias de salida
            'time_out_tolerance_before' => $schedule['time_out_tolerance_before'] ?? 0,
            'time_out_tolerance_after' => $schedule['time_out_tolerance_after'] ?? 0,
            // Tolerancias de almuerzo
            'lunch_out_tolerance_before' => $schedule['lunch_out_tolerance_before'] ?? 0,
            'lunch_out_tolerance_after' => $schedule['lunch_out_tolerance_after'] ?? 0,
            'lunch_in_tolerance_before' => $schedule['lunch_in_tolerance_before'] ?? 0,
            'lunch_in_tolerance_after' => $schedule['lunch_in_tolerance_after'] ?? 0,
            'date' => $date,
            'employee_id' => $employeeId
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
