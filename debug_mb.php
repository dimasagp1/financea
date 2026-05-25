<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Print out monthly budgets
foreach(App\Models\MonthlyBudget::all() as $mb) {
    echo $mb->department_id . ' - ' . $mb->month . ' - ' . $mb->amount . "\n";
}
echo "Global\n";
foreach(App\Models\GlobalMonthlyBudget::where('type', 'forecast')->get() as $gmb) {
    echo $gmb->year . '-' . $gmb->month . ' - ' . $gmb->amount . "\n";
}
