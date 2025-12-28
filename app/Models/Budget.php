<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'category', 'amount'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}