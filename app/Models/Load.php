<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Load extends Model
{
    use HasFactory;
    protected $fillable = [
        'dispatcher',
        'date',
        'bol',
        'company',
        'contact',
        'street',
        'city',
        'state',
        'zip',
        'broker',
        'd_date',
        'pol',
        'd_company',
        'd_contact',
        'd_street',
        'd_city',
        'd_state',
        'd_zip',
        'delivery_docs',
        'truck',
        'trailer',
        'miles',
        'weight',
        'rate',
        'driver_a',
        'driver_b',
        'is_active',
        'is_delivered',
        'is_paid',
    ];
}
