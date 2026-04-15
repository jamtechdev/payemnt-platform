<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $logs = AuditLog::query()
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')->toString()))
            ->when($request->filled('model_type'), fn ($q) => $q->where('model_type', $request->string('model_type')->toString()))
            ->whereBetween('created_at', [
                $request->input('date_from', now()->subDays(30)->toDateString()),
                $request->input('date_to', now()->toDateString()),
            ])
            ->latest('created_at')
            ->paginate(50);

        return Inertia::render('Admin/SuperAdmin/AuditLog', ['logs' => $logs]);
    }

    public function show(AuditLog $auditLog): Response
    {
        $old = $auditLog->old_values ?? [];
        $new = $auditLog->new_values ?? [];
        $diff = array_diff_assoc($new, $old);

        return Inertia::render('Admin/SuperAdmin/AuditLogDetail', ['log' => $auditLog, 'diff' => $diff]);
    }
}
