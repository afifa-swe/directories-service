<?php

namespace App\Http\Controllers;

use App\Models\SwiftCode;
use Illuminate\Http\Request;
use App\Http\Requests\StoreSwiftCodeRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Jobs\ImportSwiftCodesJob;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SwiftCodeImport;


class SwiftCodeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $allowedSorts = ['swift_code', 'bank_name', 'country', 'city', 'created_at'];
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

        $query = SwiftCode::query()
            ->when(auth()->check(), fn($q) => $q->where('created_by', auth()->id()))
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $term = "%{$request->search}%";
                    $sub->where('bank_name', 'ilike', $term)
                        ->orWhere('address', 'ilike', $term)
                        ->orWhere('country', 'ilike', $term)
                        ->orWhere('city', 'ilike', $term)
                        ->orWhere('swift_code', 'ilike', $term);
                });
            })
            ->when($request->filled('country'), fn($q) => $q->where('country', $request->country))
            ->when($request->filled('city'), fn($q) => $q->where('city', $request->city))
            ->when($request->filled('bank_name'), fn($q) => $q->where('bank_name', $request->bank_name))
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
    public function store(StoreSwiftCodeRequest $request)
    {
        $data = $request->validated();
        // ensure owner is current user
        if (auth()->check()) {
            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();
        }

        $model = SwiftCode::create($data);

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
    public function show(SwiftCode $swift)
    {
        if (auth()->check() && $swift->created_by !== auth()->id()) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }
        return response()->json([
            'message' => 'Успешно',
            'data' => $swift,
            'timestamp' => now()->toIso8601String(),
            'success' => true,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreSwiftCodeRequest $request, SwiftCode $swift)
    {
        if (auth()->check() && $swift->created_by !== auth()->id()) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        $data = $request->validated();
        if (auth()->check()) {
            $data['updated_by'] = auth()->id();
        }

        $swift->update($data);

        return response()->json([
            'message' => 'Успешно',
            'data' => $swift,
            'timestamp' => now()->toIso8601String(),
            'success' => true,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SwiftCode $swift)
    {
        if (auth()->check() && $swift->created_by !== auth()->id()) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        $swift->delete();

        return response()->json([
            'message' => 'Успешно',
            'data' => [],
            'timestamp' => now()->toIso8601String(),
            'success' => true,
        ]);
    }

    /**
     * Import swift codes from an Excel file.
     */
public function import(Request $request)
{
    $validator = Validator::make($request->all(), [
        'file' => 'required|mimes:csv,txt|max:5120',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Ошибка валидации',
            'data' => $validator->errors()->messages(),
            'timestamp' => now()->toISOString(),
            'success' => false,
        ], 422);
    }

    $file = $request->file('file');
    $rows = array_map('str_getcsv', file($file->getRealPath()));

    if (count($rows) <= 1) {
        return response()->json([
            'message' => 'Файл не содержит данных',
            'data' => [],
            'timestamp' => now()->toISOString(),
            'success' => false,
        ], 400);
    }

    $header = array_map(fn($h) => mb_strtolower(trim($h)), $rows[0]);
    $dataRows = array_slice($rows, 1);

    $assocRows = [];
    $lineNumber = 1; // header is line 1
    foreach ($dataRows as $row) {
        $lineNumber++;

        $row = array_map(fn($c) => is_string($c) ? trim($c) : $c, $row);

        $headerCount = count($header);
        $rowCount = count($row);

        if ($rowCount !== $headerCount) {
            $isAllEmpty = true;
            foreach ($row as $cell) {
                if ($cell !== null && $cell !== '') {
                    $isAllEmpty = false;
                    break;
                }
            }

            Log::error('CSV row/column mismatch during import', [
                'file_path' => $file->getRealPath(),
                'line' => $lineNumber,
                'header_count' => $headerCount,
                'row_count' => $rowCount,
                'header' => $header,
                'row' => $row,
                'all_empty' => $isAllEmpty,
            ]);

            if ($isAllEmpty) {
                continue;
            }

            if ($rowCount < $headerCount) {
                $row = array_pad($row, $headerCount, null);
            } else {
                $row = array_slice($row, 0, $headerCount);
            }
        }

        $assoc = array_combine($header, $row);
        if ($assoc === false) {
            Log::error('Failed to combine header and row into assoc array', [
                'file_path' => $file->getRealPath(),
                'line' => $lineNumber,
                'header' => $header,
                'row' => $row,
            ]);
            continue;
        }

        if (!empty($assoc['swift_code']) && !empty($assoc['bank_name'])) {
            $assocRows[] = $assoc;
        }
    }

    if (empty($assocRows)) {
        return response()->json([
            'message' => 'Не найдено строк с валидными данными',
            'data' => [],
            'timestamp' => now()->toISOString(),
            'success' => false,
        ], 400);
    }

    $chunks = array_chunk($assocRows, 10);
    foreach ($chunks as $i => $chunk) {
        ImportSwiftCodesJob::dispatch($chunk, auth()->id())
            ->onConnection('rabbitmq')
            ->onQueue('imports');

        Log::info('Dispatched Swift chunk to RabbitMQ', [
            'index' => $i,
            'rows' => count($chunk),
        ]);
    }

    return response()->json([
        'message' => 'Импорт запущен в очередь RabbitMQ',
        'data' => [
            'total_rows' => count($assocRows),
            'chunks' => count($chunks),
            'chunk_size' => 10
        ],
        'timestamp' => now()->toISOString(),
        'success' => true,
    ]);
}
}
