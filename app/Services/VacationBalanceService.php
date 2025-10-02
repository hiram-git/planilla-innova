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
     */
    public function updateAnnualBalance($employee_id, $year, $dias_pagados, $dias_disfrutados)
    {
        try {
            // Obtener balance actual
            $balance = $this->getAnnualBalance($employee_id, $year);
            if (!$balance) {
                return false;
            }

            // Calcular nuevos totales
            $new_dias_pagados = $balance['dias_pagados_year'] + $dias_pagados;
            $new_dias_disfrutados = $balance['dias_disfrutados_year'] + $dias_disfrutados;
            $new_saldo = $balance['dias_vacaciones_anuales'] - $new_dias_pagados - $new_dias_disfrutados;

            // Validar que no exceda los 30 días anuales
            if ($new_dias_pagados + $new_dias_disfrutados > $balance['dias_vacaciones_anuales']) {
                throw new \Exception("Total de días excede el límite anual de {$balance['dias_vacaciones_anuales']} días");
            }

            // Actualizar balance anual
            $sql = "UPDATE vacation_annual_balances
                    SET dias_pagados_year = ?,
                        dias_disfrutados_year = ?,
                        saldo_disponible_year = ?
                    WHERE employee_id = ? AND year = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$new_dias_pagados, $new_dias_disfrutados, $new_saldo, $employee_id, $year]);

            // Actualizar totales generales
            $this->updateGeneralTotals($employee_id, $dias_pagados, $dias_disfrutados);

            return true;
        } catch (PDOException $e) {
            error_log("Error updating annual balance: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener totales generales acumulativos de un empleado
     */
    public function getGeneralTotals($employee_id)
    {
        try {
            $sql = "SELECT * FROM vacation_general_totals WHERE employee_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id]);
            $totals = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$totals) {
                // Crear registro inicial si no existe
                $totals = $this->createGeneralTotals($employee_id);
            }

            return $totals;
        } catch (PDOException $e) {
            error_log("Error getting general totals: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear totales generales iniciales
     */
    private function createGeneralTotals($employee_id)
    {
        try {
            $sql = "INSERT INTO vacation_general_totals (employee_id) VALUES (?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id]);

            return [
                'id' => $this->db->lastInsertId(),
                'employee_id' => $employee_id,
                'total_dias_pagados' => 0.00,
                'total_dias_disfrutados' => 0.00,
                'total_saldo_acumulado' => 0.00
            ];
        } catch (PDOException $e) {
            error_log("Error creating general totals: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar totales generales acumulativos
     */
    private function updateGeneralTotals($employee_id, $dias_pagados, $dias_disfrutados)
    {
        try {
            // Obtener totales actuales
            $totals = $this->getGeneralTotals($employee_id);
            if (!$totals) {
                return false;
            }

            // Calcular nuevos totales
            $new_total_pagados = $totals['total_dias_pagados'] + $dias_pagados;
            $new_total_disfrutados = $totals['total_dias_disfrutados'] + $dias_disfrutados;

            // Calcular saldo acumulado total (suma de todos los años disponibles)
            $new_saldo_acumulado = $this->calculateTotalAccumulatedBalance($employee_id);

            // Actualizar totales generales
            $sql = "UPDATE vacation_general_totals
                    SET total_dias_pagados = ?,
                        total_dias_disfrutados = ?,
                        total_saldo_acumulado = ?
                    WHERE employee_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$new_total_pagados, $new_total_disfrutados, $new_saldo_acumulado, $employee_id]);

            return true;
        } catch (PDOException $e) {
            error_log("Error updating general totals: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcular saldo acumulado total de todos los años
     */
    private function calculateTotalAccumulatedBalance($employee_id)
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
            error_log("Error calculating total accumulated balance: " . $e->getMessage());
            return 0;
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
     */
    public function canProcessVacationRequest($employee_id, $year, $dias_pagados, $dias_disfrutados)
    {
        try {
            $balance = $this->getAnnualBalance($employee_id, $year);
            if (!$balance) {
                return ['valid' => false, 'message' => 'No se pudo obtener el balance anual'];
            }

            $total_solicitado = $dias_pagados + $dias_disfrutados;
            $total_usado_actual = $balance['dias_pagados_year'] + $balance['dias_disfrutados_year'];
            $nuevo_total = $total_usado_actual + $total_solicitado;

            if ($nuevo_total > $balance['dias_vacaciones_anuales']) {
                return [
                    'valid' => false,
                    'message' => "Total solicitado ({$total_solicitado}) excede el saldo disponible ({$balance['saldo_disponible_year']}) para el año {$year}"
                ];
            }

            return ['valid' => true, 'message' => 'Solicitud válida'];
        } catch (Exception $e) {
            return ['valid' => false, 'message' => 'Error validando solicitud: ' . $e->getMessage()];
        }
    }
}