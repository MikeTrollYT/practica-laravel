<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::with('owner')
            ->where('user_id', Auth::id())
            ->orWhereHas('participants', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->latest()
            ->paginate(10);

        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        return view('projects.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'due_date' => 'required|date|after_or_equal:today',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
        ]);

        $project = Auth::user()->ownedProjects()->create($validated);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Proyecto creado exitosamente.');
    }

    public function show(Project $project)
    {
        $this->authorize('view', $project);

        $project->load(['owner', 'participants']);
        $availableUsers = User::whereNotIn('id', $project->participants->pluck('id'))
            ->where('id', '!=', $project->user_id)
            ->get();

        return view('projects.show', compact('project', 'availableUsers'));
    }

    public function edit(Project $project)
    {
        $this->authorize('update', $project);

        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'due_date' => 'required|date|after_or_equal:today',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
        ]);

        $project->update($validated);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Proyecto actualizado exitosamente.');
    }

    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Proyecto eliminado exitosamente.');
    }

    public function addParticipant(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id|unique:project_user,user_id,NULL,id,project_id,'.$project->id,
            'role' => 'required|string|max:255',
            'contribution_hours' => 'required|integer|min:0',
        ]);

        $project->participants()->attach($validated['user_id'], [
            'role' => $validated['role'],
            'contribution_hours' => $validated['contribution_hours'],
        ]);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Participante añadido exitosamente.');
    }

    public function removeParticipant(Project $project, User $user)
    {
        $this->authorize('update', $project);

        $project->participants()->detach($user->id);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Participante eliminado exitosamente.');
    }

    public function updateParticipantHours(Request $request, Project $project, User $user)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'contribution_hours' => 'required|integer|min:0',
        ]);

        $project->participants()->updateExistingPivot($user->id, [
            'contribution_hours' => $validated['contribution_hours'],
        ]);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Horas actualizadas exitosamente.');
    }
}
