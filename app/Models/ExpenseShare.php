<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseShare extends Model
{
    use HasFactory;
    protected $fillable = [
        'expense_id',
        'user_id',
        'amount_owed',
        'is_paid',
    ];

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
