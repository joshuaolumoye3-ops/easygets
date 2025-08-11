<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailOtp extends Model
{
    protected $fillable = ['user_id', 'otp', 'expires_at'];

    // Relationship to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
