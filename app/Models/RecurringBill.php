<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecurringBill extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'description', 'amount', 'type', 
        'category', 'frequency', 'start_date', 
        'next_payment_date', 'is_active'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'next_payment_date' => 'date',
        'is_active' => 'boolean',
    ];
}