<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $u = $request->user();
        $q = User::query()->select('id', 'name', 'email', 'phone', 'role', 'parent_id', 'subscription_plan', 'subscription_starts_at', 'subscription_expires_at', 'is_active', 'created_at');

        if ($u->isSubMaster() || $u->isAgent()) {
            $q->whereIn('id', $u->teamUserIds());
        }
        return $q->orderByDesc('id')->paginate(20);
    }

    public function store(Request $request)
    {
        $u = $request->user();
        $allowedRoles = match (true) {
            $u->isSuperMaster() => ['master'],
            $u->isMaster() => ['sub_master', 'agent'],
            $u->isSubMaster() => ['agent'],
            default => ['sub_agent'],
        };

        $data = $request->validate([
            'name'      => 'required|string|max:120',
            'email'     => 'required|email|unique:users,email',
            'phone'     => 'nullable|string|max:32',
            'password'  => 'required|string|min:6',
            'role'      => ['required', Rule::in($allowedRoles)],
            'subscription_plan' => 'nullable|string|max:120',
            'subscription_starts_at' => 'nullable|date',
            'subscription_expires_at' => 'nullable|date|after_or_equal:subscription_starts_at',
            'is_active' => 'boolean',
        ]);

        $data['password']  = Hash::make($data['password']);
        $data['parent_id'] = $u->isSuperMaster() ? null : ($request->input('parent_id') ?: $u->id);

        return User::create($data);
    }

    public function show(Request $request, User $user) { return $user; }

    public function update(Request $request, User $user)
    {
        $u = $request->user();
        if (($u->isSubMaster() || $u->isAgent()) && ! in_array($user->id, $u->teamUserIds(), true)) abort(403);

        $data = $request->validate([
            'name'      => 'sometimes|string|max:120',
            'email'     => 'sometimes|email|unique:users,email,' . $user->id,
            'phone'     => 'nullable|string|max:32',
            'password'  => 'nullable|string|min:6',
            'role'      => ['sometimes', Rule::in(['master', 'sub_master', 'agent', 'sub_agent'])],
            'subscription_plan' => 'nullable|string|max:120',
            'subscription_starts_at' => 'nullable|date',
            'subscription_expires_at' => 'nullable|date|after_or_equal:subscription_starts_at',
            'is_active' => 'boolean',
        ]);
        if (! empty($data['password'])) $data['password'] = Hash::make($data['password']);
        else unset($data['password']);

        if ($u->isSubMaster() && isset($data['role']) && $data['role'] !== 'agent') {
            abort(403, 'Sub master can only manage agents.');
        }
        if ($u->isAgent() && isset($data['role']) && $data['role'] !== 'sub_agent') {
            abort(403, 'Agent can only manage sub-agents.');
        }

        $user->update($data);
        return $user;
    }

    public function destroy(Request $request, User $user)
    {
        $u = $request->user();
        if (($u->isSubMaster() || $u->isAgent()) && ! in_array($user->id, $u->teamUserIds(), true)) abort(403);
        if ($user->id === $u->id) abort(422, 'Cannot delete yourself.');
        $user->delete();
        return response()->noContent();
    }
}
