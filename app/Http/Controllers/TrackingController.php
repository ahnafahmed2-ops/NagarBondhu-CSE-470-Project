<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;

/** Feature F4 – anyone can follow a report by its tracking code. No personal data is shown. */
class TrackingController extends Controller
{
    public function index(Request $request)
    {
        $code = strtoupper(trim((string) $request->query('code', '')));
        $report = null;

        if ($code !== '') {
            $report = Report::with('category', 'histories')->where('tracking_code', $code)->first();
        }

        return view('track.index', ['code' => $code, 'report' => $report]);
    }
}
