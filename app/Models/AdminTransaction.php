<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'action',
        'amount',
        'affected_user_id',
        'description',
        'reference',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function affectedUser()
    {
        return $this->belongsTo(User::class, 'affected_user_id');
    }
}

