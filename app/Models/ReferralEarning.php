<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralEarning extends Model
{
    protected $fillable = [
        'referral_id',
        'referrer_id',
        'referred_id',
        'transaction_id',
        'deposit_amount',
        'percentage',
        'commission',
    ];

    protected $casts = [
        'deposit_amount' => 'decimal:2',
        'percentage'     => 'decimal:2',
        'commission'     => 'decimal:2',
    ];

    public function referral()
    {
        return $this->belongsTo(Referral::class, 'referral_id');
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred()
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }
}