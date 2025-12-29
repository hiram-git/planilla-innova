<?php

namespace App\Services\Attendance;

use App\Core\Database;
use PDO;
use PDOException;

/**
 * AttendanceConceptMapper
 *
 * Mapea cálculos de asistencia a conceptos de planilla
 * Aplica reglas de negocio según configuración en attendance_concepts_mapping
 *
 * @version 1.0.0
 * @date 2025-10-20
 */
class AttendanceConceptMapper
{
    private $db;
    private array $mappingsCache = [];
    private bool $cacheLoaded = false;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtener todos los mapeos activos
     *
     * @param int|null $tipo_planilla_id Filtrar por tipo de planilla específico
     * @return array Array de mapeos activos
     */
    public function getActiveMappings(?int $tipo_planilla_id = null): array
    {
        try {
            $sql = "SELECT
                        acm.*,
                        c.concepto as concepto_codigo,
                        c.descripcion as concepto_descripcion,
                        c.tipo_concepto,
                        c.formula as concepto_formula
                    FROM attendance_concepts_mapping acm
                    INNER JOIN concepto c ON acm.concepto_id = c.id
                    WHERE acm.is_active = 1";

            $params = [];

            // Filtrar por tipo de planilla si se especifica
            if ($tipo_planilla_id !== null) {
                $sql .= " AND (acm.tipo_planilla_id IS NULL OR acm.tipo_planilla_id = ?)";
                $params[] = $tipo_planilla_id;
            }

            $sql .= " ORDER BY acm.priority ASC, acm.mapping_type";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error obteniendo mapeos activos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cargar mapeos en caché para procesamiento rápido
     *
     * @param int|null $tipo_planilla_id Tipo de planilla
     */
    private function loadMappingsCache(?int $tipo_planilla_id = null): void
    {
        if ($this->cacheLoaded) {
            return;
        }

        $mappings = $this->getActiveMappings($tipo_planilla_id);

        // Organizar por tipo de mapeo para acceso rápido
        foreach ($mappings as $mapping) {
            $this->mappingsCache[$mapping['mapping_type']][] = $mapping;
        }

        $this->cacheLoaded = true;
    }

    /**
     * Mapear resumen de asistencias a conceptos de planilla
     *
     * @param array $attendanceSummary Resumen de asistencias del empleado
     * @param int $employee_id ID del empleado
     * @param int $tipo_planilla_id ID del tipo de planilla
     * @param int|null $situacion_id ID de situación del empleado
     * @return array Array de conceptos mapeados listos para insertar en planilla_detalle
     */
    public function mapAttendanceToConcepts(
        array $attendanceSummary,
        int $employee_id,
        int $tipo_planilla_id,
        ?int $situacion_id = null
    ): array {
        // Cargar mapeos en caché
        $this->loadMappingsCache($tipo_planilla_id);

        $concepts = [];

        // Obtener salario del empleado para cálculos
        $empleadoData = $this->getEmpleadoData($employee_id, $tipo_planilla_id);
        $salarioMensual = $empleadoData['salario'] ?? 0;
        $tarifaHora = $salarioMensual > 0 ? $salarioMensual / 220 : 0; // 220 horas/mes

        // Mapear cada tipo de métrica
        $concepts = array_merge($concepts, $this->mapRegularHours($attendanceSummary, $salarioMensual, $tarifaHora));
        $concepts = array_merge($concepts, $this->mapOvertime25($attendanceSummary, $tarifaHora));
        $concepts = array_merge($concepts, $this->mapOvertime50($attendanceSummary, $tarifaHora));
        $concepts = array_merge($concepts, $this->mapNightHours($attendanceSummary, $tarifaHora));
        $concepts = array_merge($concepts, $this->mapHolidayHours($attendanceSummary, $tarifaHora));
        $concepts = array_merge($concepts, $this->mapSundayHours($attendanceSummary, $tarifaHora));
        $concepts = array_merge($concepts, $this->mapTardinessDeductions($attendanceSummary, $tarifaHora));
        $concepts = array_merge($concepts, $this->mapAbsenceDeductions($attendanceSummary, $salarioMensual));
        $concepts = array_merge($concepts, $this->mapPunctualityBonuses($attendanceSummary));
        $concepts = array_merge($concepts, $this->mapPerfectAttendanceBonuses($attendanceSummary));

        return $concepts;
    }

    /**
     * Mapear horas regulares/trabajadas
     */
    private function mapRegularHours(array $summary, float $salarioMensual, float $tarifaHora): array
    {
        $concepts = [];

        if (!isset($this->mappingsCache['HORAS_TRABAJADAS'])) {
            return $concepts;
        }

        $horasTrabajadas = $summary['total_hours_worked'] ?? 0;

        if ($horasTrabajadas <= 0) {
            return $concepts;
        }

        foreach ($this->mappingsCache['HORAS_TRABAJADAS'] as $mapping) {
            $monto = $this->calculateAmount($horasTrabajadas, $tarifaHora, $mapping, $salarioMensual);

            if ($monto > 0 || $mapping['usar_unidad']) {
                $concepts[] = [
                    'concepto_id' => $mapping['concepto_id'],
                    'monto' => $monto,
                    'unidad' => $mapping['usar_unidad'] ? $horasTrabajadas : null,
                    'tipo' => $mapping['tipo_concepto'],
                    'notas' => "Horas trabajadas: $horasTrabajadas"
                ];
            }
        }

        return $concepts;
    }

    /**
     * Mapear horas extras 25%
     */
    private function mapOvertime25(array $summary, float $tarifaHora): array
    {
        $concepts = [];

        if (!isset($this->mappingsCache['HORAS_EXTRAS_25'])) {
            return $concepts;
        }

        $horasExtras25 = $summary['overtime_hours_25'] ?? 0;

        if ($horasExtras25 <= 0) {
            return $concepts;
        }

        foreach ($this->mappingsCache['HORAS_EXTRAS_25'] as $mapping) {
            $monto = $this->calculateAmount($horasExtras25, $tarifaHora, $mapping);

            if ($monto > 0 || $mapping['usar_unidad']) {
                $concepts[] = [
                    'concepto_id' => $mapping['concepto_id'],
                    'monto' => $monto,
                    'unidad' => $mapping['usar_unidad'] ? $horasExtras25 : null,
                    'tipo' => $mapping['tipo_concepto'],
                    'notas' => "Horas extras 25%: $horasExtras25"
                ];
            }
        }

        return $concepts;
    }

    /**
     * Mapear horas extras 50%
     */
    private function mapOvertime50(array $summary, float $tarifaHora): array
    {
        $concepts = [];

        if (!isset($this->mappingsCache['HORAS_EXTRAS_50'])) {
            return $concepts;
        }

        $horasExtras50 = $summary['overtime_hours_50'] ?? 0;

        if ($horasExtras50 <= 0) {
            return $concepts;
        }

        foreach ($this->mappingsCache['HORAS_EXTRAS_50'] as $mapping) {
            $monto = $this->calculateAmount($horasExtras50, $tarifaHora, $mapping);

            if ($monto > 0 || $mapping['usar_unidad']) {
                $concepts[] = [
                    'concepto_id' => $mapping['concepto_id'],
                    'monto' => $monto,
                    'unidad' => $mapping['usar_unidad'] ? $horasExtras50 : null,
                    'tipo' => $mapping['tipo_concepto'],
                    'notas' => "Horas extras 50%: $horasExtras50"
                ];
            }
        }

        return $concepts;
    }

    /**
     * Mapear horas nocturnas
     */
    private function mapNightHours(array $summary, float $tarifaHora): array
    {
        $concepts = [];

        if (!isset($this->mappingsCache['HORAS_NOCTURNAS'])) {
            return $concepts;
        }

        $horasNocturnas = $summary['night_hours'] ?? 0;

        if ($horasNocturnas <= 0) {
            return $concepts;
        }

        foreach ($this->mappingsCache['HORAS_NOCTURNAS'] as $mapping) {
            $monto = $this->calculateAmount($horasNocturnas, $tarifaHora, $mapping);

            if ($monto > 0 || $mapping['usar_unidad']) {
                $concepts[] = [
                    'concepto_id' => $mapping['concepto_id'],
                    'monto' => $monto,
                    'unidad' => $mapping['usar_unidad'] ? $horasNocturnas : null,
                    'tipo' => $mapping['tipo_concepto'],
                    'notas' => "Horas nocturnas: $horasNocturnas"
                ];
            }
        }

        return $concepts;
    }

    /**
     * Mapear horas en feriados
     */
    private function mapHolidayHours(array $summary, float $tarifaHora): array
    {
        $concepts = [];

        if (!isset($this->mappingsCache['HORAS_FERIADOS'])) {
            return $concepts;
        }

        $horasFeriados = $summary['holiday_hours'] ?? 0;

        if ($horasFeriados <= 0) {
            return $concepts;
        }

        foreach ($this->mappingsCache['HORAS_FERIADOS'] as $mapping) {
            $monto = $this->calculateAmount($horasFeriados, $tarifaHora, $mapping);

            if ($monto > 0 || $mapping['usar_unidad']) {
                $concepts[] = [
                    'concepto_id' => $mapping['concepto_id'],
                    'monto' => $monto,
                    'unidad' => $mapping['usar_unidad'] ? $horasFeriados : null,
                    'tipo' => $mapping['tipo_concepto'],
                    'notas' => "Horas feriados: $horasFeriados"
                ];
            }
        }

        return $concepts;
    }

    /**
     * Mapear horas dominicales
     */
    private function mapSundayHours(array $summary, float $tarifaHora): array
    {
        $concepts = [];

        if (!isset($this->mappingsCache['HORAS_DOMINICALES'])) {
            return $concepts;
        }

        $horasDominicales = $summary['sunday_hours'] ?? 0;

        if ($horasDominicales <= 0) {
            return $concepts;
        }

        foreach ($this->mappingsCache['HORAS_DOMINICALES'] as $mapping) {
            $monto = $this->calculateAmount($horasDominicales, $tarifaHora, $mapping);

            if ($monto > 0 || $mapping['usar_unidad']) {
                $concepts[] = [
                    'concepto_id' => $mapping['concepto_id'],
                    'monto' => $monto,
                    'unidad' => $mapping['usar_unidad'] ? $horasDominicales : null,
                    'tipo' => $mapping['tipo_concepto'],
                    'notas' => "Horas dominicales: $horasDominicales"
                ];
            }
        }

        return $concepts;
    }

    /**
     * Mapear descuentos por tardanzas
     */
    private function mapTardinessDeductions(array $summary, float $tarifaHora): array
    {
        $concepts = [];

        if (!isset($this->mappingsCache['DESCUENTO_TARDANZAS'])) {
            return $concepts;
        }

        $minutosTardanza = $summary['total_tardiness_minutes'] ?? 0;

        if ($minutosTardanza <= 0) {
            return $concepts;
        }

        foreach ($this->mappingsCache['DESCUENTO_TARDANZAS'] as $mapping) {
            // Convertir minutos a horas fraccionadas para el cálculo
            $horasTardanza = $minutosTardanza / 60;
            $monto = $this->calculateAmount($horasTardanza, $tarifaHora, $mapping);

            if ($monto > 0 || $mapping['usar_unidad']) {
                $concepts[] = [
                    'concepto_id' => $mapping['concepto_id'],
                    'monto' => $monto,
                    'unidad' => $mapping['usar_unidad'] ? $minutosTardanza : null,
                    'tipo' => $mapping['tipo_concepto'],
                    'notas' => "Minutos tardanza: $minutosTardanza"
                ];
            }
        }

        return $concepts;
    }

    /**
     * Mapear descuentos por ausencias
     */
    private function mapAbsenceDeductions(array $summary, float $salarioMensual): array
    {
        $concepts = [];

        if (!isset($this->mappingsCache['DESCUENTO_AUSENCIAS'])) {
            return $concepts;
        }

        $ausenciasInjustificadas = $summary['unjustified_absences'] ?? 0;

        if ($ausenciasInjustificadas <= 0) {
            return $concepts;
        }

        foreach ($this->mappingsCache['DESCUENTO_AUSENCIAS'] as $mapping) {
            // Salario diario = salario mensual / 30
            $salarioDiario = $salarioMensual / 30;
            $monto = $ausenciasInjustificadas * $salarioDiario;

            if ($monto > 0 || $mapping['usar_unidad']) {
                $concepts[] = [
                    'concepto_id' => $mapping['concepto_id'],
                    'monto' => $monto,
                    'unidad' => $mapping['usar_unidad'] ? $ausenciasInjustificadas : null,
                    'tipo' => $mapping['tipo_concepto'],
                    'notas' => "Ausencias injustificadas: $ausenciasInjustificadas días"
                ];
            }
        }

        return $concepts;
    }

    /**
     * Mapear bonificaciones por puntualidad
     */
    private function mapPunctualityBonuses(array $summary): array
    {
        $concepts = [];

        if (!isset($this->mappingsCache['BONO_PUNTUALIDAD'])) {
            return $concepts;
        }

        $punctualityScore = $summary['punctuality_score'] ?? 0;

        // Solo dar bono si el score es mayor a cierto umbral (ej: 90%)
        if ($punctualityScore < 90) {
            return $concepts;
        }

        foreach ($this->mappingsCache['BONO_PUNTUALIDAD'] as $mapping) {
            // Si tiene valor fijo, usarlo; sino calcular según fórmula
            $monto = $mapping['valor_fijo'] ?? 0;

            if ($monto > 0) {
                $concepts[] = [
                    'concepto_id' => $mapping['concepto_id'],
                    'monto' => $monto,
                    'unidad' => null,
                    'tipo' => $mapping['tipo_concepto'],
                    'notas' => "Bono puntualidad - Score: $punctualityScore%"
                ];
            }
        }

        return $concepts;
    }

    /**
     * Mapear bonificaciones por asistencia perfecta
     */
    private function mapPerfectAttendanceBonuses(array $summary): array
    {
        $concepts = [];

        if (!isset($this->mappingsCache['BONO_ASISTENCIA_PERFECTA'])) {
            return $concepts;
        }

        $diasPerfectos = $summary['perfect_attendance_days'] ?? 0;
        $ausencias = $summary['total_absences'] ?? 0;
        $tardanzas = $summary['tardiness_count'] ?? 0;

        // Solo dar bono si no tiene ausencias ni tardanzas
        if ($ausencias > 0 || $tardanzas > 0) {
            return $concepts;
        }

        foreach ($this->mappingsCache['BONO_ASISTENCIA_PERFECTA'] as $mapping) {
            // Si tiene valor fijo, usarlo
            $monto = $mapping['valor_fijo'] ?? 0;

            if ($monto > 0) {
                $concepts[] = [
                    'concepto_id' => $mapping['concepto_id'],
                    'monto' => $monto,
                    'unidad' => null,
                    'tipo' => $mapping['tipo_concepto'],
                    'notas' => "Asistencia perfecta - Días: $diasPerfectos"
                ];
            }
        }

        return $concepts;
    }

    /**
     * Calcular monto según fórmula o valor fijo del mapeo
     *
     * @param float $cantidad Cantidad de unidades (horas, días, etc.)
     * @param float $tarifaBase Tarifa base (por hora, día, etc.)
     * @param array $mapping Configuración del mapeo
     * @param float $salarioMensual Salario mensual del empleado (opcional)
     * @return float Monto calculado
     */
    private function calculateAmount(
        float $cantidad,
        float $tarifaBase,
        array $mapping,
        float $salarioMensual = 0
    ): float {
        // Si tiene valor fijo, usarlo
        if (!empty($mapping['valor_fijo']) && $mapping['valor_fijo'] > 0) {
            return $cantidad * (float)$mapping['valor_fijo'];
        }

        // Si tiene fórmula de multiplicador, evaluarla
        if (!empty($mapping['formula_multiplicador'])) {
            $formula = $mapping['formula_multiplicador'];

            // Reemplazar variables en la fórmula
            $formula = str_replace('SUELDO', (string)$salarioMensual, $formula);
            $formula = str_replace('TARIFA_HORA', (string)$tarifaBase, $formula);

            try {
                // Evaluar la fórmula de forma segura
                $tarifaCalculada = eval("return $formula;");
                return $cantidad * (float)$tarifaCalculada;
            } catch (\Throwable $e) {
                error_log("Error evaluando fórmula multiplicador: {$mapping['formula_multiplicador']} - " . $e->getMessage());
                return $cantidad * $tarifaBase;
            }
        }

        // Por defecto, usar tarifa base simple
        return $cantidad * $tarifaBase;
    }

    /**
     * Obtener datos del empleado necesarios para los cálculos
     *
     * @param int $employee_id ID del empleado
     * @param int $tipo_planilla_id ID del tipo de planilla
     * @return array Datos del empleado
     */
    private function getEmpleadoData(int $employee_id, int $tipo_planilla_id): array
    {
        try {
            // Primero intentar obtener salario de employee_payroll_salaries
            $sql = "SELECT sueldo_base as salario, gastos_representacion
                    FROM employee_payroll_salaries
                    WHERE employee_id = ? AND tipo_planilla_id = ?
                    AND is_active = 1
                    AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())
                    ORDER BY fecha_inicio DESC
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id, $tipo_planilla_id]);
            $salary = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($salary) {
                return [
                    'salario' => (float)$salary['salario'] + (float)($salary['gastos_representacion'] ?? 0)
                ];
            }

            // Fallback: obtener salario de employees
            $sql = "SELECT
                        COALESCE(e.sueldo_individual, p.sueldo, 0) as salario,
                        COALESCE(e.gastos_representacion, 0) as gastos_representacion
                    FROM employees e
                    LEFT JOIN posiciones p ON e.position_id = p.id
                    WHERE e.id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($employee) {
                return [
                    'salario' => (float)$employee['salario'] + (float)$employee['gastos_representacion']
                ];
            }

            return ['salario' => 0];

        } catch (PDOException $e) {
            error_log("Error obteniendo datos empleado: " . $e->getMessage());
            return ['salario' => 0];
        }
    }

    /**
     * Limpiar caché de mapeos
     */
    public function clearCache(): void
    {
        $this->mappingsCache = [];
        $this->cacheLoaded = false;
    }
}
