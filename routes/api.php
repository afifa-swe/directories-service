<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SwiftCodeController;
use App\Http\Controllers\BudgetHolderController;
use App\Http\Controllers\TreasuryAccountController;
use App\Http\Controllers\FileUploadController;

// Public endpoint for quick MinIO testing (no auth)
Route::post('/upload', [FileUploadController::class, 'store']);

// Authentication endpoints (public)
Route::post('/register', [\App\Http\Controllers\Auth\RegisterController::class, 'register']);
Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login']);

// Debug endpoint (public) - returns raw Authorization header and extracted token.
// Use this to verify Postman / curl send token without surrounding quotes or extra chars.
Route::get('/auth-check', function (Request $request) {
    $auth = $request->header('authorization');
    $token = $auth ? preg_replace('/^Bearer\s+/i', '', $auth) : null;
    $hasQuotes = is_string($token) && (str_contains($token, '"') || str_contains($token, "'"));

    return response()->json([
        'authorization_header' => $auth,
        'token' => $token,
        'has_quotes_or_extra_chars' => $hasQuotes,
    ]);
});

Route::middleware(['auth:api'])->group(function () {
    Route::apiResource('swift', SwiftCodeController::class);
    Route::post('swift/import', [SwiftCodeController::class, 'import']);

    Route::apiResource('budget-holders', BudgetHolderController::class);
    Route::post('budget-holders/import', [BudgetHolderController::class, 'import']);

    Route::apiResource('treasury-accounts', TreasuryAccountController::class);
    Route::post('treasury-accounts/import', [TreasuryAccountController::class, 'import']);

    // keep upload in protected group as well if needed
    // Route::post('/upload', [FileUploadController::class, 'store']);
});

// Temporary debug route (local/debug only): returns total count of BudgetHolder records
if (app()->environment('local') || config('app.debug')) {
    Route::get('/debug/budget-holders-count', function () {
        try {
            $count = \App\Models\BudgetHolder::count();
            return response()->json(['count' => $count]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
}
