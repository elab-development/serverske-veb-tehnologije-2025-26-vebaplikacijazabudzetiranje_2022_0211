<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'group_id',
        'category_id',
        'paid_by',
        'amount',
        'description',
        'payment_date',
    ];
}
