<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'amount', 'type', 'description', 'category', 'receipt_image_url'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // A Transaction belongs to one User
    public function user() {
        return $this->belongsTo(User::class);
    }
}
