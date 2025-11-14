<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AgentController extends Controller
{
    public function index()
    {
        $agents = Agent::all()->map(function ($agent) {
            return [
                'id' => $agent->id,
                'name' => $agent->name,
                'join_date' => $agent->join_date instanceof Carbon
                    ? $agent->join_date->format('Y-m-d')
                    : Carbon::parse($agent->join_date)->format('Y-m-d'),
                'address' => $agent->address,
                'trn' => $agent->trn,
                'username' => $agent->username,
                'email' => $agent->email,
                'photo' => $agent->photo ? Storage::url($agent->photo) : null,
            ];
        });

        return Inertia::render('Agent/Index', [
            'agents' => $agents,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'join_date' => 'required|date',
            'address' => 'required|string|max:255',
            'trn' => 'required|string|max:100|unique:agents,trn',
            'username' => 'required|string|max:100|unique:agents,username',
            'email' => 'required|email|max:255|unique:agents,email',
            'photo' => 'nullable|image|max:2048',
        ]);

        $agent = new Agent();
        $agent->fill($validated);

        if ($request->hasFile('photo')) {
            $agent->photo = $request->file('photo')->store('photos', 'public');
        }

        $agent->save();

        return redirect()->route('agents.index')->with('success', 'Agent created successfully.');
    }

    public function show(Agent $agent)
    {
        return response()->json([
            'id' => $agent->id,
            'name' => $agent->name,
            'join_date' => $agent->join_date->format('Y-m-d'),
            'address' => $agent->address,
            'trn' => $agent->trn,
            'username' => $agent->username,
            'email' => $agent->email,
            'photo' => $agent->photo ? Storage::url($agent->photo) : null,
        ]);
    }

    public function update(Request $request, Agent $agent)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'join_date' => 'required|date',
            'address' => 'required|string|max:255',
            'trn' => 'required|string|max:100|unique:agents,trn,' . $agent->id,
            'username' => 'required|string|max:100|unique:agents,username,' . $agent->id,
            'email' => 'required|email|max:255|unique:agents,email,' . $agent->id,
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if ($agent->photo) {
                Storage::disk('public')->delete($agent->photo);
            }
            $validated['photo'] = $request->file('photo')->store('photos', 'public');
        }

        $agent->update($validated);

        return redirect()->route('agents.index')->with('success', 'Agent updated successfully.');
    }

    public function destroy(Agent $agent)
    {
        if ($agent->photo) {
            Storage::disk('public')->delete($agent->photo);
        }
        $agent->delete();

        return redirect()->route('agents.index')->with('success', 'Agent deleted successfully.');
    }
}
