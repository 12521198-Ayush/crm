<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    use HasFactory;

    protected $fillable = ['label', 'key', 'type', 'options', 'required', 'sort_order'];

    protected $casts = [
        'options'  => 'array',
        'required' => 'boolean',
    ];
}
