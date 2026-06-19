<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        return Project::with('owner:id,name')->orderByDesc('id')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:191',
            'code'        => 'nullable|string|max:50|unique:projects,code',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);
        $data['owner_id'] = $request->user()->id;
        return Project::create($data);
    }

    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'name'        => 'sometimes|string|max:191',
            'code'        => 'nullable|string|max:50|unique:projects,code,' . $project->id,
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);
        $project->update($data);
        return $project;
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return response()->noContent();
    }
}
