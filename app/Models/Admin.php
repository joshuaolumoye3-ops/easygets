<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'last_login',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    public function transactions()
    {
        return $this->hasMany(AdminTransaction::class);
    }
}
