<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name', 'mobile', 'email', 'city',
        'project_id', 'source_id', 'sub_source', 'status_id',
        'budget', 'remarks', 'follow_up_at',
        'assigned_to', 'created_by', 'external_id',
        'campaign_name', 'ad_set_name', 'ad_name', 'form_name',
        'custom_fields', 'raw_payload',
    ];

    protected $casts = [
        'budget'        => 'decimal:2',
        'follow_up_at'  => 'datetime',
        'custom_fields' => 'array',
        'raw_payload'   => 'array',
    ];

    public function project()  { return $this->belongsTo(Project::class); }
    public function source()   { return $this->belongsTo(LeadSource::class, 'source_id'); }
    public function status()   { return $this->belongsTo(LeadStatus::class, 'status_id'); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function creator()  { return $this->belongsTo(User::class, 'created_by'); }
    public function activities() { return $this->hasMany(LeadActivity::class)->orderByDesc('id'); }
}
