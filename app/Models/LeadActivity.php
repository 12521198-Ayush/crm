<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadActivity extends Model
{
    use HasFactory;

    public const TYPE_NOTE      = 'note';
    public const TYPE_CALL      = 'call';
    public const TYPE_WHATSAPP  = 'whatsapp';
    public const TYPE_MEETING   = 'meeting';
    public const TYPE_FOLLOWUP  = 'follow_up';
    public const TYPE_STATUS    = 'status_change';
    public const TYPE_ASSIGN    = 'assignment';
    public const TYPE_EVENT     = 'event';

    protected $fillable = [
        'lead_id', 'user_id', 'type', 'title', 'body',
        'scheduled_at', 'meta',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'meta'         => 'array',
    ];

    public function lead() { return $this->belongsTo(Lead::class); }
    public function user() { return $this->belongsTo(User::class); }
}
