<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'reference',
        'user_id',
        'type',
        'channel',
        'provider',
        'phone',
        'recipient',
        'amount',
        'status',
        'response',
    ];
}
