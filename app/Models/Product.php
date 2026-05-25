<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'product_code',
        'product_sno',
        'product_id',
        'name',
        'api_price',
        'client_price',
        'is_active',
        'country',
        'country_code',
        'description',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function getFinalPriceAttribute()
    {
        return $this->client_price ?? $this->api_price;
    }
}
