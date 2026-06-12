<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeaveController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $leaves = LeaveRequest::where('user_id', $request->user()->id)
            ->with('reviewer:id,name')
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json(['leaves' => $leaves]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'type'       => ['required', 'string', 'in:annual,sick,unpaid,other'],
            'reason'     => ['required', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $leave = LeaveRequest::create([
            'user_id'    => $request->user()->id,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'type'       => $request->type,
            'reason'     => $request->reason,
            'status'     => 'pending',
        ]);

        return response()->json([
            'message' => 'Leave request submitted successfully.',
            'leave' => $leave,
        ], 201);
    }

    public function show(Request $request, LeaveRequest $leave): JsonResponse
    {
        $user = $request->user();

        if ($leave->user_id !== $user->id && ! $user->isAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $leave->load(['user:id,name,email', 'reviewer:id,name']);

        return response()->json(['leave' => $leave]);
    }

    public function destroy(Request $request, LeaveRequest $leave): JsonResponse
    {
        $user = $request->user();

        if ($leave->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($leave->status !== 'pending') {
            return response()->json(['message' => 'Only pending leave requests can be cancelled.'], 422);
        }

        $leave->delete();

        return response()->json(['message' => 'Leave request cancelled.']);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $query = LeaveRequest::with(['user:id,name,email,position', 'reviewer:id,name']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        return response()->json(['leaves' => $query->orderByDesc('created_at')->paginate(20)]);
    }

    public function review(Request $request, LeaveRequest $leave): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status'       => ['required', 'string', 'in:approved,rejected'],
            'admin_remark' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($leave->status !== 'pending') {
            return response()->json(['message' => 'This leave request has already been reviewed.'], 422);
        }

        $leave->update([
            'status'       => $request->status,
            'admin_remark' => $request->admin_remark,
            'reviewed_by'  => $request->user()->id,
            'reviewed_at'  => now(),
        ]);

        return response()->json([
            'message' => 'Leave request '.$request->status.' successfully.',
            'leave' => $leave->fresh(['user:id,name,email', 'reviewer:id,name']),
        ]);
    }
}
