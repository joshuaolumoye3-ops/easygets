<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SocialServiceDescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'description',
        'custom_name',
        'custom_category',
        'admin_percentage',
    ];
}

