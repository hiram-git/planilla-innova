<?php

namespace App\Models;

use App\Core\Model;

class Loan extends Model
{
    public $table = 'loans';
    protected $fillable = [
        'employee_id',
        'loan_type',
        'frequency',
        'allow_december',
        'total_amount',
        'installments_count',
        'start_date',
        'created_at'
    ];
    protected $timestamps = false;
}
