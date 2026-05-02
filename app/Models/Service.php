<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function providers()
    {
        return $this->hasMany(Provider::class);
    }

    /**
     * Scope to get only active services
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if service is active - Always fetch fresh from DB
     * This method forces a fresh database query to ensure real-time status
     */
    public function isActive(): bool
    {
        // Refresh the model from database to ensure latest status
        $this->refresh();
        return $this->is_active === true;
    }

    /**
     * Get fresh service by slug - Always queries fresh from database
     */
    public static function findActiveBySlug($slug): ?self
    {
        return static::where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Check if a service slug is active - Direct DB query, no model caching
     */
    public static function isSlugActive($slug): bool
    {
        $exists = static::where('slug', $slug)
            ->where('is_active', true)
            ->exists();
        
        return $exists;
    }

    /**
     * Get all active services - fresh query each time
     */
    public static function getAllActive()
    {
        return static::where('is_active', true)
            ->get();
    }
}
