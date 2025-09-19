<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SwiftCodeController;
use App\Http\Controllers\BudgetHolderController;
use App\Http\Controllers\TreasuryAccountController;

Route::apiResource('swift', SwiftCodeController::class);
Route::apiResource('budget-holders', BudgetHolderController::class);
Route::apiResource('treasury-accounts', TreasuryAccountController::class);
