<?php
/**
 * Alias para compatibilidad con código existente
 * La clase real está en Calculators/AbsenceDetector.php
 */

namespace App\Services\Attendance;

// Re-exportar la clase desde su ubicación real
class AbsenceDetector extends \App\Services\Attendance\Calculators\AbsenceDetector
{
    // Esta clase es solo un alias, toda la funcionalidad está en la clase padre
}
