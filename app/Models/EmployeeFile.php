<?php

namespace App\Models;

use App\Core\Model;

class EmployeeFile extends Model
{
    public $table = 'employee_files';
    protected $fillable = [
        'employee_id',
        'type_id',
        'subtype_id',
        'document_date',
        'document_number',
        'document_year',
        'document_sequence',
        'observations',
        'extra_fields'
    ];
    protected $timestamps = false;

    public function getByEmployee(int $employeeId): array
    {
        $sql = "SELECT ef.*,
                       t.name AS type_name,
                       st.name AS subtype_name,
                       COUNT(a.id) AS attachments_count
                FROM employee_files ef
                INNER JOIN employee_file_types t ON ef.type_id = t.id
                INNER JOIN employee_file_subtypes st ON ef.subtype_id = st.id
                LEFT JOIN employee_file_attachments a ON a.employee_file_id = ef.id
                WHERE ef.employee_id = ?
                GROUP BY ef.id
                ORDER BY ef.document_date DESC, ef.id DESC";

        return $this->db->findAll($sql, [$employeeId]);
    }

    public function getByIdWithRelations(int $id): ?array
    {
        $sql = "SELECT ef.*,
                       t.name AS type_name,
                       st.name AS subtype_name,
                       e.firstname,
                       e.lastname
                FROM employee_files ef
                INNER JOIN employee_file_types t ON ef.type_id = t.id
                INNER JOIN employee_file_subtypes st ON ef.subtype_id = st.id
                INNER JOIN employees e ON ef.employee_id = e.id
                WHERE ef.id = ?";

        return $this->db->find($sql, [$id]) ?: null;
    }

    public function createWithCorrelative(array $data): int
    {
        $this->db->beginTransaction();

        try {
            $correlative = $this->buildDocumentCorrelative(
                (int)($data['type_id'] ?? 0),
                (int)($data['subtype_id'] ?? 0),
                (string)($data['document_date'] ?? date('Y-m-d'))
            );

            $data = array_merge($data, $correlative);
            $id = parent::create($data);
            $this->db->commit();

            return $id;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function updateWithCorrelative(int $id, array $data, array $current): void
    {
        $this->db->beginTransaction();

        try {
            $documentDate = (string)($data['document_date'] ?? $current['document_date'] ?? date('Y-m-d'));
            if ($this->shouldRegenerateCorrelative($data, $current, $documentDate)) {
                $correlative = $this->buildDocumentCorrelative(
                    (int)($data['type_id'] ?? 0),
                    (int)($data['subtype_id'] ?? 0),
                    $documentDate
                );
                $data = array_merge($data, $correlative);
            } else {
                $data = array_merge($data, $this->buildExistingDocumentMetadata($current, $documentDate));
            }

            $this->update($id, $data);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    private function shouldRegenerateCorrelative(array $data, array $current, string $documentDate): bool
    {
        if (empty($current['document_number'])) {
            return true;
        }

        $currentYear = isset($current['document_year'])
            ? (int)$current['document_year']
            : $this->extractDocumentYear((string)($current['document_date'] ?? $documentDate));
        $newYear = $this->extractDocumentYear($documentDate);

        return (int)($data['type_id'] ?? 0) !== (int)($current['type_id'] ?? 0)
            || (int)($data['subtype_id'] ?? 0) !== (int)($current['subtype_id'] ?? 0)
            || $newYear !== $currentYear;
    }

    private function buildDocumentCorrelative(int $typeId, int $subtypeId, string $documentDate): array
    {
        $year = $this->extractDocumentYear($documentDate);
        $sequence = $this->getNextDocumentSequence($typeId, $subtypeId, $year);
        $documentNumber = $this->formatDocumentNumber($typeId, $subtypeId, $year, $sequence);

        return [
            'document_number' => $documentNumber,
            'document_year' => $year,
            'document_sequence' => $sequence
        ];
    }

    private function buildExistingDocumentMetadata(array $current, string $documentDate): array
    {
        $year = isset($current['document_year'])
            ? (int)$current['document_year']
            : $this->extractDocumentYear($documentDate);

        return [
            'document_number' => $current['document_number'] ?? null,
            'document_year' => $year,
            'document_sequence' => $current['document_sequence'] ?? null
        ];
    }

    private function getNextDocumentSequence(int $typeId, int $subtypeId, int $year): int
    {
        $sql = "SELECT MAX(document_sequence) AS max_seq
                FROM employee_files
                WHERE type_id = ?
                  AND subtype_id = ?
                  AND document_year = ?
                FOR UPDATE";
        $row = $this->db->find($sql, [$typeId, $subtypeId, $year]) ?: [];

        if (!array_key_exists('max_seq', $row) || $row['max_seq'] === null) {
            return 1;
        }

        return (int)$row['max_seq'] + 1;
    }

    public function getNextDocumentNumberPreview(int $typeId, int $subtypeId, string $documentDate): ?string
    {
        if ($typeId <= 0 || $subtypeId <= 0) {
            return null;
        }

        $year = $this->extractDocumentYear($documentDate);
        $sequence = $this->getNextDocumentSequencePreview($typeId, $subtypeId, $year);

        return $this->formatDocumentNumber($typeId, $subtypeId, $year, $sequence);
    }

    private function getNextDocumentSequencePreview(int $typeId, int $subtypeId, int $year): int
    {
        $sql = "SELECT MAX(document_sequence) AS max_seq
                FROM employee_files
                WHERE type_id = ?
                  AND subtype_id = ?
                  AND document_year = ?";
        $row = $this->db->find($sql, [$typeId, $subtypeId, $year]) ?: [];

        if (!array_key_exists('max_seq', $row) || $row['max_seq'] === null) {
            return 1;
        }

        return (int)$row['max_seq'] + 1;
    }

    private function extractDocumentYear(string $documentDate): int
    {
        $date = \DateTime::createFromFormat('Y-m-d', $documentDate);
        if (!$date) {
            $date = new \DateTime();
        }

        return (int)$date->format('Y');
    }

    private function formatDocumentNumber(int $typeId, int $subtypeId, int $year, int $sequence): string
    {
        $typeSegment = str_pad((string)$typeId, 3, '0', STR_PAD_LEFT);
        $subtypeSegment = str_pad((string)$subtypeId, 3, '0', STR_PAD_LEFT);
        $sequenceSegment = str_pad((string)$sequence, 4, '0', STR_PAD_LEFT);

        return sprintf('T%s-S%s-%d-%s', $typeSegment, $subtypeSegment, $year, $sequenceSegment);
    }
}
