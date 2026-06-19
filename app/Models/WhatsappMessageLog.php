<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappMessageLog extends Model
{
    use HasFactory;

    public const STATUS_QUEUED    = 'queued';
    public const STATUS_SENT      = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_READ      = 'read';
    public const STATUS_FAILED    = 'failed';

    protected $fillable = [
        'owner_id', 'lead_id', 'rule_id', 'connection_id', 'template_id',
        'trigger_event', 'recipient', 'variables', 'status', 'remote_message_id',
        'error_message', 'queued_at', 'sent_at', 'delivered_at', 'read_at',
        'failed_at', 'response_payload',
    ];

    protected $casts = [
        'variables'        => 'array',
        'response_payload' => 'array',
        'queued_at'        => 'datetime',
        'sent_at'          => 'datetime',
        'delivered_at'     => 'datetime',
        'read_at'          => 'datetime',
        'failed_at'        => 'datetime',
    ];

    public function owner()    { return $this->belongsTo(User::class, 'owner_id'); }
    public function lead()     { return $this->belongsTo(Lead::class, 'lead_id'); }
    public function rule()     { return $this->belongsTo(AutomationRule::class, 'rule_id'); }
    public function template() { return $this->belongsTo(WhatsappTemplate::class, 'template_id'); }
}
