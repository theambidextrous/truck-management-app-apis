<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'account',
        'company',
        'contact_name',
        'address',
        'city',
        'state',
        'zip',
        'email',
        'phone',
        'is_active',
    ];
}
