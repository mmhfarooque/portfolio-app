<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LogController extends Controller
{
    /**
     * Display the activity logs.
     */
    public function index(Request $request): Response
    {
        $query = ActivityLog::with('user')->latest();

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by level
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        // Filter by date range
        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->to . ' 23:59:59');
        }

        // Search in message
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', '%' . $search . '%')
                  ->orWhere('action', 'like', '%' . $search . '%')
                  ->orWhere('exception_message', 'like', '%' . $search . '%');
            });
        }

        $logs = $query->paginate(50)->through(fn($log) => [
            'id' => $log->id,
            'type' => $log->type,
            'level' => $log->level,
            'action' => $log->action,
            'message' => $log->message,
            'user' => $log->user ? ['name' => $log->user->name] : null,
            'ip_address' => $log->ip_address,
            'created_at' => $log->created_at->format('M j, Y g:i A'),
            'created_at_human' => $log->created_at->diffForHumans(),
        ]);

        // Get stats
        $stats = [
            'total' => ActivityLog::count(),
            'errors' => ActivityLog::where('type', 'error')->count(),
            'warnings' => ActivityLog::where('type', 'warning')->count(),
            'today' => ActivityLog::whereDate('created_at', today())->count(),
        ];

        // Get available types and levels for filters
        $types = ActivityLog::distinct()->pluck('type')->toArray();
        $levels = ActivityLog::distinct()->pluck('level')->toArray();

        return Inertia::render('Admin/Logs/Index', [
            'logs' => $logs,
            'stats' => $stats,
            'types' => $types,
            'levels' => $levels,
            'filters' => [
                'type' => $request->type,
                'level' => $request->level,
                'search' => $request->search,
                'from' => $request->from,
                'to' => $request->to,
            ],
        ]);
    }

    /**
     * Show a specific log entry.
     */
    public function show(ActivityLog $log): Response
    {
        $log->load('user', 'loggable');

        return Inertia::render('Admin/Logs/Show', [
            'log' => [
                'id' => $log->id,
                'type' => $log->type,
                'level' => $log->level,
                'action' => $log->action,
                'message' => $log->message,
                'context' => $log->context,
                'user' => $log->user ? ['name' => $log->user->name] : null,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'url' => $log->url,
                'method' => $log->method,
                'duration_ms' => $log->duration_ms,
                'memory_usage' => $log->memory_usage ? number_format($log->memory_usage / 1024 / 1024, 2) . ' MB' : null,
                'exception_class' => $log->exception_class,
                'exception_message' => $log->exception_message,
                'exception_trace' => $log->exception_trace,
                'file' => $log->file,
                'line' => $log->line,
                'created_at' => $log->created_at->format('M j, Y g:i A'),
                'created_at_human' => $log->created_at->diffForHumans(),
            ],
        ]);
    }

    /**
     * Clear old logs.
     */
    public function clear(Request $request)
    {
        $days = $request->input('days', 30);
        $count = ActivityLog::cleanup($days);

        return redirect()
            ->route('admin.logs.index')
            ->with('success', "Cleared {$count} log entries older than {$days} days.");
    }

    /**
     * Delete a specific log entry.
     */
    public function destroy(ActivityLog $log)
    {
        $log->delete();

        return redirect()
            ->route('admin.logs.index')
            ->with('success', 'Log entry deleted.');
    }

    /**
     * Get log details via AJAX.
     */
    public function details(ActivityLog $log)
    {
        $log->load('user');

        return response()->json([
            'id' => $log->id,
            'type' => $log->type,
            'level' => $log->level,
            'action' => $log->action,
            'message' => $log->message,
            'context' => $log->context,
            'user' => $log->user ? $log->user->name : 'System',
            'ip_address' => $log->ip_address,
            'user_agent' => $log->user_agent,
            'url' => $log->url,
            'method' => $log->method,
            'duration_ms' => $log->duration_ms,
            'memory_usage' => $log->memory_usage ? number_format($log->memory_usage / 1024 / 1024, 2) . ' MB' : null,
            'exception_class' => $log->exception_class,
            'exception_message' => $log->exception_message,
            'exception_trace' => $log->exception_trace,
            'file' => $log->file,
            'line' => $log->line,
            'created_at' => $log->created_at->format('Y-m-d H:i:s'),
            'created_at_human' => $log->created_at->diffForHumans(),
        ]);
    }
}
