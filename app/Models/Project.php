<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'description', 'owner_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function leads() { return $this->hasMany(Lead::class); }
}
