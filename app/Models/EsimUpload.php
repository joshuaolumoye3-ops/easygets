<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EsimUpload extends Model
{
    protected $fillable = [
        'user_id',
        'transaction_id',
        'number',
        'details',
        'images',
        'link',
    ];

    protected $casts = [
        'images' => 'array', // Automatically cast JSON to array and back
    ];

    /**
     * Get the user that owns the upload
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transaction associated with this upload
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get full URLs for images with proper path handling
     */
    public function getImageUrlsAttribute()
    {
        if (!$this->images) {
            return [];
        }

        return collect($this->images)->map(function ($image) {
            // Normalize: remove escaped slashes
            $image = str_replace('\\/', '/', $image);
            $image = ltrim($image, '/');
            
            // Handle storage paths
            if (strpos($image, 'storage/') === 0) {
                $actualPath = str_replace('storage/', '', $image);
                return \Illuminate\Support\Facades\Storage::disk('public')->url($actualPath);
            } else if (strpos($image, 'uploads/') === 0) {
                // Public folder paths
                return url('/' . $image);
            }
            
            return asset($image); // Fallback
        })->toArray();
    }
}