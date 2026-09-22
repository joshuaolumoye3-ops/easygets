<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftcardWithdrawal extends Model
{
    protected $fillable = [
        'reference', 'user_id', 'amount', 'bank_name', 'account_number', 'account_name',
        'status', 'admin_id', 'admin_note', 'processed_at',
    ];

    protected $casts = ['amount' => 'decimal:2', 'processed_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}