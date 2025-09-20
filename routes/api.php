<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SwiftCodeController;
use App\Http\Controllers\BudgetHolderController;
use App\Http\Controllers\TreasuryAccountController;

Route::middleware(['auth:api', \App\Http\Middleware\PassportClientOrUser::class])->group(function () {
    Route::apiResource('swift', SwiftCodeController::class);
    Route::post('swift/import', [SwiftCodeController::class, 'import']);

    Route::apiResource('budget-holders', BudgetHolderController::class);
    Route::post('budget-holders/import', [BudgetHolderController::class, 'import']);

    Route::apiResource('treasury-accounts', TreasuryAccountController::class);
    Route::post('treasury-accounts/import', [TreasuryAccountController::class, 'import']);
});
