<?php

namespace App\Models;

use App\Core\Model;

class LoanInstallment extends Model
{
    public $table = 'loan_installments';
    protected $fillable = [
        'loan_id',
        'installment_number',
        'amount',
        'due_date',
        'status',
        'created_at'
    ];
    protected $timestamps = false;
}
