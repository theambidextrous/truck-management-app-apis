<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setup extends Model
{
    use HasFactory;

    protected $fillable = [
        'company',
        'address',
        'city',
        'state',
        'zip',
        'email',
        'phone',
        'fax',
        'is_active',
    ];
}
