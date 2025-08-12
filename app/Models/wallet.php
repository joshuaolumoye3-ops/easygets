<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'balance',
    ];

    /**
     * Relationship: A wallet belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: A wallet can have multiple virtual accounts.
     */
    public function virtualAccounts()
    {
        return $this->hasMany(VirtualAccount::class);
    }

    /**
     * Relationship: A wallet can have many transactions.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
