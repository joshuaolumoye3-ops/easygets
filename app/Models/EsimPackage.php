<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EsimPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'esim_country_id',
        'plan',
        'validity_days',
        'price',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'validity_days' => 'integer',
    ];

    public function country()
    {
        return $this->belongsTo(EsimCountry::class, 'esim_country_id');
    }
}