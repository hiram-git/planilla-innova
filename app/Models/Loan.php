<?php

namespace App\Models;

use App\Core\Model;
use Exception;

class Loan extends Model
{
    public $table = 'loans';
    protected $fillable = [
        'employee_id',
        'creditor_id',
        'loan_type',
        'frequency',
        'allow_december',
        'total_amount',
        'installments_count',
        'start_date',
        'status',
        'created_at'
    ];
    protected $timestamps = false;

    const TIPOS_PRESTAMO = [
        'PERSONAL' => 'Prestamo Personal',
        'VEHICULAR' => 'Prestamo Vehicular',
        'HIPOTECARIO' => 'Credito Hipotecario',
        'EMBARGO' => 'Embargo de Sueldo',
        'JUDICIAL' => 'Retencion Judicial',
        'PENSION' => 'Pension Alimenticia',
        'SEGURO' => 'Seguro/Prima',
        'COOPERATIVA' => 'Cooperativa',
        'OTRO' => 'Otro'
    ];

    public function getTiposPrestamo()
    {
        return self::TIPOS_PRESTAMO;
    }

    public function cancelLoan(int $loanId): void
    {
        $loanStatusInfo = $this->getStatusColumnInfo($this->table);
        $installmentStatusInfo = $this->getStatusColumnInfo('loan_installments');
        if (!$installmentStatusInfo) {
            throw new Exception('No se encontro la columna de estado para cuotas.');
        }

        $loanStatusValue = $loanStatusInfo ? $this->resolveStatusValue($loanStatusInfo, 'anulado') : null;
        $installmentStatusValue = $this->resolveStatusValue($installmentStatusInfo, 'anulado');

        $this->db->beginTransaction();

        try {
            if ($loanStatusInfo) {
                $this->db->update(
                    $this->table,
                    [$loanStatusInfo['name'] => $loanStatusValue],
                    'id = :id',
                    ['id' => $loanId]
                );
            } else {
                error_log('Loan cancel: no status column found in loans table.');
            }

            $this->db->update(
                'loan_installments',
                [$installmentStatusInfo['name'] => $installmentStatusValue],
                'loan_id = :loan_id',
                ['loan_id' => $loanId]
            );
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    private function getStatusColumnInfo(string $table): ?array
    {
        $columns = $this->db->findAll("SHOW COLUMNS FROM {$table}");
        foreach ($columns as $column) {
            $field = $column['Field'] ?? '';
            if (!in_array($field, ['status', 'estado'], true)) {
                continue;
            }

            return [
                'name' => $field,
                'allowed' => $this->parseEnumValues($column['Type'] ?? '')
            ];
        }

        return null;
    }

    private function resolveStatusValue(array $statusInfo, string $preferred): string
    {
        $allowed = $statusInfo['allowed'] ?? [];
        if (empty($allowed)) {
            return $preferred;
        }

        if (in_array($preferred, $allowed, true)) {
            return $preferred;
        }

        if (in_array('cancelada', $allowed, true)) {
            return 'cancelada';
        }

        return $allowed[0] ?? $preferred;
    }

    private function parseEnumValues(string $type): array
    {
        if (stripos($type, 'enum(') !== 0) {
            return [];
        }

        $trimmed = trim($type);
        $start = strpos($trimmed, '(');
        $end = strrpos($trimmed, ')');
        if ($start === false || $end === false || $end <= $start) {
            return [];
        }

        $values = substr($trimmed, $start + 1, $end - $start - 1);
        $values = str_replace("'", '', $values);
        $items = array_map('trim', explode(',', $values));
        return array_values(array_filter($items, static fn ($item) => $item !== ''));
    }
}
