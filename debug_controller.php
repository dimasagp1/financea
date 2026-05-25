<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Set up identical params as ForecastController March 2026
$currentDate = \Carbon\Carbon::createFromDate(2026, 3, 1);
$departments = App\Models\Department::all();

$allExpenses = App\Models\Expense::whereYear('date', $currentDate->year)
    ->whereMonth('date', $currentDate->month)
    ->whereIn('department_id', $departments->pluck('id'))
    ->get();

$expensesByDeptCat = $allExpenses->groupBy('department_id')
    ->map(function($rows) { return $rows->groupBy('budget_category_id')
        ->map(function($catRows) { return $catRows->sum('amount'); }); });

$dept = $departments->firstWhere('id', 8); // General Affair
if (!$dept) die("No GA dept");

// Exactly how ForecastController does it
$activeFiscalYear = App\Models\FiscalYear::where('is_active', true)->first();
$categories = $dept->budgetCategories()
    ->where('fiscal_year_id', $activeFiscalYear->id)
    ->orderBy('code')
    ->get()
    ->map(function ($cat) use ($dept, $expensesByDeptCat) {
        $catActual = 0;
        $deptExpenses = $expensesByDeptCat->get($dept->id);
        if ($deptExpenses) {
            $catActual = (float) $deptExpenses->get($cat->id, 0);
        }
        $cat->actual_spending = $catActual;
        return $cat;
    });

foreach($categories as $cat) {
    if ($cat->actual_spending > 0) {
        echo "Found! Cat {$cat->id} ({$cat->name}) = {$cat->actual_spending}\n";
    }
}
echo "Total categories for GA: " . $categories->count() . "\n";
echo "Total actual spending computed: " . $categories->sum('actual_spending') . "\n";
