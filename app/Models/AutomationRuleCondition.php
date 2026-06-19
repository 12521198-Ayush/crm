<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomationRuleCondition extends Model
{
    use HasFactory;

    protected $fillable = ['rule_id', 'field', 'operator', 'value', 'sort'];

    protected $casts = ['value' => 'array'];

    public function rule() { return $this->belongsTo(AutomationRule::class, 'rule_id'); }
}
