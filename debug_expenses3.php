<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$allExpenses = App\Models\Expense::whereMonth('date', 3)->get();
$expensesByDeptCat = $allExpenses->groupBy('department_id')->map(function($rows){
    return $rows->groupBy('budget_category_id')->map(function($catRows){
        return $catRows->sum('amount');
    });
});
print_r($expensesByDeptCat->toArray());
