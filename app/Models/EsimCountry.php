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
        'flag_url',
    ];

    public function packages()
    {
        return $this->hasMany(EsimPackage::class);
    }
}
