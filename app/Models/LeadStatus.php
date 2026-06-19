<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadStatus extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'color', 'sort_order', 'is_system', 'owner_id'];

    protected $casts = ['is_system' => 'boolean'];

    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function leads() { return $this->hasMany(Lead::class, 'status_id'); }
}
