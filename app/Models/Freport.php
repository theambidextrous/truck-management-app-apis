<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Freport extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'download',
        'items',
        'is_active',
    ];
}
