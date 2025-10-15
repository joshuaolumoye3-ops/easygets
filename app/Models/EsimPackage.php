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
    ];

    public function country()
    {
        return $this->belongsTo(EsimCountry::class);
    }
}
