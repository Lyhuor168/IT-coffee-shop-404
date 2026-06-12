<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Attendance::where('user_id', $request->user()->id)->orderByDesc('date');

        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('date', $request->month)->whereYear('date', $request->year);
        }

        return response()->json(['attendances' => $query->paginate(31)]);
    }

    public function today(Request $request): JsonResponse
    {
        $attendance = Attendance::where('user_id', $request->user()->id)
            ->whereDate('date', Carbon::today())
            ->first();

        return response()->json(['attendance' => $attendance]);
    }

    public function checkIn(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = Carbon::today();
        $now = Carbon::now();

        $existing = Attendance::where('user_id', $user->id)->whereDate('date', $today)->first();

        if ($existing && $existing->check_in) {
            return response()->json([
                'message' => 'You have already checked in today.',
                'attendance' => $existing,
            ], 409);
        }

        $lateThreshold = Carbon::today()->setTime(9, 0);
        $status = $now->greaterThan($lateThreshold) ? 'late' : 'present';

        $attendance = Attendance::updateOrCreate(
            ['user_id' => $user->id, 'date' => $today->toDateString()],
            ['check_in' => $now, 'status' => $status]
        );

        return response()->json([
            'message' => 'Checked in successfully.',
            'attendance' => $attendance,
        ], 201);
    }

    public function checkOut(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = Carbon::today();
        $now = Carbon::now();

        $attendance = Attendance::where('user_id', $user->id)->whereDate('date', $today)->first();

        if (! $attendance || ! $attendance->check_in) {
            return response()->json(['message' => 'You must check in before checking out.'], 422);
        }

        if ($attendance->check_out) {
            return response()->json([
                'message' => 'You have already checked out today.',
                'attendance' => $attendance,
            ], 409);
        }

        $attendance->update(['check_out' => $now]);

        return response()->json([
            'message' => 'Checked out successfully.',
            'attendance' => $attendance->fresh(),
        ]);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $query = Attendance::with('user:id,name,email,position');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('date', $request->month)->whereYear('date', $request->year);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json(['attendances' => $query->orderByDesc('date')->paginate(20)]);
    }
}
