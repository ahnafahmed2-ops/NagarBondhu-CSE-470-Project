<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Report;
use App\Models\ReportVote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin side of F4 (move reports through the status workflow) and F7 (override the priority).
 */
class ReportController extends Controller
{
    public function index(Request $request)
    {
        $this->adminOnly($request);

        $status = $request->query('status');
        $status = array_key_exists((string) $status, Report::TRANSITIONS) ? $status : null;
        $priority = $request->query('priority');
        $priority = array_key_exists((string) $priority, Report::PRIORITY_LEVELS) ? $priority : null;

        $reports = Report::with('category')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($priority, fn ($q) => $q->where('priority_level', $priority))
            ->orderByDesc('priority_score')->latest()
            ->get();

        return view('admin.reports.index', [
            'reports'  => $reports,
            'statuses' => array_keys(Report::TRANSITIONS),
            'levels'   => array_keys(Report::PRIORITY_LEVELS),
            'status'   => $status,
            'priority' => $priority,
        ]);
    }

    public function show(Request $request, Report $report)
    {
        $this->adminOnly($request);
        $report->load('category', 'user', 'votes', 'histories.user');

        return view('admin.reports.show', [
            'report' => $report,
            'levels' => array_keys(Report::PRIORITY_LEVELS),
            'labels' => ReportVote::LABELS,
        ]);
    }

    public function updateStatus(Request $request, Report $report): RedirectResponse
    {
        $this->adminOnly($request);

        $data = $request->validate([
            'status' => ['required', 'string'],
            'note'   => ['nullable', 'string', 'max:500'],
        ]);

        if (! $report->canMoveTo($data['status'])) {
            return back()->with('error', "A report cannot move from {$report->status} to {$data['status']}.");
        }

        $report->changeStatus($data['status'], $request->user(), $data['note'] ?? null);

        return back()->with('success', "Status changed to {$report->status}.");
    }

    public function updatePriority(Request $request, Report $report): RedirectResponse
    {
        $this->adminOnly($request);

        $data = $request->validate([
            'priority_level' => ['required', Rule::in(array_keys(Report::PRIORITY_LEVELS))],
            'reason'         => ['required', 'string', 'min:5', 'max:300'],
        ], ['reason.required' => 'Give a reason for changing the priority.']);

        $report->overridePriority($data['priority_level'], $request->user(), $data['reason']);

        return back()->with('success', "Priority set to {$report->priority_level}.");
    }

    public function resetPriority(Request $request, Report $report): RedirectResponse
    {
        $this->adminOnly($request);
        $report->clearPriorityOverride($request->user());

        return back()->with('success', "Priority is automatic again ({$report->priority_level}).");
    }

    private function adminOnly(Request $request): void
    {
        abort_unless($request->user()->isAdmin(), 403);
    }
}
