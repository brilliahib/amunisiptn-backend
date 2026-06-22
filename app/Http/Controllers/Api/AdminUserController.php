<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->per_page ?? 15), 100);
        $users = User::latest()
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"))
            ->paginate($perPage);

        return response()->json($users);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(['data' => $user]);
    }

    public function export(Request $request): StreamedResponse
    {
        $users = User::where('role', 'user')
            ->when($request->search, fn($q, $s) =>
                $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")
            )
            ->latest()
            ->get(['name', 'email', 'phone_number', 'school_origin', 'grade_level', 'created_at']);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Pengguna');

        $headers = ['No', 'Nama', 'Email', 'No. HP', 'Asal Sekolah', 'Kelas', 'Tanggal Daftar'];
        $sheet->fromArray($headers, null, 'A1');

        $headerStyle = [
            'font'    => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF004AAB']],
        ];
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

        foreach ($users as $i => $user) {
            $sheet->fromArray([
                $i + 1,
                $user->name,
                $user->email,
                $user->phone_number ?? '-',
                $user->school_origin ?? '-',
                $user->grade_level ?? '-',
                $user->created_at->format('d/m/Y'),
            ], null, 'A' . ($i + 2));
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'data-pengguna-' . now()->format('Ymd-His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->stream(
            fn() => $writer->save('php://output'),
            200,
            [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            ]
        );
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri.'
            ], 403);
        }

        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Akun admin tidak dapat dihapus.'
            ], 403);
        }

        $user->tokens()->delete();

        $user->delete();

        return response()->json([
            'message' => 'Data pengguna berhasil dihapus oleh Admin.'
        ]);
    }

    public function vipPreview(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->per_page ?? 10), 100);
        $search = $request->search;
        
        $query = User::whereHas('orders', function ($q) use ($request) {
            $q->whereIn('status', ['paid', 'approved']);
            if ($request->filter_type === 'date_range' && $request->start_date && $request->end_date) {
                $startDate = \Carbon\Carbon::parse($request->start_date)->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->end_date)->endOfDay();
                $q->whereBetween('created_at', [$startDate, $endDate]);
            }
        })->withMax(['orders as last_transaction_date' => function($q) {
            $q->whereIn('status', ['paid', 'approved']);
        }], 'created_at')
          ->withSum(['ticketLogs as total_tickets_in' => function($q) {
              $q->where('type', 'credit');
          }], 'amount')
          ->withSum(['ticketLogs as total_tickets_out' => function($q) {
              $q->where('type', 'debit');
          }], 'amount');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate($perPage);

        return response()->json($users);
    }

    public function ticketLogs(Request $request, User $user): JsonResponse
    {
        $perPage = min((int) ($request->per_page ?? 10), 100);

        $logs = $user->ticketLogs()
            ->latest()
            ->paginate($perPage);

        return response()->json($logs);
    }

    public function injectVipTickets(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
            'description' => 'required|string|max:255',
            'filter_type' => 'required|in:all_vip,date_range,single_user',
            'start_date' => 'required_if:filter_type,date_range|date|nullable',
            'end_date' => 'required_if:filter_type,date_range|date|after_or_equal:start_date|nullable',
            'user_id' => 'required_if:filter_type,single_user|exists:users,id',
            'action' => 'nullable|in:inject,pull',
        ]);

        try {
            DB::beginTransaction();

            $query = User::whereHas('orders', function ($q) use ($request) {
                $q->whereIn('status', ['paid', 'approved']);
                if ($request->filter_type === 'date_range') {
                    $startDate = \Carbon\Carbon::parse($request->start_date)->startOfDay();
                    $endDate = \Carbon\Carbon::parse($request->end_date)->endOfDay();
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                }
            });

            if ($request->filter_type === 'single_user') {
                $query->where('id', $request->user_id);
            }

            $users = $query->get();
            $count = 0;
            $action = $request->input('action', 'inject');

            foreach ($users as $user) {
                if ($action === 'pull') {
                    // Hanya tarik maksimal sejumlah saldo yang dimiliki
                    $deduct = min($user->ticket_balance, $request->amount);
                    if ($deduct > 0) {
                        $user->decrement('ticket_balance', $deduct);
                        \App\Models\TicketLog::create([
                            'user_id' => $user->id,
                            'type' => 'debit',
                            'amount' => $deduct,
                            'source' => 'Sistem AmunisiPTN',
                            'description' => $request->description,
                        ]);
                        $count++;
                    }
                } else {
                    $user->increment('ticket_balance', $request->amount);
                    \App\Models\TicketLog::create([
                        'user_id' => $user->id,
                        'type' => 'credit',
                        'amount' => $request->amount,
                        'source' => 'Sistem AmunisiPTN',
                        'description' => $request->description,
                    ]);
                    $count++;
                }
            }

            DB::commit();

            $actionText = $action === 'pull' ? 'menarik tiket dari' : 'menginjeksi tiket ke';
            return response()->json([
                'message' => "Berhasil {$actionText} {$count} pengguna."
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal melakukan injeksi tiket.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}