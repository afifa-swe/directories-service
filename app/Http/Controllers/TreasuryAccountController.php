<?php

namespace App\Http\Controllers;

use App\Models\TreasuryAccount;
use Illuminate\Http\Request;
use App\Http\Requests\StoreTreasuryAccountRequest;

class TreasuryAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = TreasuryAccount::paginate(25);
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
    public function store(StoreTreasuryAccountRequest $request)
    {
        $model = TreasuryAccount::create($request->validated());

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
        $treasuryAccount->update($request->validated());

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
        $treasuryAccount->delete();

        return response()->json([
            'message' => 'Успешно',
            'data' => [],
            'timestamp' => now()->toIso8601String(),
            'success' => true,
        ]);
    }
}
