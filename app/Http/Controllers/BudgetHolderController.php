<?php

namespace App\Http\Controllers;

use App\Models\BudgetHolder;
use Illuminate\Http\Request;
use App\Http\Requests\StoreBudgetHolderRequest;

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
        $model = BudgetHolder::create($request->validated());

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
        $budgetHolder->update($request->validated());

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
        $budgetHolder->delete();

        return response()->json([
            'message' => 'Успешно',
            'data' => [],
            'timestamp' => now()->toIso8601String(),
            'success' => true,
        ]);
    }
}
