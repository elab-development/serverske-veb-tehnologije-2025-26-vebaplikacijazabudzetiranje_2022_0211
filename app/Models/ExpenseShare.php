<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseShare extends Model
{
    protected $fillable = [
        'expense_id',
        'user_id',
        'amount_owed',
        'is_paid',
    ];
}
