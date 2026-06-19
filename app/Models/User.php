<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_MASTER     = 'master';
    public const ROLE_SUPER_MASTER = 'super_master';
    public const ROLE_SUB_MASTER = 'sub_master';
    public const ROLE_AGENT      = 'agent';
    public const ROLE_SUB_AGENT  = 'sub_agent';

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'role',
        'parent_id', 'subscription_plan', 'subscription_starts_at',
        'subscription_expires_at', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'  => 'hashed',
            'subscription_starts_at' => 'date',
            'subscription_expires_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function parent()   { return $this->belongsTo(User::class, 'parent_id'); }
    public function children() { return $this->hasMany(User::class, 'parent_id'); }
    public function assignedLeads() { return $this->hasMany(Lead::class, 'assigned_to'); }

    public function isSuperMaster(): bool { return $this->role === self::ROLE_SUPER_MASTER; }
    public function isMaster(): bool    { return $this->role === self::ROLE_MASTER; }
    public function isSubMaster(): bool { return $this->role === self::ROLE_SUB_MASTER; }
    public function isAgent(): bool     { return $this->role === self::ROLE_AGENT; }
    public function isSubAgent(): bool  { return $this->role === self::ROLE_SUB_AGENT; }

    /** Team chain: returns ids of self + all descendants (for sub_master) */
    public function teamUserIds(): array
    {
        $ids = [$this->id];
        $queue = [$this->id];
        while ($queue) {
            $rows = User::whereIn('parent_id', $queue)->pluck('id')->all();
            $queue = $rows;
            $ids = array_merge($ids, $rows);
        }
        return $ids;
    }
}
