<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$allExpenses = App\Models\Expense::all();
$expensesByDeptCat = $allExpenses->groupBy('department_id')
    ->map(function($rows){
        return $rows->groupBy('budget_category_id')->map(function($catRows){
            return $catRows->sum('amount');
        });
    });
echo data_get($expensesByDeptCat, '10.73', 'Failed');
