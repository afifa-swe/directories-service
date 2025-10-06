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

        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 25);
        $perPage = max(20, min($perPage, 100));

        if ($waitRetries > 0) {
            $attempt = 0;
            do {
                $items = $query->paginate($perPage, ['*'], 'page', $page);
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

        $file = $request->file('file');
        $userId = auth()->check() ? auth()->id() : null;

        $path = $file->getRealPath();
        if (!is_readable($path)) {
            Log::error('TreasuryAccount import file not readable', ['path' => $path]);
            return response()->json(['message' => 'Файл недоступен для чтения', 'success' => false], 400);
        }

        $fh = fopen($path, 'r');
        if ($fh === false) {
            Log::error('Failed to open treasury accounts file', ['path' => $path]);
            return response()->json(['message' => 'Не удалось открыть файл', 'success' => false], 400);
        }

        $firstLine = fgets($fh);
        rewind($fh);
        $delimiter = strpos($firstLine, ';') !== false ? ';' : ',';

        $header = [];
        $rows = [];
        $line = 0;
        while (($data = fgetcsv($fh, 0, $delimiter)) !== false) {
            $line++;
            if ($line === 1) {
                $header = array_map(fn($h) => mb_strtolower(trim($h)), $data);
                continue;
            }

            // trim values and skip empty rows
            $data = array_map(fn($c) => is_string($c) ? trim($c) : $c, $data);
            // pad/truncate to header length
            $data = array_pad($data, count($header), null);
            $assoc = @array_combine($header, $data);
            if ($assoc === false) {
                Log::warning('TreasuryAccount import: header/row mismatch', ['line' => $line, 'row' => $data]);
                continue;
            }

            // skip rows where all fields are empty
            $allEmpty = true;
            foreach ($assoc as $v) {
                if (!is_null($v) && $v !== '') { $allEmpty = false; break; }
            }
            if ($allEmpty) continue;

            $rows[] = $assoc;
        }

        fclose($fh);

        if (empty($rows)) {
            return response()->json(['message' => 'Не найдено строк с данными', 'success' => false], 400);
        }

        $sleepSeconds = (int) env('TREASURY_IMPORT_SLEEP_SECONDS', 1);

        $chunks = array_chunk($rows, 10);
        $dispatched = 0;

        foreach ($chunks as $chunk) {
            try {
                // dispatch one job for the whole chunk (array of rows)
                \App\Jobs\ImportTreasuryAccountsJob::dispatch($chunk, $userId)
                    ->onConnection('rabbitmq')
                    ->onQueue('imports');

                $dispatched++;
                Log::info('Dispatched ImportTreasuryAccountsJob (chunk)', ['count' => count($chunk)]);
            } catch (\Throwable $e) {
                Log::error('Failed to dispatch ImportTreasuryAccountsJob (chunk)', ['error' => $e->getMessage(), 'chunk_count' => count($chunk)]);
            }

            if ($sleepSeconds > 0) {
                sleep($sleepSeconds);
            }
        }

        return response()->json([
            'message' => 'Импорт поставлен в очередь',
            'data' => [
                'total_rows' => count($rows),
                'queued_jobs' => $dispatched,
                'chunk_size' => 10
            ],
            'timestamp' => now()->toIso8601String(),
            'success' => true,
        ]);

    }
}
