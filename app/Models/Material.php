<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'semester',
        'unit',
        'file_path',
        'rating'
    ];

    protected $casts = [
        'rating' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s'
    ];

    protected $attributes = [
        'rating' => 0
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookmarkedBy()
    {
        return $this->belongsToMany(User::class, 'bookmarks', 'material_id', 'user_id')
            ->withTimestamps();
    }
}
