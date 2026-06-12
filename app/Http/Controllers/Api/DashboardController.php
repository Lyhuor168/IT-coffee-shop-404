<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $today = Attendance::where('user_id', $user->id)->whereDate('date', now()->toDateString())->first();
        $attendances = Attendance::where('user_id', $user->id)->orderBy('date', 'desc')->limit(20)->get();
        $leaves = LeaveRequest::where('user_id', $user->id)->orderBy('start_date', 'desc')->limit(20)->get();

        return response()->json([
            'today' => $today,
            'attendances' => $attendances,
            'leaves' => $leaves,
        ]);
    }
}
