<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketReport;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminTicketReportController extends Controller
{
    /** Valid status transitions */
    private const TRANSITIONS = [
        'OPEN'        => 'IN_PROGRESS',
        'IN_PROGRESS' => 'SOLVED',
    ];

    /**
     * List all tickets with optional status filter and search.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 15);
        $status  = $request->input('status');   // null when absent → ->when() skips correctly
        $search  = $request->input('search');

        $tickets = TicketReport::with('user:id,name,email')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search, fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage);

        return response()->json($tickets);
    }

    /**
     * Show a single ticket detail.
     */
    public function show(TicketReport $ticketReport): JsonResponse
    {
        $ticketReport->load(['user:id,name,email', 'replies.user:id,name,email,role']);

        return response()->json([
            'success' => true,
            'data'    => $ticketReport,
        ]);
    }

    /**
     * Update ticket status (validated transition only).
     *
     * OPEN → IN_PROGRESS → SOLVED
     */
    public function updateStatus(Request $request, TicketReport $ticketReport): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['IN_PROGRESS', 'SOLVED'])],
        ]);

        $current  = $ticketReport->status;
        $next     = $validated['status'];
        $allowed  = self::TRANSITIONS[$current] ?? null;

        if ($allowed !== $next) {
            return response()->json([
                'success' => false,
                'message' => "Status tidak dapat diubah dari {$current} ke {$next}.",
            ], 422);
        }

        $ticketReport->update(['status' => $next]);

        AuditLogger::log(
            'TicketReport',
            'update_status',
            "Status ticket #{$ticketReport->id} diubah dari {$current} ke {$next}",
            $request->user(),
            $ticketReport
        );

        return response()->json([
            'success' => true,
            'data'    => $ticketReport->fresh(),
        ]);
    }

    /**
     * Admin reply to a ticket.
     */
    public function reply(Request $request, TicketReport $ticketReport): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);

        $reply = $ticketReport->replies()->create([
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
        ]);

        return response()->json([
            'success' => true,
            'data'    => $reply->load('user:id,name,email,role'),
        ], 201);
    }
}
