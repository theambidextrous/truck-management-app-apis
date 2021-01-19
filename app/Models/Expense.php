<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'truck',
        'amount',
        'description',
        'startdate',
        'enddate',
        'frequency',
        'limit',
        'city',
        'state',
        'misc_amount',
        'is_active',
    ];
}
