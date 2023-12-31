<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\Projects;

class ReportsController extends Controller
{
    public function PageReports(Request $request)
    {
        $user = Auth::user();
        $projectsQuery = Projects::with('tasks')
            ->select('id', 'name', 'team_members', 'start_date', 'end_date', 'status', 'validation', 'note');

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
        
        // Setting ascending pada reportsQuery
        $reportsQuery = $projectsQuery->orderBy('name', 'asc')->paginate(10);

        $totalData = $projectsQuery->count();

        $reports = [];

        foreach ($reportsQuery as $project) {
            $completedTasks = $project->tasks->where('status', 'done')->count();
            $totalTasks = $project->tasks->count();
            $progress = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;

            $start_date = new \DateTime($project->start_date);
            $end_date = new \DateTime($project->end_date);
            $end_date->setTime(23, 59, 0);
            $work_duration = $start_date->diff($end_date)->days;

            $now = now();
            if ($now > $end_date && ($completedTasks < $totalTasks || $totalTasks === 0)) {
                $status = 'over due';
            } elseif (($project->status === 'pending' || $project->status === 'to do') && $totalTasks === 0) {
                $status = 'pending';
            } elseif ($project->status === 'to do' && $completedTasks === 0) {
                $status = 'started';
            } elseif ($project->status === 'doing' && ($completedTasks === 0 || $completedTasks !== 0)) {
                $status = 'on-progress';
            } elseif ($project->status === 'submission' && $completedTasks !== 0) {
                $status = 'submission';
            } else {
                $status = 'done';
            }

            $reports[] = [
                'project_name' => $project->name,
                'due' => $project->end_date,
                'total_tasks' => $totalTasks,
                'completed_task' => $completedTasks,
                'work_duration' => $work_duration,
                'progress' => $progress,
                'status' => $status,
                'validation' => $project->validation,
                'note'=> $project->note,
            ];

        }

        if ($request->ajax()) {
            return response()->json(['reports' => ['data' => $reports, 'total' => $totalData]]);
        }

        return Inertia::render('Reports/PageReports', [
            'reports' => ['data' => $reports, 'total' => $totalData],
            'auth' => $user 
        ]);
    }
}
