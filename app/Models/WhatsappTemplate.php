<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'connection_id', 'owner_id', 'remote_id', 'name', 'category',
        'language', 'status', 'components', 'variables', 'is_active', 'last_synced_at',
    ];

    protected $casts = [
        'components'     => 'array',
        'variables'      => 'array',
        'is_active'      => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function connection() { return $this->belongsTo(WhatsupninjaConnection::class, 'connection_id'); }
    public function owner()      { return $this->belongsTo(User::class, 'owner_id'); }

    /** Number of body placeholders ({{1}}..{{n}}) this template expects. */
    public function bodyVariableCount(): int
    {
        return (int) ($this->variables['body_count'] ?? 0);
    }
}
