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
    public function index()
    {
        $data = SwiftCode::paginate(25);
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
