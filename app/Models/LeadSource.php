<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadSource extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'channel', 'config', 'is_active'];

    protected $casts = [
        'config'    => 'array',
        'is_active' => 'boolean',
    ];
}
