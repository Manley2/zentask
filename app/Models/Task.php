<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'due_date',
        'status',
    ];

    /**
     * Relasi Task -> User
     * Setiap task dimiliki oleh 1 user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

