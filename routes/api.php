<?php

use App\Http\Controllers\Api\BudgetApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api.key'])->group(function () {
    Route::post('/budget/check', [BudgetApiController::class, 'check']);
    Route::post('/budget/generate-monthly', [BudgetApiController::class, 'generateMonthly']);
    Route::get('/budget/monthly-status', [BudgetApiController::class, 'monthlyStatus']);
    Route::get('/budget/list-monthly', [BudgetApiController::class, 'listMonthly']);
    Route::get('/budget/detail-monthly', [BudgetApiController::class, 'detailMonthly']);
    Route::get('/budget/categories', [BudgetApiController::class, 'categories']);
    Route::get('/budget/departments', [BudgetApiController::class, 'departments']);
    Route::get('/budget/stagings', [BudgetApiController::class, 'stagings']);
    Route::post('/budget/record-expense', [BudgetApiController::class, 'recordExpense']);
    Route::post('/budget/remove-expense', [BudgetApiController::class, 'removeExpense']);
});

// Fallback: GET request ke endpoint POST akan mendapat pesan jelas
Route::get('/budget/check', function () {
    return response()->json([
        'status' => 'error',
        'message' => 'Method tidak valid. Gunakan POST untuk endpoint ini.',
    ], 405);
});
