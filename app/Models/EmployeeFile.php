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
}
