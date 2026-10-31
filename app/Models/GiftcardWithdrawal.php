<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftcardWithdrawal extends Model
{
    protected $fillable = [
        'reference', 'client_token', 'user_id', 'amount', 'fee_amount', 'payout_amount', 'bank_name', 'bank_code', 'account_number', 'account_name',
        'status', 'provider', 'provider_transfer_id', 'failure_reason', 'payout_attempts', 'payout_alerted_at',
        'admin_id', 'admin_note', 'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2', 'fee_amount' => 'decimal:2', 'payout_amount' => 'decimal:2', 'payout_attempts' => 'integer',
        'processed_at' => 'datetime', 'payout_alerted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}