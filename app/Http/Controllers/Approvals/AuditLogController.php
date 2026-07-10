<?php

namespace App\Http\Controllers\Approvals;

use App\Http\Controllers\Controller;
use App\Services\TyreReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly TyreReportService $reportService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('audit.view'), 403);

        $logs = $this->reportService->auditTrail(
            $request->query('date_from'),
            $request->query('date_to'),
        )
            ->when($request->query('log_name'), fn ($q, $name) => $q->where('log_name', $name))
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($activity) => [
                'id' => $activity->id,
                'created_at' => $activity->created_at?->toDateTimeString(),
                'log_name' => $activity->log_name,
                'event' => $activity->event,
                'description' => $activity->description,
                'subject' => $activity->subject_type
                    ? class_basename($activity->subject_type).' #'.$activity->subject_id
                    : null,
                'causer' => $activity->causer?->name ?? 'System',
                'properties' => $activity->properties?->toArray() ?? [],
            ]);

        $logNames = \Spatie\Activitylog\Models\Activity::query()
            ->select('log_name')
            ->distinct()
            ->whereNotNull('log_name')
            ->orderBy('log_name')
            ->pluck('log_name');

        return Inertia::render('approvals/audit-logs', [
            'logs' => $logs,
            'logNames' => $logNames,
            'filters' => [
                'date_from' => $request->query('date_from'),
                'date_to' => $request->query('date_to'),
                'log_name' => $request->query('log_name'),
            ],
        ]);
    }
}
