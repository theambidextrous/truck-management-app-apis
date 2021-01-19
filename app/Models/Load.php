<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Load extends Model
{
    use HasFactory;

    protected $fillable = [
        'dispatcher',
        'booking_date',
        'number',
        'origin',
        'destination',
        'milage',
        'rate',
        'weight',
        'truck',
        'driver',
        'is_active',
        'mileage',
    ];
}
