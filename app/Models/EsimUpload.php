<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EsimUpload extends Model
{
    protected $fillable = [
        'user_id',
        'transaction_id',
        'number',
        'details',
        'images',
    ];

    protected $casts = [
        'images' => 'array', // cast JSON to array
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
