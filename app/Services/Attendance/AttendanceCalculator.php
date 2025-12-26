<?php
/**
 * Alias para compatibilidad con código existente
 * La clase real está en Calculators/AttendanceCalculator.php
 */

namespace App\Services\Attendance;

// Re-exportar la clase desde su ubicación real
class AttendanceCalculator extends \App\Services\Attendance\Calculators\AttendanceCalculator
{
    // Esta clase es solo un alias, toda la funcionalidad está en la clase padre
}
