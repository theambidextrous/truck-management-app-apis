<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'account',
        'type',
        'truck',
        'amount',
        'description',
        'installment',
        'frequency',
        'city',
        'state',
        'misc_amount',
        'next_due',
        'is_paid',
        'is_active',
    ];
}
