<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class virtualAccount extends Model
{
    protected $fillable = [
        'wallet_id',
        'account_reference',
        'customer_email',
        'customer_name',
        'bank_name',
        'bank_code',
        'account_number',
        'status',
        'expires_at',
        'amount'
    ];

    protected $casts = [
        'expires_at' => 'datetime'
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }


}
