<?php

namespace App\Http\Controllers;

use App\Models\SwiftCode;
use Illuminate\Http\Request;
use App\Http\Requests\StoreSwiftCodeRequest;

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

        $items = SwiftCode::query()
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
            ->orderBy($sort, $direction)
            ->paginate($request->get('per_page', 20));

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
        $model = SwiftCode::create($request->validated());

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
        $swift->update($request->validated());

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
        $swift->delete();

        return response()->json([
            'message' => 'Успешно',
            'data' => [],
            'timestamp' => now()->toIso8601String(),
            'success' => true,
        ]);
    }
}
