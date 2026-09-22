<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftcardOrder extends Model
{
    protected $fillable = [
        'reference', 'client_token', 'user_id', 'type', 'status', 'provider_reference', 'provider_status',
        'product_id', 'title', 'category', 'image_url', 'card_form', 'country', 'currency_code',
        'face_value', 'quantity', 'unit_rate', 'total_ngn', 'provider_amount_ngn', 'payout_ngn',
        'pricing', 'codes', 'attachments', 'comments', 'reject_reason', 'needs_review',
        'wallet_debited', 'settled_at', 'last_synced_at',
    ];

    protected $casts = [
        'face_value'          => 'decimal:2',
        'unit_rate'           => 'decimal:4',
        'total_ngn'           => 'decimal:2',
        'provider_amount_ngn' => 'decimal:2',
        'payout_ngn'          => 'decimal:2',
        'pricing'             => 'array',
        'attachments'         => 'array',
        'codes'               => 'encrypted:array',
        'comments'            => 'encrypted',
        'needs_review'        => 'boolean',
        'wallet_debited'      => 'boolean',
        'settled_at'          => 'datetime',
        'last_synced_at'      => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function statusLabel(): string
    {
        if ($this->type === 'buy') {
            return match ($this->status) {
                'completed'          => 'Delivered',
                'failed', 'refunded' => 'Failed · refunded',
                default              => 'Processing',
            };
        }
        return match ($this->status) {
            'completed' => 'Paid',
            'rejected'  => 'Rejected',
            'failed'    => 'Failed',
            default     => 'Under review',
        };
    }

    public function toUserArray(bool $withCodes = false): array
    {
        $amount = $this->type === 'sell' ? ($this->payout_ngn ?? $this->total_ngn) : $this->total_ngn;
        $note = null;
        if (in_array($this->status, ['pending', 'processing'], true)) {
            $note = $this->type === 'buy'
                ? 'Your card is being prepared. Codes appear here as soon as delivery completes.'
                : 'Your card is being reviewed. You will be credited once it is approved.';
        }
        return [
            'reference'     => $this->reference,
            'type'          => $this->type,
            'status'        => $this->status,
            'status_label'  => $this->statusLabel(),
            'title'         => $this->title,
            'category'      => $this->category,
            'image'         => $this->image_url,
            'face_value'    => number_format((float) $this->face_value, 2),
            'currency'      => $this->currency_code,
            'quantity'      => (int) $this->quantity,
            'amount'        => number_format((float) $amount, 2),
            'reject_reason' => $this->reject_reason,
            'note'          => $note,
            'date'          => $this->created_at?->format('M d, Y h:i A'),
            'codes'         => ($withCodes && $this->type === 'buy' && $this->status === 'completed') ? ($this->codes ?? []) : [],
        ];
    }
}