<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TicketReportController extends Controller
{
    /**
     * List the authenticated user's own tickets.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 15);

        $tickets = TicketReport::where('user_id', $request->user()->id)
            ->latest()
            ->paginate($perPage);

        return response()->json($tickets);
    }

    /**
     * Create a new ticket report.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'images'      => ['nullable', 'array', 'max:5'],
            'images.*'    => ['image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $imagePaths = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imagePaths[] = $image->store('ticket-reports', 'public');
            }
        }

        $ticket = TicketReport::create([
            'user_id'     => $request->user()->id,
            'title'       => $validated['title'],
            'description' => $validated['description'],
            'images'      => !empty($imagePaths) ? $imagePaths : null,
            'status'      => 'OPEN',
        ]);

        return response()->json([
            'success' => true,
            'data'    => $ticket,
        ], 201);
    }

    /**
     * Show a single ticket — only if it belongs to the authenticated user.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $ticket = TicketReport::with(['replies.user:id,name,email,role'])
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => $ticket,
        ]);
    }

    /**
     * Reply to a ticket.
     */
    public function reply(Request $request, string $id): JsonResponse
    {
        $ticket = TicketReport::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);

        $reply = $ticket->replies()->create([
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
        ]);

        return response()->json([
            'success' => true,
            'data'    => $reply->load('user:id,name,email,role'),
        ], 201);
    }
}
