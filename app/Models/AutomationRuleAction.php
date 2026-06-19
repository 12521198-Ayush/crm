<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomationRuleAction extends Model
{
    use HasFactory;

    public const TYPE_SEND_TEMPLATE = 'send_whatsapp_template';

    protected $fillable = ['rule_id', 'type', 'template_id', 'config', 'sort'];

    protected $casts = ['config' => 'array'];

    public function rule()     { return $this->belongsTo(AutomationRule::class, 'rule_id'); }
    public function template() { return $this->belongsTo(WhatsappTemplate::class, 'template_id'); }
}
