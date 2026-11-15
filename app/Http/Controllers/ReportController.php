<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Models\Category;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

/**
 * Feature F1 – Multi-Category Civic Issue Reporting.
 * Create / list / view / edit / delete of a citizen's own reports counts as ONE feature.
 */
class ReportController extends Controller
{
    public function index(Request $request)
    {
        $categoryId = $request->integer('category') ?: null;

        $reports = $request->user()->reports()
            ->with('category')
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->latest()->latest('id')
            ->get();

        return view('reports.index', [
            'reports'    => $reports,
            'categories' => Category::orderBy('id')->get(),
            'filter'     => $categoryId,
        ]);
    }

    public function create()
    {
        return view('reports.create', ['categories' => Category::orderBy('id')->get()]);
    }

    public function store(ReportRequest $request): RedirectResponse
    {
        $data = Arr::except($request->validated(), ['evidence', 'remove_evidence']);

        if ($request->hasFile('evidence')) {
            $data['evidence_path'] = $request->file('evidence')->store('evidence', 'public');
        }

        $report = $request->user()->reports()->create($data);

        return redirect()->route('reports.show', $report)
            ->with('success', "Your report was submitted. Its tracking code is {$report->tracking_code}.");
    }

    public function show(Request $request, Report $report)
    {
        $this->ownedBy($request, $report);
        $report->load('category');

        return view('reports.show', ['report' => $report]);
    }

    public function edit(Request $request, Report $report)
    {
        $this->ownedBy($request, $report);

        if ($blocked = $this->blockIfLocked($report)) {
            return $blocked;
        }

        return view('reports.edit', [
            'report'     => $report,
            'categories' => Category::orderBy('id')->get(),
        ]);
    }

    public function update(ReportRequest $request, Report $report): RedirectResponse
    {
        $this->ownedBy($request, $report);

        if ($blocked = $this->blockIfLocked($report)) {
            return $blocked;
        }

        $data = Arr::except($request->validated(), ['evidence', 'remove_evidence']);

        if ($request->hasFile('evidence')) {
            $this->deleteEvidence($report);
            $data['evidence_path'] = $request->file('evidence')->store('evidence', 'public');
        } elseif ($request->boolean('remove_evidence')) {
            $this->deleteEvidence($report);
            $data['evidence_path'] = null;
        }

        $report->update($data);

        return redirect()->route('reports.show', $report)->with('success', 'Your report was updated.');
    }

    public function destroy(Request $request, Report $report): RedirectResponse
    {
        $this->ownedBy($request, $report);

        if ($blocked = $this->blockIfLocked($report)) {
            return $blocked;
        }

        $this->deleteEvidence($report);
        $report->delete();

        return redirect()->route('reports.index')->with('success', 'The report was deleted.');
    }

    /** Other citizens' reports behave as "not found" so their existence is never revealed. */
    private function ownedBy(Request $request, Report $report): void
    {
        abort_unless($report->user_id === $request->user()->id, 404);
    }

    private function blockIfLocked(Report $report): ?RedirectResponse
    {
        if ($report->isEditable()) {
            return null;
        }

        return redirect()->route('reports.show', $report)
            ->with('error', 'This report is already being handled and can no longer be changed.');
    }

    private function deleteEvidence(Report $report): void
    {
        if ($report->evidence_path) {
            Storage::disk('public')->delete($report->evidence_path);
        }
    }
}
