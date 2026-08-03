<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DedicatedAccount extends Model
{
    protected $fillable = [
        'user_id',
        'wallet_id',
        'provider',
        'account_reference',
        'provider_unique_id',
        'account_number',
        'bank_code',
        'bank_name',
        'account_name',
        'currency',
        'status',
        'bvn_last4',
        'nin_last4',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
