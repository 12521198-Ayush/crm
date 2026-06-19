<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id', 'name', 'description', 'trigger_event', 'match_type',
        'delay_minutes', 'graph', 'is_active', 'executions_count',
        'last_run_at', 'created_by',
    ];

    protected $casts = [
        'graph'         => 'array',
        'is_active'     => 'boolean',
        'delay_minutes' => 'integer',
        'last_run_at'   => 'datetime',
    ];

    public function owner()      { return $this->belongsTo(User::class, 'owner_id'); }
    public function creator()    { return $this->belongsTo(User::class, 'created_by'); }
    public function conditions() { return $this->hasMany(AutomationRuleCondition::class, 'rule_id')->orderBy('sort'); }
    public function actions()    { return $this->hasMany(AutomationRuleAction::class, 'rule_id')->orderBy('sort'); }

    /** The primary send action (Phase 1: a rule has one send_whatsapp_template action). */
    public function primaryAction()
    {
        return $this->hasOne(AutomationRuleAction::class, 'rule_id')->orderBy('sort');
    }
}
