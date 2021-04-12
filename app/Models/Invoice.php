<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'account',
        'address',
        'invoice_amount',
        'paid_amount',
        'is_recurring',
        'is_paid',
        'next_auto_charge',
    ];
}
