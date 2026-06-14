<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EsimCountry extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_name',
        'country_code',
        'base_price',
        'description',
        'flag_url',
        'is_active',
        'local_number_available',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'local_number_available' => 'boolean',
        'base_price' => 'decimal:2',
    ];

    public function packages()
    {
        return $this->hasMany(EsimPackage::class, 'esim_country_id');
    }
}