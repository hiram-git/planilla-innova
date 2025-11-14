<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use PDOException;

class VacationBalanceService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtener saldo de vacaciones por año específico para un empleado
     */
    public function getAnnualBalance($employee_id, $year)
    {
        try {
            $sql = "SELECT * FROM vacation_annual_balances
                    WHERE employee_id = ? AND year = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id, $year]);
            $balance = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$balance) {
                // Crear balance anual inicial si no existe
                $balance = $this->createAnnualBalance($employee_id, $year);
            }

            return $balance;
        } catch (PDOException $e) {
            error_log("Error getting annual balance: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear balance anual inicial para un empleado y año
     */
    private function createAnnualBalance($employee_id, $year)
    {
        try {
            $sql = "INSERT INTO vacation_annual_balances
                    (employee_id, year, dias_vacaciones_anuales, saldo_disponible_year)
                    VALUES (?, ?, 30, 30)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id, $year]);

            return [
                'id' => $this->db->lastInsertId(),
                'employee_id' => $employee_id,
                'year' => $year,
                'dias_vacaciones_anuales' => 30,
                'dias_pagados_year' => 0.00,
                'dias_disfrutados_year' => 0.00,
                'saldo_disponible_year' => 30.00
            ];
        } catch (PDOException $e) {
            error_log("Error creating annual balance: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar saldo anual después de una solicitud de vacaciones
     * NUEVO: Soporta distribución multi-año usando FIFO (años más antiguos primero)
     * IMPORTANTE: Solo días PAGADOS afectan el saldo. Días DISFRUTADOS solo se registran.
     */
    public function updateAnnualBalance($employee_id, $year, $dias_pagados, $dias_disfrutados)
    {
        try {
            // Obtener años disponibles ordenados del más antiguo al más reciente
            $available_years = $this->getAvailableYears($employee_id);

            if (empty($available_years)) {
                error_log("No available years found for employee $employee_id");
                return false;
            }

            $remaining_dias_pagados = $dias_pagados;
            $remaining_dias_disfrutados = $dias_disfrutados;

            // Distribuir días usando FIFO (años más antiguos primero)
            foreach ($available_years as $year_data) {
                if ($remaining_dias_pagados <= 0 && $remaining_dias_disfrutados <= 0) {
                    break;
                }

                $year_balance = $this->getAnnualBalance($employee_id, $year_data['year']);
                if (!$year_balance || $year_balance['saldo_disponible_year'] <= 0) {
                    continue;
                }

                // CORRECCIÓN: Solo días PAGADOS afectan el saldo
                $dias_to_take_pagar = min($remaining_dias_pagados, $year_balance['saldo_disponible_year']);

                // Días DISFRUTADOS se distribuyen proporcionalmente a los días pagados tomados de este año
                // Si tomo X días pagados de este año, asigno proporcionalmente los días disfrutados
                $proportion = $dias_pagados > 0 ? ($dias_to_take_pagar / $dias_pagados) : 0;
                $dias_to_take_disfrute = round($dias_disfrutados * $proportion);

                // Ajustar para no exceder lo que queda por distribuir
                $dias_to_take_disfrute = min($dias_to_take_disfrute, $remaining_dias_disfrutados);

                if ($dias_to_take_pagar > 0 || $dias_to_take_disfrute > 0) {
                    // Actualizar este año
                    $new_dias_pagados = $year_balance['dias_pagados_year'] + $dias_to_take_pagar;
                    $new_dias_disfrutados = $year_balance['dias_disfrutados_year'] + $dias_to_take_disfrute;

                    // FÓRMULA CORRECTA: saldo = dias_vacaciones - dias_pagados (sin restar disfrutados)
                    $new_saldo = $year_balance['dias_vacaciones_anuales'] - $new_dias_pagados;

                    $sql = "UPDATE vacation_annual_balances
                            SET dias_pagados_year = ?,
                                dias_disfrutados_year = ?,
                                saldo_disponible_year = ?,
                                updated_at = NOW()
                            WHERE employee_id = ? AND year = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([
                        $new_dias_pagados,
                        $new_dias_disfrutados,
                        $new_saldo,
                        $employee_id,
                        $year_data['year']
                    ]);

                    $remaining_dias_pagados -= $dias_to_take_pagar;
                    $remaining_dias_disfrutados -= $dias_to_take_disfrute;
                }
            }

            return true;
        } catch (PDOException $e) {
            error_log("Error updating annual balance: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Revertir saldo anual cuando se rechaza una solicitud de vacaciones
     * NUEVO: Soporta reversión multi-año usando LIFO (últimos años usados primero)
     * IMPORTANTE: Solo días PAGADOS afectan el saldo. Días DISFRUTADOS solo se revierten del registro.
     */
    public function revertAnnualBalance($employee_id, $year, $dias_pagados, $dias_disfrutados)
    {
        try {
            // Obtener todos los años del empleado ordenados del más reciente al más antiguo (LIFO)
            $sql = "SELECT year, dias_pagados_year, dias_disfrutados_year, saldo_disponible_year, dias_vacaciones_anuales
                    FROM vacation_annual_balances
                    WHERE employee_id = ?
                    ORDER BY year DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id]);
            $all_years = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($all_years)) {
                error_log("No years found for employee $employee_id to revert");
                return false;
            }

            $remaining_dias_pagados = $dias_pagados;
            $remaining_dias_disfrutados = $dias_disfrutados;

            // Revertir usando LIFO (años más recientes primero)
            foreach ($all_years as $year_data) {
                if ($remaining_dias_pagados <= 0 && $remaining_dias_disfrutados <= 0) {
                    break;
                }

                // Solo revertir de años que tienen días pagados/disfrutados
                if ($year_data['dias_pagados_year'] <= 0 && $year_data['dias_disfrutados_year'] <= 0) {
                    continue;
                }

                // Calcular cuántos días devolver a este año
                $dias_to_return_pagar = min($remaining_dias_pagados, $year_data['dias_pagados_year']);
                $dias_to_return_disfrute = min($remaining_dias_disfrutados, $year_data['dias_disfrutados_year']);

                if ($dias_to_return_pagar > 0 || $dias_to_return_disfrute > 0) {
                    // Actualizar este año
                    $new_dias_pagados = $year_data['dias_pagados_year'] - $dias_to_return_pagar;
                    $new_dias_disfrutados = $year_data['dias_disfrutados_year'] - $dias_to_return_disfrute;

                    // FÓRMULA CORRECTA: saldo = dias_vacaciones - dias_pagados (sin considerar disfrutados)
                    $new_saldo = $year_data['dias_vacaciones_anuales'] - $new_dias_pagados;

                    // Evitar valores negativos
                    $new_dias_pagados = max(0, $new_dias_pagados);
                    $new_dias_disfrutados = max(0, $new_dias_disfrutados);

                    $sql = "UPDATE vacation_annual_balances
                            SET dias_pagados_year = ?,
                                dias_disfrutados_year = ?,
                                saldo_disponible_year = ?,
                                updated_at = NOW()
                            WHERE employee_id = ? AND year = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([
                        $new_dias_pagados,
                        $new_dias_disfrutados,
                        $new_saldo,
                        $employee_id,
                        $year_data['year']
                    ]);

                    $remaining_dias_pagados -= $dias_to_return_pagar;
                    $remaining_dias_disfrutados -= $dias_to_return_disfrute;
                }
            }

            return true;
        } catch (PDOException $e) {
            error_log("Error reverting annual balance: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Obtener historial completo de vacaciones por empleado
     */
    public function getVacationHistory($employee_id)
    {
        try {
            $sql = "SELECT
                        vab.year,
                        vab.dias_vacaciones_anuales,
                        vab.dias_pagados_year,
                        vab.dias_disfrutados_year,
                        vab.saldo_disponible_year,
                        COUNT(vr.id) as solicitudes_count
                    FROM vacation_annual_balances vab
                    LEFT JOIN vacation_requests vr ON vab.employee_id = vr.employee_id
                                                   AND vab.year = vr.ano_vacaciones
                    WHERE vab.employee_id = ?
                    GROUP BY vab.year, vab.dias_vacaciones_anuales, vab.dias_pagados_year,
                             vab.dias_disfrutados_year, vab.saldo_disponible_year
                    ORDER BY vab.year DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting vacation history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validar si se puede procesar una solicitud de vacaciones
     * NUEVO: Valida contra el saldo total acumulado de todos los años
     */
    public function canProcessVacationRequest($employee_id, $year, $dias_pagados, $dias_disfrutados)
    {
        try {
            // Obtener saldo total acumulado de todos los años
            $total_saldo_disponible = $this->getTotalAccumulatedBalance($employee_id);

            if ($total_saldo_disponible <= 0) {
                return [
                    'valid' => false,
                    'message' => "No hay días de vacaciones disponibles. Saldo total acumulado: 0 días"
                ];
            }

            // CORRECCIÓN: Los días disfrutados NO afectan el saldo, solo validar días pagados
            $total_solicitado = $dias_pagados; // Solo validar días pagados

            if ($total_solicitado > $total_saldo_disponible) {
                return [
                    'valid' => false,
                    'message' => "Días por pagar ({$total_solicitado}) no pueden exceder el saldo disponible total ({$total_saldo_disponible})"
                ];
            }

            return ['valid' => true, 'message' => 'Solicitud válida'];
        } catch (\Exception $e) {
            return ['valid' => false, 'message' => 'Error validando solicitud: ' . $e->getMessage()];
        }
    }

    /**
     * Obtener años disponibles con saldo positivo (ordenados del más antiguo al más reciente)
     * Usado para distribución FIFO
     */
    private function getAvailableYears($employee_id)
    {
        try {
            $sql = "SELECT year, saldo_disponible_year
                    FROM vacation_annual_balances
                    WHERE employee_id = ? AND saldo_disponible_year > 0
                    ORDER BY year ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting available years: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener saldo total acumulado de todos los años
     */
    public function getTotalAccumulatedBalance($employee_id)
    {
        try {
            $sql = "SELECT COALESCE(SUM(saldo_disponible_year), 0) as total_saldo
                    FROM vacation_annual_balances
                    WHERE employee_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['total_saldo'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error getting total accumulated balance: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Generar años faltantes de vacaciones para un empleado
     * ✅ NUEVO: Detecta años faltantes desde fecha_ingreso hasta año actual y los crea
     *
     * @param int $employee_id ID del empleado
     * @return array Resultado con años creados y estadísticas
     */
    public function generateMissingYears($employee_id)
    {
        try {
            // Obtener fecha de ingreso del empleado
            $sql = "SELECT fecha_ingreso FROM employees WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee || !$employee['fecha_ingreso']) {
                return [
                    'success' => false,
                    'message' => 'Empleado no encontrado o sin fecha de ingreso',
                    'years_created' => [],
                    'count' => 0
                ];
            }

            $fecha_ingreso = new \DateTime($employee['fecha_ingreso']);
            $current_year = (int)date('Y');

            // Calcular primer año completo (11 meses después del ingreso)
            $fecha_primer_derecho = clone $fecha_ingreso;
            $fecha_primer_derecho->modify('+11 months');
            $first_year = (int)$fecha_primer_derecho->format('Y');

            // Si aún no ha cumplido 11 meses, no tiene años disponibles
            if ($first_year > $current_year) {
                return [
                    'success' => true,
                    'message' => 'El empleado aún no cumple 11 meses para generar años de vacaciones',
                    'years_created' => [],
                    'count' => 0
                ];
            }

            // Obtener años que ya existen
            $sql = "SELECT year FROM vacation_annual_balances WHERE employee_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id]);
            $existing_years = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Detectar años faltantes con validación de 24 meses cumplidos
            $missing_years = [];
            $today = new \DateTime();

            for ($year = $first_year; $year <= $current_year; $year++) {
                // Saltar si el año ya existe
                if (in_array($year, $existing_years)) {
                    continue;
                }

                // Calcular meses requeridos para generar este año
                // Año 1 (first_year): 12 meses desde ingreso
                // Año 2: 24 meses desde ingreso
                // Año 3: 36 meses desde ingreso, etc.
                $year_offset = $year - $first_year;
                $meses_requeridos = ($year_offset + 1) * 12;
                $fecha_requerida = clone $fecha_ingreso;
                $fecha_requerida->modify("+{$meses_requeridos} months");

                // Solo agregar el año si ya cumplió los meses requeridos
                if ($today >= $fecha_requerida) {
                    $missing_years[] = $year;
                }
            }

            // Si no hay años faltantes
            if (empty($missing_years)) {
                return [
                    'success' => true,
                    'message' => 'No hay años faltantes. Todos los años disponibles están generados.',
                    'years_created' => [],
                    'count' => 0
                ];
            }

            // Crear años faltantes
            $created_years = [];
            foreach ($missing_years as $year) {
                $sql = "INSERT INTO vacation_annual_balances
                        (employee_id, year, dias_vacaciones_anuales, dias_pagados_year,
                         dias_disfrutados_year, saldo_disponible_year, created_at, updated_at)
                        VALUES (?, ?, 30, 0, 0, 30, NOW(), NOW())";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$employee_id, $year]);

                $created_years[] = $year;
            }

            return [
                'success' => true,
                'message' => 'Años de vacaciones generados exitosamente',
                'years_created' => $created_years,
                'count' => count($created_years),
                'first_year' => $first_year,
                'current_year' => $current_year
            ];

        } catch (PDOException $e) {
            error_log("Error generating missing years: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al generar años: ' . $e->getMessage(),
                'years_created' => [],
                'count' => 0
            ];
        }
    }
}