<?php

// app/Http/Controllers/JobController.php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobController extends Controller
{
    public function index(): View
    {
        return view('jobs.index', ['jobs' => Job::latest()->paginate(10)]);
    }

    public function create(): View
    {
        return view('jobs.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        Auth::user()->jobs()->create($validated);

        return redirect()->route('jobs.index');
    }

    public function show(Job $job): View
    {
        // Eager load resumes + scores to avoid N+1 queries
        $job->load(['resumes.score']);

        return view('jobs.show', compact('job'));
    }
}