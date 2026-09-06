<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settlement extends Model
{
    protected $fillable = [
        'group_id',
        'from_user_id',
        'to_user_id',
        'amount',
        'settled_at',
    ];
}
