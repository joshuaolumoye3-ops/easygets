<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualAccount extends Model
{
    protected $fillable = [
        'wallet_id',
        'account_reference',
        'customer_email',
        'customer_name',
        'bank_name',
        'bank_code',
        'account_number',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
