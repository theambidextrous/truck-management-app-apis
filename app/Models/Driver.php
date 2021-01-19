<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;
  
    protected $fillable = [
        'fname',
        'lname',
        'address',
        'city',
        'state',
        'zip',
        'email',
        'phone',
        'license',
        'experience',
        'is_active',
        'rate_type',
        'rate',
    ];
}
