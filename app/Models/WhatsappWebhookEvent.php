<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappWebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id', 'connection_id', 'event_type', 'remote_message_id',
        'payload', 'signature_valid', 'processed', 'received_at',
    ];

    protected $casts = [
        'payload'         => 'array',
        'signature_valid' => 'boolean',
        'processed'       => 'boolean',
        'received_at'     => 'datetime',
    ];

    public function connection() { return $this->belongsTo(WhatsupninjaConnection::class, 'connection_id'); }
}
