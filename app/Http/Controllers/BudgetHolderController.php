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
    public function index()
    {
        $data = BudgetHolder::paginate(25);
        return response()->json([
            'message' => 'Успешно',
            'data' => $data,
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
