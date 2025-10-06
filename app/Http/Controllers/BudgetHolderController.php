<?php

namespace App\Http\Controllers;

use App\Models\BudgetHolder;
use Illuminate\Http\Request;
use App\Http\Requests\StoreBudgetHolderRequest;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\BudgetHolderImport;
use Illuminate\Support\Facades\Log;

class BudgetHolderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $allowedSorts = ['tin', 'name', 'region', 'district', 'created_at'];
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

        $query = BudgetHolder::query()
            ->when(auth()->check(), fn($q) => $q->where('created_by', auth()->id()))
            ->when($request->search, function ($q) use ($request) {
                $term = "%{$request->search}%";
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'ilike', $term)
                        ->orWhere('address', 'ilike', $term)
                        ->orWhere('region', 'ilike', $term)
                        ->orWhere('district', 'ilike', $term)
                        ->orWhere('tin', 'ilike', $term);
                });
            })
            ->when($request->filled('region'), fn($q) => $q->where('region', $request->region))
            ->when($request->filled('district'), fn($q) => $q->where('district', $request->district))
            ->when($request->filled('tin'), fn($q) => $q->where('tin', $request->tin))
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
    public function store(StoreBudgetHolderRequest $request)
    {
        $data = $request->validated();
        if (auth()->check()) {
            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();
        }
        $model = BudgetHolder::create($data);

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
    public function show(BudgetHolder $budgetHolder)
    {
        if (auth()->check() && $budgetHolder->created_by !== auth()->id()) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }
        return response()->json([
            'message' => 'Успешно',
            'data' => $budgetHolder,
            'timestamp' => now()->toIso8601String(),
            'success' => true,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreBudgetHolderRequest $request, BudgetHolder $budgetHolder)
    {
        if (auth()->check() && $budgetHolder->created_by !== auth()->id()) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        $data = $request->validated();
        if (auth()->check()) {
            $data['updated_by'] = auth()->id();
        }

        $budgetHolder->update($data);

        return response()->json([
            'message' => 'Успешно',
            'data' => $budgetHolder,
            'timestamp' => now()->toIso8601String(),
            'success' => true,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BudgetHolder $budgetHolder)
    {
        if (auth()->check() && $budgetHolder->created_by !== auth()->id()) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        $budgetHolder->delete();

        return response()->json([
            'message' => 'Успешно',
            'data' => [],
            'timestamp' => now()->toIso8601String(),
            'success' => true,
        ]);
    }

    /**
     * Import budget holders from an Excel file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('file');

        $userId = auth()->check() ? auth()->id() : null;

        $path = $file->getRealPath();
        if (!is_readable($path)) {
            Log::error('BudgetHolder import file not readable', ['path' => $path]);
            return response()->json(['message' => 'Файл недоступен для чтения', 'success' => false], 400);
        }

        $rows = array_map('str_getcsv', file($path));
        if (count($rows) <= 1) {
            return response()->json(['message' => 'Файл не содержит данных', 'success' => false], 400);
        }

        $header = array_map(fn($h) => mb_strtolower(trim($h)), $rows[0]);
        $dataRows = array_slice($rows, 1);

        foreach ($dataRows as $row) {
            // normalize row length to header
            $row = array_map(fn($c) => is_string($c) ? trim($c) : $c, $row);
            $row = array_pad($row, count($header), null);
            $assoc = array_combine($header, $row);
            if ($assoc === false) {
                Log::error('BudgetHolder import failed to combine header and row', ['header' => $header, 'row' => $row]);
                continue;
            }

            // Dispatch one job per row to rabbitmq imports queue
            try {
                \App\Jobs\ImportBudgetHoldersJob::dispatch($assoc, $userId)
                    ->onConnection('rabbitmq')
                    ->onQueue('imports');
            } catch (\Throwable $e) {
                Log::error('Failed to dispatch ImportBudgetHoldersJob', ['error' => $e->getMessage(), 'row' => $assoc]);
            }
        }

        return response()->json([
            'message' => 'Импорт отправлен в очередь',
            'data' => [],
            'timestamp' => now()->toIso8601String(),
            'success' => true,
        ]);
    }
}
