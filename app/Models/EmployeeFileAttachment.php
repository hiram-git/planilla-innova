<?php

namespace App\Models;

use App\Core\Model;

class EmployeeFileAttachment extends Model
{
    public $table = 'employee_file_attachments';
    protected $fillable = [
        'employee_file_id',
        'label',
        'file_path',
        'original_name',
        'mime_type',
        'file_size'
    ];
    protected $timestamps = false;

    public function getByEmployeeFile(int $employeeFileId): array
    {
        $sql = "SELECT *
                FROM employee_file_attachments
                WHERE employee_file_id = ?
                ORDER BY id DESC";

        return $this->db->findAll($sql, [$employeeFileId]);
    }

    public function getByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT *
                FROM employee_file_attachments
                WHERE id IN ($placeholders)";

        return $this->db->findAll($sql, array_values($ids));
    }

    public function deleteByIds(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM employee_file_attachments WHERE id IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($ids));

        return $stmt->rowCount();
    }
}
