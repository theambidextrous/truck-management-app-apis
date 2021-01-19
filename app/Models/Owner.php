<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Owner extends Model
{
    use HasFactory;

    protected $fillable = [
        'company',
        // 'fname',
        // 'lname',
        'address',
        'city',
        'state',
        'zip',
        'email',
        'phone',
        'is_active',
    ];
}
