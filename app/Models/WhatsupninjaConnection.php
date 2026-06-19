<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsupninjaConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id', 'base_url', 'account_id',
        'api_key', 'username', 'password', 'jwt_token', 'jwt_expires_at',
        'status', 'last_error', 'webhook_token', 'webhook_secret',
        'last_synced_at', 'is_active',
    ];

    protected $casts = [
        'api_key'        => 'encrypted',
        'username'       => 'encrypted',
        'password'       => 'encrypted',
        'jwt_token'      => 'encrypted',
        'jwt_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'is_active'      => 'boolean',
    ];

    // Never expose secrets/credentials through API serialization.
    protected $hidden = [
        'api_key', 'username', 'password', 'jwt_token', 'webhook_secret',
    ];

    public function owner()     { return $this->belongsTo(User::class, 'owner_id'); }
    public function templates() { return $this->hasMany(WhatsappTemplate::class, 'connection_id'); }

    public function isConnected(): bool
    {
        return $this->status === 'connected' && $this->is_active;
    }
}
