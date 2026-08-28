<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Wallet;
use App\Models\Transaction;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'password',
        'pin',
        'is_blocked',
        'blocked_reason',
        'blocked_at',
        'last_login_at',
        // Referral / partner fields
        'is_partner',
        'partner_percentage',
        'partner_min_withdrawal',
        'referral_balance',
        'referred_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'pin',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'      => 'datetime',
            'blocked_at'             => 'datetime',
            'last_login_at'          => 'datetime',
            'password'               => 'hashed',
            'is_blocked'             => 'boolean',
            'is_partner'             => 'boolean',
            'partner_percentage'     => 'decimal:2',
            'partner_min_withdrawal' => 'decimal:2',
            'referral_balance'       => 'decimal:2',
        ];
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_blocked', false);
    }

    public function scopeBlocked($query)
    {
        return $query->where('is_blocked', true);
    }

    public function scopePartners($query)
    {
        return $query->where('is_partner', true);
    }

    // ── Block helpers ─────────────────────────────────────────

    public function isBlocked(): bool
    {
        return (bool) $this->is_blocked;
    }

    public function block(string $reason = null): void
    {
        $this->update([
            'is_blocked'     => true,
            'blocked_reason' => $reason,
            'blocked_at'     => now(),
        ]);
    }

    public function unblock(): void
    {
        $this->update([
            'is_blocked'     => false,
            'blocked_reason' => null,
            'blocked_at'     => null,
        ]);
    }

    // ── Relationships ─────────────────────────────────────────

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /** The user who referred this user */
    public function referrer()
    {
        return $this->belongsTo(\App\Models\User::class, 'referred_by');
    }

    /** Users this user has referred */
    public function referrals()
    {
        return $this->hasMany(\App\Models\Referral::class, 'referrer_id');
    }

    /** Referral record where this user is the referred party */
    public function referralRecord()
    {
        return $this->hasOne(\App\Models\Referral::class, 'referred_id');
    }

    /** Earnings this user has generated as a referrer */
    public function referralEarnings()
    {
        return $this->hasMany(\App\Models\ReferralEarning::class, 'referrer_id');
    }

    /** Partner withdrawal requests */
    public function partnerWithdrawals()
    {
        return $this->hasMany(\App\Models\PartnerWithdrawal::class, 'user_id');
    }

    // ── Referral helpers ──────────────────────────────────────

    /** The effective commission rate for this user when they refer someone */
    public function referralPercentage(): float
    {
        if ($this->is_partner && $this->partner_percentage !== null) {
            return (float) $this->partner_percentage;
        }
        return \App\Models\ReferralSetting::globalPercentage();
    }

    /** Credit referral balance */
    public function creditReferralBalance(float $amount): void
    {
        $this->increment('referral_balance', $amount);
    }

    /** Debit referral balance (returns false if insufficient funds) */
    public function debitReferralBalance(float $amount): bool
    {
        if ((float) $this->referral_balance < $amount) {
            return false;
        }
        $this->decrement('referral_balance', $amount);
        return true;
    }

    /** Promote to partner */
    public function promoteToPartner(float $percentage, float $minWithdrawal): void
    {
        $this->update([
            'is_partner'             => true,
            'partner_percentage'     => $percentage,
            'partner_min_withdrawal' => $minWithdrawal,
        ]);
    }

    /** Demote from partner back to normal user */
    public function demoteFromPartner(): void
    {
        $this->update([
            'is_partner'             => false,
            'partner_percentage'     => null,
            'partner_min_withdrawal' => null,
        ]);
    }
}