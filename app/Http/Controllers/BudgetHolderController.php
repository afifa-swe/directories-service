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

        $items = $query->paginate($request->get('per_page', 20));

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

        try {
            $userId = auth()->check() ? auth()->id() : null;
            Excel::queueImport(new BudgetHolderImport($userId), $request->file('file'));

            return response()->json([
                'message' => 'Импорт запущен',
                'data' => [],
                'timestamp' => now()->toIso8601String(),
                'success' => true,
            ]);
        } catch (\Throwable $e) {
            Log::error('BudgetHolder import failed to queue', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Ошибка при запуске импорта',
                'data' => [],
                'timestamp' => now()->toIso8601String(),
                'success' => false,
            ], 500);
        }
    }
}
