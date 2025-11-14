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
     * Obtener lista de días laborables entre dos fechas
     * Retorna array de fechas en formato Y-m-d
     *
     * @param string $startDate Fecha inicial (Y-m-d)
     * @param string $endDate Fecha final (Y-m-d)
     * @return array Array de fechas laborables ['2025-11-01', '2025-11-04', ...]
     */
    public function getWorkingDaysList($startDate, $endDate)
    {
        try {
            $sql = "SELECT date_value
                    FROM business_calendar
                    WHERE date_value BETWEEN ? AND ?
                    AND day_type = 'LABORAL'
                    ORDER BY date_value ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$startDate, $endDate]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_column($results, 'date_value');
        } catch (PDOException $e) {
            error_log("Error getting working days list: " . $e->getMessage());
            return $this->getWorkingDaysListFallback($startDate, $endDate);
        }
    }

    /**
     * Obtener lista de días laborables (fallback sin BD)
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function getWorkingDaysListFallback($startDate, $endDate)
    {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $workingDays = [];

        while ($start <= $end) {
            $dateStr = $start->format('Y-m-d');
            if ($this->isWeekday($dateStr)) {
                $workingDays[] = $dateStr;
            }
            $start->modify('+1 day');
        }

        return $workingDays;
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
     * ✅ ACTUALIZADO: Incluye campo is_paid_holiday
     */
    public function addSpecialDay($date, $dayType, $status, $description, $isPaidHoliday = 0)
    {
        try {
            $dateObj = new \DateTime($date);

            $sql = "INSERT INTO business_calendar
                    (date_value, day_type, status, description, is_paid_holiday, is_weekend, year_value, month_value, day_of_week)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    day_type = VALUES(day_type),
                    status = VALUES(status),
                    description = VALUES(description),
                    is_paid_holiday = VALUES(is_paid_holiday)";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $date,
                $dayType,
                $status,
                $description,
                $isPaidHoliday,
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
     * Actualizar un día del calendario
     * ✅ NUEVO: Permite actualizar campos de días existentes
     */
    public function updateDay($id, $data)
    {
        try {
            $allowedFields = ['day_type', 'status', 'description', 'is_paid_holiday', 'notes', 'is_weekend'];
            $updates = [];
            $params = [];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }

            if (empty($updates)) {
                return false;
            }

            $params[] = $id;
            $sql = "UPDATE business_calendar SET " . implode(', ', $updates) . " WHERE id = ?";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);

        } catch (PDOException $e) {
            error_log("Error updating day: " . $e->getMessage());
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
     * Inicializar calendario completo para un año
     * Genera todos los días laborables y fines de semana, manteniendo feriados existentes
     *
     * @param int $year Año a inicializar
     * @param bool $saturdayHalfDay Si true, los sábados se marcan como LABORAL con status MEDIO_DIA
     * @return array Resultado de la operación
     */
    public function initializeYear($year, $saturdayHalfDay = false)
    {
        try {
            $startDate = new \DateTime("$year-01-01");
            $endDate = new \DateTime("$year-12-31");

            // Obtener días ya existentes para este año
            $stmt = $this->db->prepare("SELECT date_value FROM business_calendar WHERE year_value = ?");
            $stmt->execute([$year]);
            $existingDates = array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'date_value');
            $existingDatesSet = array_flip($existingDates);

            $diasSemana = [
                1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
                4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'
            ];

            $diasSemanaEs = [
                1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
                4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'
            ];

            $updated = 0;

            // Si el checkbox está marcado, actualizar sábados existentes
            if ($saturdayHalfDay) {
                error_log("BusinessCalendar::initializeYear - UPDATING Saturdays for year {$year}");

                // Verificar cuántos sábados hay antes del update
                $checkStmt = $this->db->prepare("
                    SELECT COUNT(*) as total
                    FROM business_calendar
                    WHERE year_value = ?
                    AND day_of_week = 6
                    AND day_type = 'NO_LABORAL'
                ");
                $checkStmt->execute([$year]);
                $beforeCount = $checkStmt->fetch(\PDO::FETCH_ASSOC);
                error_log("BusinessCalendar: Sábados NO_LABORAL antes del update: {$beforeCount['total']}");

                $updateStmt = $this->db->prepare("
                    UPDATE business_calendar
                    SET day_type = 'LABORAL',
                        status = 'MEDIO_DIA',
                        description = 'Medio día laborable - Sábado',
                        is_weekend = 0
                    WHERE year_value = ?
                    AND day_of_week = 6
                    AND day_type = 'NO_LABORAL'
                ");
                $updateStmt->execute([$year]);
                $updated = $updateStmt->rowCount();

                error_log("BusinessCalendar: Actualizados {$updated} sábados a medio día laborable en año {$year}");

                // Verificar después del update
                $afterStmt = $this->db->prepare("
                    SELECT COUNT(*) as total
                    FROM business_calendar
                    WHERE year_value = ?
                    AND day_of_week = 6
                    AND day_type = 'LABORAL'
                    AND status = 'MEDIO_DIA'
                ");
                $afterStmt->execute([$year]);
                $afterCount = $afterStmt->fetch(\PDO::FETCH_ASSOC);
                error_log("BusinessCalendar: Sábados LABORAL/MEDIO_DIA después del update: {$afterCount['total']}");
            } else {
                error_log("BusinessCalendar::initializeYear - Saturday half-day NOT requested");
            }

            $inserted = 0;
            $currentDate = clone $startDate;

            // Preparar statement de inserción
            $insertStmt = $this->db->prepare("
                INSERT INTO business_calendar
                (date_value, year_value, month_value, day_of_week, day_type, status, description, is_weekend)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            while ($currentDate <= $endDate) {
                $dateStr = $currentDate->format('Y-m-d');

                // Si ya existe, saltarlo
                if (isset($existingDatesSet[$dateStr])) {
                    $currentDate->modify('+1 day');
                    continue;
                }

                $dayOfWeek = (int)$currentDate->format('N'); // 1=Lunes, 7=Domingo

                // Lógica especial para sábados si saturday_half_day está activado
                if ($dayOfWeek == 6 && $saturdayHalfDay) {
                    // Sábado como medio día laborable
                    $dayType = 'LABORAL';
                    $status = 'MEDIO_DIA';
                    $description = "Medio día laborable - Sábado";
                    $isWeekend = 0; // No se marca como fin de semana si es laborable
                } elseif ($dayOfWeek >= 6) {
                    // Fin de semana normal (sábados sin opción activa o domingos)
                    $dayType = 'NO_LABORAL';
                    $status = 'NORMAL';
                    $description = $diasSemanaEs[$dayOfWeek];
                    $isWeekend = 1;
                } else {
                    // Días laborables normales (Lunes-Viernes)
                    $dayType = 'LABORAL';
                    $status = 'NORMAL';
                    $description = "Día laboral - {$diasSemana[$dayOfWeek]}";
                    $isWeekend = 0;
                }

                $insertStmt->execute([
                    $dateStr,
                    (int)$currentDate->format('Y'),
                    (int)$currentDate->format('m'),
                    $dayOfWeek,
                    $dayType,
                    $status,
                    $description,
                    $isWeekend
                ]);

                $inserted++;
                $currentDate->modify('+1 day');
            }

            return [
                'success' => true,
                'inserted' => $inserted,
                'updated' => $updated,
                'skipped' => count($existingDates),
                'total' => $inserted + count($existingDates),
                'saturday_half_day' => $saturdayHalfDay
            ];

        } catch (\PDOException $e) {
            error_log("Error initializing year calendar: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener día por fecha (alias de getDayInfo para compatibilidad con CalendarSyncService)
     */
    public function getByDate($date)
    {
        return $this->getDayInfo($date);
    }

    /**
     * Crear un nuevo día en el calendario
     * Método genérico para compatibilidad con CalendarSyncService
     */
    public function create($data)
    {
        try {
            $dateObj = new \DateTime($data['date_value']);

            $sql = "INSERT INTO business_calendar
                    (date_value, year_value, month_value, day_of_week, day_type, status,
                     description, is_weekend, is_paid_holiday, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['date_value'],
                $data['year'] ?? (int)$dateObj->format('Y'),
                $data['month'] ?? (int)$dateObj->format('n'),
                $this->getDayOfWeek($data['date_value']),
                $data['day_type'] ?? 'LABORAL',
                $data['status'] ?? 'NORMAL',
                $data['description'] ?? null,
                $data['is_weekend'] ?? $this->isWeekend($data['date_value']),
                $data['is_paid_holiday'] ?? 0,
                $data['notes'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Error creating calendar day: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar día (alias de updateDay para compatibilidad con CalendarSyncService)
     */
    public function update($id, $data)
    {
        return $this->updateDay($id, $data);
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