<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Report;
use App\Models\ReportVote;
use Illuminate\Http\Request;

/**
 * Feature F5 – Community issue verification and voting.
 * Citizens browse other people's open reports in their area and vote on them.
 */
class CommunityController extends Controller
{
    public function index(Request $request)
    {
        $categoryId = $request->integer('category') ?: null;
        $ward = trim((string) $request->query('ward', ''));

        $reports = Report::open()
            ->where('user_id', '!=', $request->user()->id)
            ->with('category')
            ->withCount([
                'votes as affected_count' => fn ($q) => $q->where('type', ReportVote::AFFECTED),
                'votes as still_there_count' => fn ($q) => $q->where('type', ReportVote::STILL_THERE),
            ])
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($ward !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('ward', 'like', "%{$ward}%")->orWhere('address', 'like', "%{$ward}%")))
            ->orderByDesc('priority_score')->latest()
            ->get();

        return view('community.index', [
            'reports'    => $reports,
            'categories' => Category::orderBy('id')->get(),
            'filter'     => $categoryId,
            'ward'       => $ward,
        ]);
    }

    /** Public view of someone else's report – the reporter's name is never shown. */
    public function show(Request $request, Report $report)
    {
        if ($report->user_id === $request->user()->id) {
            return redirect()->route('reports.show', $report);
        }

        $report->load('category', 'votes');

        return view('community.show', [
            'report'  => $report,
            'myVotes' => $report->votes->where('user_id', $request->user()->id)->pluck('type')->all(),
        ]);
    }
}
