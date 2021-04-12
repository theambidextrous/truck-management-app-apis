<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deduction extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'account',
        'expense',
        'deducted',
        'payment_date',
        'initiator',
    ];
}
