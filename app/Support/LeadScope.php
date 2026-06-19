<?php

namespace App\Support;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class LeadScope
{
    /** Constrain a Lead query to what $user is allowed to see. */
    public static function apply(Builder $query, User $user): Builder
    {
        if ($user->isSuperMaster() || $user->isMaster()) {
            return $query;
        }
        if ($user->isSubMaster()) {
            $ids = $user->teamUserIds();
            return $query->where(function ($q) use ($ids) {
                $q->whereIn('assigned_to', $ids)->orWhereIn('created_by', $ids);
            });
        }
        if ($user->isAgent()) {
            $ids = $user->teamUserIds();
            return $query->where(function ($q) use ($ids) {
                $q->whereIn('assigned_to', $ids)->orWhereIn('created_by', $ids);
            });
        }

        return $query->where('assigned_to', $user->id);
    }
}
