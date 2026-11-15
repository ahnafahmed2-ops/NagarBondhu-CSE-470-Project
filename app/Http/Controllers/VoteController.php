<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportVote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Feature F5 – add or take back a vote. Every vote immediately recalculates the priority (F7).
 * Rules: not on your own report, not on closed reports, each type once per user,
 * and "still there" / "already fixed" replace each other.
 */
class VoteController extends Controller
{
    public function store(Request $request, Report $report): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(ReportVote::LABELS))],
        ], ['type.in' => 'Choose one of the listed options.']);

        abort_if($report->user_id === $request->user()->id, 403, 'You cannot vote on your own report.');

        if ($report->isClosed()) {
            return back()->with('error', 'This report is closed, so it no longer takes votes.');
        }

        DB::transaction(function () use ($request, $report, $data) {
            $opposite = [ReportVote::STILL_THERE => ReportVote::FIXED, ReportVote::FIXED => ReportVote::STILL_THERE];
            if (isset($opposite[$data['type']])) {
                $report->votes()->where('user_id', $request->user()->id)
                    ->where('type', $opposite[$data['type']])->delete();
            }

            $report->votes()->firstOrCreate(['user_id' => $request->user()->id, 'type' => $data['type']]);
            $report->save(); // recalculates the priority
        });

        return back()->with('success', 'Thanks – your vote was recorded.');
    }

    public function destroy(Request $request, Report $report, string $type): RedirectResponse
    {
        abort_unless(array_key_exists($type, ReportVote::LABELS), 404);

        if ($report->isClosed()) {
            return back()->with('error', 'This report is closed, so its votes can no longer change.');
        }

        $report->votes()->where('user_id', $request->user()->id)->where('type', $type)->delete();
        $report->save();

        return back()->with('success', 'Your vote was removed.');
    }
}
