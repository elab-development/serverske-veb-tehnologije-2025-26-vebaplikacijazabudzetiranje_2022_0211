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

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function payer()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function shares()
    {
        return $this->hasMany(ExpenseShare::class);
    }
}
