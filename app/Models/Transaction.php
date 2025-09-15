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

    /**
     * Get the user that owns the transaction.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the wallet associated with this transaction (if needed).
     */
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Scope for successful transactions
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'Success');
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    /**
     * Scope for failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'Failed');
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute()
    {
        return '₦' . number_format($this->amount, 2);
    }

    /**
     * Get status badge class for styling
     */
    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'Success' => 'bg-modern-success',
            'Failed' => 'bg-modern-danger',
            'Pending' => 'bg-modern-warning',
            default => 'bg-gray-500'
        };
    }
}