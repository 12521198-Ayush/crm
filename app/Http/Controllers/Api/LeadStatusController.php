<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeadStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LeadStatusController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        return LeadStatus::query()
            ->where(function ($q) use ($user) {
                $q->where('is_system', true)
                  ->orWhere('owner_id', $user->isSubMaster() ? $user->id : ($user->parent_id ?? $user->id));
                if ($user->isMaster()) $q->orWhereNotNull('owner_id');
            })
            ->orderBy('sort_order')->orderBy('name')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:80',
            'color'      => 'nullable|string|max:16',
            'sort_order' => 'nullable|integer',
        ]);
        $data['slug']     = Str::slug($data['name']);
        $data['owner_id'] = $request->user()->id;
        $data['is_system']= false;
        return LeadStatus::create($data);
    }

    public function update(Request $request, LeadStatus $status)
    {
        if ($status->is_system && ! $request->user()->isMaster()) abort(403);
        if ($status->owner_id && $status->owner_id !== $request->user()->id && ! $request->user()->isMaster()) abort(403);
        $data = $request->validate([
            'name'       => 'sometimes|string|max:80',
            'color'      => 'nullable|string|max:16',
            'sort_order' => 'nullable|integer',
        ]);
        if (isset($data['name'])) $data['slug'] = Str::slug($data['name']);
        $status->update($data);
        return $status;
    }

    public function destroy(Request $request, LeadStatus $status)
    {
        if ($status->is_system) abort(422, 'System status cannot be deleted.');
        if ($status->owner_id && $status->owner_id !== $request->user()->id && ! $request->user()->isMaster()) abort(403);
        $status->delete();
        return response()->noContent();
    }
}
