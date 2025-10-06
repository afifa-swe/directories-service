<?php

namespace App\Http\Controllers;

use App\Models\TreasuryAccount;
use Illuminate\Http\Request;
use App\Http\Requests\StoreTreasuryAccountRequest;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\TreasuryAccountImport;
use Illuminate\Support\Facades\Log;

class TreasuryAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $allowedSorts = ['account', 'name', 'department', 'currency', 'created_at'];
        $sort = $request->get('sort', 'created_at');
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }

        $direction = strtolower($request->get('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        // Optional: wait for background queue to finish importing before returning results.
        // Usage (optional query params):
        // - wait_seconds=7 : simple sleep before executing the query
        // - wait_retries=3&wait_interval=2 : retry query up to 3 times, waiting 2 seconds between attempts until results appear

        $waitSeconds = (int) $request->get('wait_seconds', 0);
        $waitRetries = (int) $request->get('wait_retries', 0);
        $waitInterval = max(1, (int) $request->get('wait_interval', 2));

        if ($waitSeconds > 0) {
            sleep($waitSeconds);
        }

        $query = TreasuryAccount::query()
            ->when(auth()->check(), fn($q) => $q->where('created_by', auth()->id()))
            ->when($request->search, function ($q) use ($request) {
                $term = "%{$request->search}%";
                $q->where(function ($sub) use ($term) {
                    $sub->where('account', 'ilike', $term)
                        ->orWhere('name', 'ilike', $term)
                        ->orWhere('department', 'ilike', $term)
                        ->orWhere('currency', 'ilike', $term);
                });
            })
            ->when($request->filled('currency'), fn($q) => $q->where('currency', $request->currency))
            ->when($request->filled('department'), fn($q) => $q->where('department', $request->department))
            ->when($request->filled('account'), fn($q) => $q->where('account', $request->account))
            ->orderBy($sort, $direction);

        if ($waitRetries > 0) {
            $attempt = 0;
            do {
                $items = $query->paginate($request->get('per_page', 20));
                if ($items->total() > 0) {
                    break;
                }
                $attempt++;
                if ($attempt >= $waitRetries) {
                    break;
                }
                sleep($waitInterval);
            } while (true);
        } else {
            $items = $query->paginate($request->get('per_page', 20));
        }

        return response()->json([
            'message' => 'Успешно',
            'data' => $items,
            'timestamp' => now()->toIso8601String(),
            'success' => true,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTreasuryAccountRequest $request)
    {
        $data = $request->validated();
        if (auth()->check()) {
            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();
        }
        $model = TreasuryAccount::create($data);

        return response()->json([
            'message' => 'Успешно',
            'data' => $model,
            'timestamp' => now()->toIso8601String(),
            'success' => true,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(TreasuryAccount $treasuryAccount)
    {
        if (auth()->check() && $treasuryAccount->created_by !== auth()->id()) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }
        return response()->json([
            'message' => 'Успешно',
            'data' => $treasuryAccount,
            'timestamp' => now()->toIso8601String(),
            'success' => true,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreTreasuryAccountRequest $request, TreasuryAccount $treasuryAccount)
    {
        if (auth()->check() && $treasuryAccount->created_by !== auth()->id()) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        $data = $request->validated();
        if (auth()->check()) {
            $data['updated_by'] = auth()->id();
        }

        $treasuryAccount->update($data);

        return response()->json([
            'message' => 'Успешно',
            'data' => $treasuryAccount,
            'timestamp' => now()->toIso8601String(),
            'success' => true,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TreasuryAccount $treasuryAccount)
    {
        if (auth()->check() && $treasuryAccount->created_by !== auth()->id()) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        $treasuryAccount->delete();

        return response()->json([
            'message' => 'Успешно',
            'data' => [],
            'timestamp' => now()->toIso8601String(),
            'success' => true,
        ]);
    }

    /**
     * Import treasury accounts from an Excel file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        try {
            $userId = auth()->check() ? auth()->id() : null;
            Excel::queueImport(new TreasuryAccountImport($userId), $request->file('file'));

            return response()->json([
                'message' => 'Импорт запущен',
                'data' => [],
                'timestamp' => now()->toIso8601String(),
                'success' => true,
            ]);
        } catch (\Throwable $e) {
            Log::error('TreasuryAccount import failed to queue', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Ошибка при запуске импорта',
                'data' => [],
                'timestamp' => now()->toIso8601String(),
                'success' => false,
            ], 500);
        }
    }
}
