<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentDashboardController;
use App\Http\Controllers\Fat\BudgetManagementController;
use App\Http\Controllers\Fat\FinanceManagementController;
use App\Http\Controllers\Fat\MonitoringBudgetController;
use App\Http\Controllers\OdooImportController;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::middleware('role:departemen')->group(function () {
        Route::get('/departemen/dashboard', [DepartmentDashboardController::class, 'index'])->name('departemen.dashboard');
    });

    Route::middleware('role:manager')->group(function () {
        Route::get('/manager/dashboard', [App\Http\Controllers\ManagerDashboardController::class, 'index'])->name('manager.dashboard');
    });

    // Monitoring Budget Routes (Access controlled by Controller)
    Route::prefix('monitoring')->name('monitoring.')->group(function () {
        Route::get('/', [MonitoringBudgetController::class, 'index'])->name('index');
        Route::post('/categories', [MonitoringBudgetController::class, 'storeCategory'])->name('categories.store');
        Route::patch('/categories/{category}', [MonitoringBudgetController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{category}', [MonitoringBudgetController::class, 'destroyCategory'])->name('categories.destroy');
        Route::get('/categories/{category}', [MonitoringBudgetController::class, 'show'])->name('categories.show');
        Route::post('/expenses', [MonitoringBudgetController::class, 'storeExpense'])->name('expenses.store');
        Route::patch('/expenses/{expense}', [MonitoringBudgetController::class, 'updateExpense'])->name('expenses.update');
        Route::delete('/expenses/{expense}', [MonitoringBudgetController::class, 'destroyExpense'])->name('expenses.destroy');
    });

    // Forecast Routes (Access controlled by Controller)
    Route::prefix('fat')->name('fat.')->group(function () {
        Route::get('forecasts/export', [App\Http\Controllers\Fat\ForecastController::class, 'exportExcel'])->name('forecasts.export');
        Route::post('forecasts/import', [App\Http\Controllers\Fat\ForecastController::class, 'importExcel'])->name('forecasts.import');
        Route::resource('forecasts', App\Http\Controllers\Fat\ForecastController::class)->except(['show']);

        // Global Monthly Budgets
        Route::resource('global-budgets', App\Http\Controllers\Fat\GlobalBudgetController::class);
    });

    Route::prefix('fat')->name('fat.')->middleware('role:fat,superadmin')->group(function () {
        Route::get('/departments', [FinanceManagementController::class, 'departments'])->name('departments.index');
        Route::post('/departments', [FinanceManagementController::class, 'storeDepartment'])->name('departments.store');
        Route::patch('/departments/{department}', [FinanceManagementController::class, 'updateDepartment'])->name('departments.update');
        Route::delete('/departments/{department}', [FinanceManagementController::class, 'destroyDepartment'])->name('departments.destroy');

        // Manage Cost Categories (per department)
        Route::get('/departments/{department}/categories', [FinanceManagementController::class, 'categories'])->name('departments.categories');
        Route::post('/departments/{department}/categories', [FinanceManagementController::class, 'storeCategory'])->name('categories.store');
        Route::patch('/categories/{category}', [FinanceManagementController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{category}', [FinanceManagementController::class, 'destroyCategory'])->name('categories.destroy');

        Route::get('/laporan', [\App\Http\Controllers\Fat\ReportController::class, 'index'])->name('laporan.index');
        Route::get('/odoo-excel/template', [\App\Http\Controllers\Fat\OdooExcelController::class, 'exportTemplate'])->name('odoo.excel.template');
        Route::post('/odoo-excel/import', [\App\Http\Controllers\Fat\OdooExcelController::class, 'import'])->name('odoo.excel.import');

        // Activity Log — accessible by FAT & Superadmin
        Route::get('/activity-logs', [\App\Http\Controllers\Fat\ActivityLogController::class, 'index'])->name('activity-logs.index');

        Route::middleware('role:superadmin')->group(function () {
            
            Route::get('/fiscal-years', [FinanceManagementController::class, 'fiscalYears'])->name('fiscal-years.index');
            Route::post('/fiscal-years', [FinanceManagementController::class, 'storeFiscalYear'])->name('fiscal-years.store');
            Route::patch('/fiscal-years/{fiscalYear}', [FinanceManagementController::class, 'updateFiscalYear'])->name('fiscal-years.update');
            Route::patch('/fiscal-years/{fiscalYear}/activate', [FinanceManagementController::class, 'activateFiscalYear'])->name('fiscal-years.activate');

            Route::get('/settings', [\App\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
            Route::post('/settings', [\App\Http\Controllers\SettingController::class, 'update'])->name('settings.update');

            Route::get('/users', [FinanceManagementController::class, 'users'])->name('users.index');
            Route::post('/users', [FinanceManagementController::class, 'storeUser'])->name('users.store');
            Route::patch('/users/{user}', [FinanceManagementController::class, 'updateUser'])->name('users.update');
            Route::delete('/users/{user}', [FinanceManagementController::class, 'destroyUser'])->name('users.destroy');
        });



        Route::patch('/fiscal-years/{fiscalYear}/global-budget', [BudgetManagementController::class, 'updateGlobalBudget'])
            ->name('fiscal-years.update-global-budget');
        Route::patch('/departments/{department}/ratio', [BudgetManagementController::class, 'updateDepartmentRatio'])
            ->name('departments.update-ratio');
        Route::patch('/departments/ratios/bulk', [BudgetManagementController::class, 'bulkUpdateDepartmentRatios'])
            ->name('departments.bulk-update-ratios');
        Route::patch('/departments/{department}/monthly-budget', [BudgetManagementController::class, 'overrideMonthlyBudget'])
            ->name('departments.override-monthly-budget');
        Route::post('/odoo/sync-expenses', [OdooImportController::class, 'syncExpenses'])
            ->name('odoo.sync-expenses');


    });
});
