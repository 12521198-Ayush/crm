<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeadSource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LeadSourceController extends Controller
{
    public function index() { return LeadSource::orderBy('name')->get(); }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:120',
            'channel'   => 'nullable|string|max:50',
            'config'    => 'nullable|array',
            'is_active' => 'boolean',
        ]);
        $data['slug'] = Str::slug($data['name']);
        return LeadSource::create($data);
    }

    public function update(Request $request, LeadSource $source)
    {
        $data = $request->validate([
            'name'      => 'sometimes|string|max:120',
            'channel'   => 'nullable|string|max:50',
            'config'    => 'nullable|array',
            'is_active' => 'boolean',
        ]);
        if (isset($data['name'])) $data['slug'] = Str::slug($data['name']);
        $source->update($data);
        return $source;
    }

    public function destroy(LeadSource $source)
    {
        $source->delete();
        return response()->noContent();
    }
}
