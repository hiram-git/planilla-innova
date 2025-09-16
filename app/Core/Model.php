<?php

namespace App\Core;

abstract class Model
{
    public $db;
    public $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $timestamps = false;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all()
    {
        return $this->db->findAll("SELECT * FROM {$this->table}");
    }

    public function find($id)
    {
        return $this->db->find("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?", [$id]);
    }

    public function where($field, $operator, $value = null)
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        return $this->db->findAll("SELECT * FROM {$this->table} WHERE {$field} {$operator} ?", [$value]);
    }

    public function first($field, $operator, $value = null)
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        return $this->db->find("SELECT * FROM {$this->table} WHERE {$field} {$operator} ?", [$value]);
    }

    public function create($data)
    {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->insert($this->table, $data);
    }

    public function update($id, $data)
    {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->update($this->table, $data, "{$this->primaryKey} = :id", ['id' => $id]);
    }

    public function delete($id)
    {
        return $this->db->delete($this->table, "{$this->primaryKey} = :id", ['id' => $id]);
    }

    protected function filterFillable($data)
    {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }

    public function validate($data, $rules)
    {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $ruleArray = explode('|', $rule);
            
            foreach ($ruleArray as $singleRule) {
                if ($singleRule === 'required' && empty($data[$field])) {
                    $errors[$field] = "El campo {$field} es requerido";
                    break;
                }
                
                if (strpos($singleRule, 'min:') === 0) {
                    $min = (int) substr($singleRule, 4);
                    if (strlen($data[$field]) < $min) {
                        $errors[$field] = "El campo {$field} debe tener al menos {$min} caracteres";
                        break;
                    }
                }
                
                if (strpos($singleRule, 'max:') === 0) {
                    $max = (int) substr($singleRule, 4);
                    if (strlen($data[$field]) > $max) {
                        $errors[$field] = "El campo {$field} no debe exceder {$max} caracteres";
                        break;
                    }
                }
                
                if ($singleRule === 'email' && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "El campo {$field} debe ser un email válido";
                    break;
                }
                
                if ($singleRule === 'date' && !empty($data[$field]) && !$this->isValidDate($data[$field])) {
                    $errors[$field] = "El campo {$field} debe ser una fecha válida";
                    break;
                }
            }
        }
        
        return $errors;
    }
    
    private function isValidDate($date, $format = 'Y-m-d')
    {
        $dateObj = \DateTime::createFromFormat($format, $date);
        return $dateObj && $dateObj->format($format) === $date;
    }

    /**
     * Contar registros en la tabla
     */
    public function count($conditions = null, $params = [])
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        
        if ($conditions) {
            $sql .= " WHERE {$conditions}";
        }
        
        $result = $this->db->find($sql, $params);
        return intval($result['total'] ?? 0);
    }

    /**
     * Obtener instancia de la base de datos
     */
    public function getDatabase()
    {
        return $this->db;
    }
}