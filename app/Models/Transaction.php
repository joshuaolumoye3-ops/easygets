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
        'balance_after', // ADDED: Track balance after transaction
        'request_id',
        'service_name',
        'service_logo',
        'country_id',
        'country_name',
        'country_code',
        'operator_name',
        'description',
    ];

    /**
     * Cast attributes to native types
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'response' => 'array', // ADDED: Auto JSON encode/decode
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
     * Scope for wallet funding transactions
     */
    public function scopeWalletFunding($query)
    {
        return $query->where('type', 'Wallet Funding');
    }

    /**
     * Scope for transactions by reference
     */
    public function scopeByReference($query, $reference)
    {
        return $query->where('reference', $reference);
    }

    /**
     * Scope for transactions within date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for recent transactions
     */
    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute()
    {
        return '₦' . number_format($this->amount, 2);
    }

    /**
     * Get formatted balance after transaction
     */
    public function getFormattedBalanceAfterAttribute()
    {
        return $this->balance_after ? '₦' . number_format($this->balance_after, 2) : null;
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

    /**
     * Get human-readable status text
     */
    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'Success' => 'Successful',
            'Failed' => 'Failed',
            'Pending' => 'Pending',
            default => 'Unknown'
        };
    }

    /**
     * Check if transaction is successful
     */
    public function isSuccessful()
    {
        return $this->status === 'Success';
    }

    /**
     * Check if transaction is pending
     */
    public function isPending()
    {
        return $this->status === 'Pending';
    }

    /**
     * Check if transaction is failed
     */
    public function isFailed()
    {
        return $this->status === 'Failed';
    }

    /**
     * Check if transaction is wallet funding
     */
    public function isWalletFunding()
    {
        return strtolower($this->type) === 'wallet funding';
    }

    /**
     * Get eSIM upload relationship
     */
    public function esimUpload()
    {
        return $this->hasOne(EsimUpload::class, 'transaction_id');
    }

    /**
     * Check if transaction is eSIM purchase
     */
    public function isEsim()
    {
        return strtolower($this->type) === 'esim purchase';
    }

    /**
     * Get the transaction response data
     */
    public function getResponseData($key = null, $default = null)
    {
        if ($key === null) {
            return $this->response ?? [];
        }

        return $this->response[$key] ?? $default;
    }

    /**
     * Get Flutterwave transaction ID from response
     */
    public function getFlutterwaveTransactionId()
    {
        return $this->getResponseData('transaction_id');
    }

    /**
     * Get payment method from response
     */
    public function getPaymentMethod()
    {
        return $this->channel ?? $this->getResponseData('payment_data.payment_method.type', 'Unknown');
    }

    /**
     * Format transaction for display
     */
    public function toDisplayArray()
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'type' => $this->type,
            'amount' => $this->formatted_amount,
            'status' => $this->status,
            'status_text' => $this->status_text,
            'status_badge_class' => $this->status_badge_class,
            'channel' => $this->getPaymentMethod(),
            'balance_after' => $this->formatted_balance_after,
            'date' => $this->created_at->format('M d, Y h:i A'),
            'created_at' => $this->created_at,
        ];
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // You can add model events here if needed
        static::creating(function ($transaction) {
            // Ensure reference is set
            if (empty($transaction->reference)) {
                $transaction->reference = 'TXN-' . strtoupper(uniqid());
            }
        });
    }
}