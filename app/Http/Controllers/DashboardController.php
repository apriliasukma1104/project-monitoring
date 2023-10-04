<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Eloquent\SoContractRepo;
use App\Models\Projects;

class DashboardController extends Controller
{
    private $SoContractRepo;
    public function __construct(SoContractRepo $SoContractRepo)
    {
        $this->middleware("auth");
        $this->SoContractRepo = $SoContractRepo;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $projectsQuery = Projects::with('tasks')
            ->select('id', 'name', 'team_members', 'start_date', 'end_date', 'status');

        // Setting Sortable
        $sortField = $request->input('sortField', 'name'); 
        $sortOrder = $request->input('sortOrder', 'asc'); 

        if ($user->role === 'User') {
            $projectsQuery->where(function ($query) use ($user) {
                $query->where('team_leader', $user->id)
                    ->orWhereRaw("JSON_SEARCH(team_members, 'one', ?) IS NOT NULL", [$user->id]);
            });
        } 
        
        $search = $request->input('search');
        if ($search) {
            $projectsQuery->where('name', 'like', '%' . $search . '%');
        }

        $totalTaskProjects = $projectsQuery->with('tasks')->get()->pluck('tasks')->flatten()->count();

        
        $projectsQuery->orderBy($sortField, $sortOrder);
        
        $dashboard = $projectsQuery->paginate(5);
        $totalProject = $projectsQuery->count();

        $formattedDashboard = [];

        foreach ($dashboard as $project) {
            $completedTasks = $project->tasks->where('status', 'done')->count();
            $totalTasks = $project->tasks->count();
            $progress = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;

            $end_date = new \DateTime($project->end_date);
            $end_date->setTime(23, 59, 0);

            $now = now();
            if ($now > $end_date && ($completedTasks < $totalTasks || $totalTasks === 0)) {
                $status = 'over due';
            } elseif ($totalTasks === 0) {
                $status = 'pending';
            } elseif ($completedTasks === 0) {
                $status = 'started';
            } elseif ($completedTasks < $totalTasks && $progress >= 25) {
                $status = 'on-progress';
            } elseif ($project->status === 'review') {
                $status = 'review';
            } else {
                $status = 'done';
            }

            $formattedDashboard[] = [
                'project_name' => $project->name,
                'progress' => $progress,
                'status' => $status,
            ];
        }

        if ($request->ajax()) {
            return response()->json(['dashboard' => ['data' => $formattedDashboard, 'total' => $totalProject]]);
        }

        return Inertia::render('Home/Index', [
            'dashboard' => ['data' => $formattedDashboard, 'total' => $totalProject], 
            'total_tasks' => $totalTaskProjects,
            'total_projects' => $totalProject,
            'auth' => $user 
        ]);
    }

}