<?php

namespace App\Models;

use App\Core\Model;
use PDO;
use PDOException;

class BusinessCalendar extends Model
{
    public $table = 'business_calendar';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Obtener información de un día específico
     */
    public function getDayInfo($date)
    {
        try {
            $sql = "SELECT * FROM business_calendar WHERE date_value = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$date]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting day info: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si un día es laboral
     */
    public function isWorkingDay($date)
    {
        $dayInfo = $this->getDayInfo($date);
        return $dayInfo ? $dayInfo['day_type'] === 'LABORAL' : $this->isWeekday($date);
    }

    /**
     * Obtener días laborables entre dos fechas
     */
    public function getWorkingDaysBetween($startDate, $endDate)
    {
        try {
            $sql = "SELECT COUNT(*) as working_days
                    FROM business_calendar
                    WHERE date_value BETWEEN ? AND ?
                    AND day_type = 'LABORAL'";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$startDate, $endDate]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['working_days'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error getting working days: " . $e->getMessage());
            return $this->calculateWorkingDaysFallback($startDate, $endDate);
        }
    }

    /**
     * Obtener calendario de un mes específico
     */
    public function getMonthCalendar($year, $month)
    {
        try {
            $sql = "SELECT * FROM business_calendar
                    WHERE year_value = ? AND month_value = ?
                    ORDER BY date_value";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$year, $month]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting month calendar: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener feriados de un año
     */
    public function getHolidaysByYear($year)
    {
        try {
            $sql = "SELECT * FROM business_calendar
                    WHERE year_value = ? AND day_type = 'FERIADO'
                    ORDER BY date_value";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$year]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting holidays: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calcular el siguiente día laboral
     */
    public function getNextWorkingDay($date)
    {
        $nextDay = date('Y-m-d', strtotime($date . ' +1 day'));
        $maxAttempts = 10; // Evitar bucle infinito
        $attempts = 0;

        while (!$this->isWorkingDay($nextDay) && $attempts < $maxAttempts) {
            $nextDay = date('Y-m-d', strtotime($nextDay . ' +1 day'));
            $attempts++;
        }

        return $nextDay;
    }

    /**
     * Calcular día laboral anterior
     */
    public function getPreviousWorkingDay($date)
    {
        $prevDay = date('Y-m-d', strtotime($date . ' -1 day'));
        $maxAttempts = 10;
        $attempts = 0;

        while (!$this->isWorkingDay($prevDay) && $attempts < $maxAttempts) {
            $prevDay = date('Y-m-d', strtotime($prevDay . ' -1 day'));
            $attempts++;
        }

        return $prevDay;
    }

    /**
     * Agregar un día especial al calendario
     */
    public function addSpecialDay($date, $dayType, $status, $description)
    {
        try {
            $dateObj = new \DateTime($date);

            $sql = "INSERT INTO business_calendar
                    (date_value, day_type, status, description, is_weekend, year_value, month_value, day_of_week)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    day_type = VALUES(day_type),
                    status = VALUES(status),
                    description = VALUES(description)";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $date,
                $dayType,
                $status,
                $description,
                $this->isWeekend($date),
                $dateObj->format('Y'),
                $dateObj->format('n'),
                $this->getDayOfWeek($date)
            ]);
        } catch (PDOException $e) {
            error_log("Error adding special day: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener resumen estadístico del calendario
     */
    public function getCalendarStats($year)
    {
        try {
            $sql = "SELECT
                        day_type,
                        status,
                        COUNT(*) as count
                    FROM business_calendar
                    WHERE year_value = ?
                    GROUP BY day_type, status
                    ORDER BY day_type, status";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$year]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting calendar stats: " . $e->getMessage());
            return [];
        }
    }

    // ==========================================
    // MÉTODOS AUXILIARES PRIVADOS
    // ==========================================

    /**
     * Verificar si es día de semana (Lunes-Viernes)
     */
    private function isWeekday($date)
    {
        $dayOfWeek = date('N', strtotime($date));
        return $dayOfWeek >= 1 && $dayOfWeek <= 5;
    }

    /**
     * Verificar si es fin de semana
     */
    private function isWeekend($date)
    {
        $dayOfWeek = date('N', strtotime($date));
        return $dayOfWeek == 6 || $dayOfWeek == 7;
    }

    /**
     * Obtener día de la semana (1=Lunes, 7=Domingo)
     */
    private function getDayOfWeek($date)
    {
        return date('N', strtotime($date));
    }

    /**
     * Cálculo fallback de días laborables sin base de datos
     */
    private function calculateWorkingDaysFallback($startDate, $endDate)
    {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $workingDays = 0;

        while ($start <= $end) {
            if ($this->isWeekday($start->format('Y-m-d'))) {
                $workingDays++;
            }
            $start->modify('+1 day');
        }

        return $workingDays;
    }

    /**
     * Obtener colores para diferentes tipos de días
     */
    public static function getDayTypeColors()
    {
        return [
            'LABORAL' => '#28a745',      // Verde
            'NO_LABORAL' => '#6c757d',   // Gris
            'FERIADO' => '#dc3545',      // Rojo
            'DUELO_NACIONAL' => '#343a40', // Negro
            'ESPECIAL' => '#17a2b8'      // Azul
        ];
    }

    /**
     * Obtener iconos para diferentes tipos de días
     */
    public static function getDayTypeIcons()
    {
        return [
            'LABORAL' => 'fas fa-briefcase',
            'NO_LABORAL' => 'fas fa-home',
            'FERIADO' => 'fas fa-calendar-check',
            'DUELO_NACIONAL' => 'fas fa-flag-usa',
            'ESPECIAL' => 'fas fa-star'
        ];
    }
}