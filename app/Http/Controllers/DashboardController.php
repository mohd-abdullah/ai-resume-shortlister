<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Resume;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with key metrics.
     */
    public function index(): View
    {
        $totalResumes = Resume::count();
        $activeJobs = Job::count();
        $resumesToday = Resume::whereDate('created_at', Carbon::today())->count();
        // Assuming a resume is "shortlisted" if it has an associated score record.
        $shortlistedResumes = Resume::whereHas('score')->count();

        return view('dashboard', compact(
            'totalResumes',
            'activeJobs',
            'resumesToday',
            'shortlistedResumes'
        ));
    }
}