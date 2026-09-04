<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = ['service_id', 'name', 'code', 'api_identifier', 'status'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
