<?php

namespace App\Http\Controllers\Fat;

use App\Http\Controllers\Controller;
use App\Models\BudgetCategory;
use App\Models\Department;
use App\Models\Expense;
use App\Models\FiscalYear;
use App\Models\GlobalMonthlyBudget;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user->isFAT() && !$user->isSuperAdmin()) {
            abort(403);
        }

        $activeFiscalYear = FiscalYear::where('is_active', true)->first();
        $fiscalYears      = FiscalYear::orderByDesc('year')->get();

        $selectedYear   = (int) $request->get('year', $activeFiscalYear?->year ?? now()->year);
        $selectedPeriod = $request->get('period', 'semua');

        $fiscalYear = FiscalYear::where('year', $selectedYear)->first() ?? $activeFiscalYear;

        // ── Resolve month range from period ─────────────────────────────────
        $periodMap = [
            'semua' => range(1, 12),
            'all'   => range(1, 12),
            's1'    => range(1, 6),
            's2'    => range(7, 12),
            'q1'    => range(1, 3),
            'q2'    => range(4, 6),
            'q3'    => range(7, 9),
            'q4'    => range(10, 12),
        ];

        $periodLabels = [
            'semua' => 'Semua Data (Jan–Des)',
            'all'   => 'Tahunan (Jan–Des)',
            's1'    => 'Semester 1 (Jan–Jun)',
            's2'    => 'Semester 2 (Jul–Des)',
            'q1'    => 'Triwulan 1 (Jan–Mar)',
            'q2'    => 'Triwulan 2 (Apr–Jun)',
            'q3'    => 'Triwulan 3 (Jul–Sep)',
            'q4'    => 'Triwulan 4 (Okt–Des)',
        ];

        // Determine active months & view mode
        $isDetailedView = false; // monthly matrix mode (khusus 'semua')
        if (isset($periodMap[$selectedPeriod])) {
            $activeMonths   = $periodMap[$selectedPeriod];
            $periodLabel    = $periodLabels[$selectedPeriod];
            $isDetailedView = ($selectedPeriod === 'semua');
        } elseif (is_numeric($selectedPeriod) && (int)$selectedPeriod >= 1 && (int)$selectedPeriod <= 12) {
            $activeMonths = [(int)$selectedPeriod];
            $periodLabel  = 'Bulan ' . Carbon::create(null, (int)$selectedPeriod)->translatedFormat('F');
        } else {
            $activeMonths   = range(1, 12);
            $periodLabel    = 'Semua Data (Jan–Des)';
            $selectedPeriod = 'semua';
            $isDetailedView = true;
        }

        // ── Pre-fetch monthly omsets ─────────────────────────────────────────
        $monthlyOmsets = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyOmsets[$m] = 0;
        }
        $allOmsets = GlobalMonthlyBudget::where('fiscal_year_id', $fiscalYear?->id)
            ->where('type', 'actual')
            ->whereIn('month', $activeMonths)
            ->get();
        foreach ($allOmsets as $omset) {
            $monthlyOmsets[$omset->month] = (float) $omset->amount;
        }

        $globalTotalOmset = array_sum(array_intersect_key($monthlyOmsets, array_flip($activeMonths)));

        // ── Expenses query ───────────────────────────────────────────────────
        $monthsIn = implode(',', array_map('intval', $activeMonths));

        // Always get per-category totals (for summary columns)
        $expensesByCategoryRows = Expense::whereYear('date', $selectedYear)
            ->whereRaw("MONTH(date) IN ({$monthsIn})")
            ->selectRaw('budget_category_id, SUM(amount) as total')
            ->groupBy('budget_category_id')
            ->get();

        $expensesByCategory = collect();
        foreach ($expensesByCategoryRows as $exp) {
            $expensesByCategory->put($exp->budget_category_id, (float)$exp->total);
        }

        // Get per-department totals
        $expensesByDepartmentRows = Expense::whereYear('date', $selectedYear)
            ->whereRaw("MONTH(date) IN ({$monthsIn})")
            ->selectRaw('department_id, SUM(amount) as total')
            ->groupBy('department_id')
            ->get();

        $expensesByDepartment = collect();
        foreach ($expensesByDepartmentRows as $exp) {
            $expensesByDepartment->put($exp->department_id, (float)$exp->total);
        }

        // For 'semua' also get per-month breakdown
        $expensesByMonthCategory = [];
        $expensesByMonthDepartment = [];
        if ($isDetailedView) {
            $expensesByMonthRows = Expense::whereYear('date', $selectedYear)
                ->whereRaw("MONTH(date) IN ({$monthsIn})")
                ->selectRaw('budget_category_id, MONTH(date) as month, SUM(amount) as total')
                ->groupBy('budget_category_id', 'month')
                ->get();
            foreach ($expensesByMonthRows as $exp) {
                $expensesByMonthCategory[$exp->month][$exp->budget_category_id] = (float)$exp->total;
            }

            $expensesByMonthDeptRows = Expense::whereYear('date', $selectedYear)
                ->whereRaw("MONTH(date) IN ({$monthsIn})")
                ->selectRaw('department_id, MONTH(date) as month, SUM(amount) as total')
                ->groupBy('department_id', 'month')
                ->get();
            foreach ($expensesByMonthDeptRows as $exp) {
                $expensesByMonthDepartment[$exp->month][$exp->department_id] = (float)$exp->total;
            }
        }

        // ── Departments ──────────────────────────────────────────────────────
        $departments = Department::where('fiscal_year_id', $fiscalYear?->id)
            ->with(['budgetCategories' => fn($q) => $q->orderBy('code')])
            ->orderBy('name')
            ->get();

        // ── Build report rows ────────────────────────────────────────────────
        $report = $departments->map(function ($dept) use (
            $globalTotalOmset, $monthlyOmsets, $expensesByCategory, $expensesByDepartment,
            $expensesByMonthCategory, $expensesByMonthDepartment, $activeMonths, $isDetailedView
        ) {
            $categories = $dept->budgetCategories->map(function ($cat) use (
                $globalTotalOmset, $monthlyOmsets, $expensesByCategory,
                $expensesByMonthCategory, $activeMonths, $isDetailedView
            ) {
                $budgetRatio    = (float)$cat->budget_ratio_percent;
                $catBudget      = $globalTotalOmset > 0 ? ($globalTotalOmset * $budgetRatio / 100) : 0;
                $catUsed        = (float) $expensesByCategory->get($cat->id, 0);
                $realisasiRatio = $globalTotalOmset > 0 ? round(($catUsed / $globalTotalOmset) * 100, 2) : 0;
                $surplus        = $catBudget - $catUsed;

                // Monthly matrix — only for 'semua'
                $monthlyData = [];
                if ($isDetailedView) {
                    foreach ($activeMonths as $m) {
                        $mOmset  = $monthlyOmsets[$m] ?? 0;
                        $mBudget = $mOmset > 0 ? ($mOmset * $budgetRatio / 100) : 0;
                        $mUsed   = $expensesByMonthCategory[$m][$cat->id] ?? 0;
                        $monthlyData[$m] = ['budget' => $mBudget, 'used' => $mUsed];
                    }
                }

                return [
                    'id'              => $cat->id,
                    'code'            => $cat->code,
                    'name'            => $cat->name,
                    'budget'          => $catBudget,
                    'used'            => $catUsed,
                    'budget_ratio'    => $budgetRatio,
                    'realisasi_ratio' => $realisasiRatio,
                    'surplus'         => $surplus,
                    'monthly_data'    => $monthlyData,
                ];
            });

            $deptBudgetRatio    = (float)$dept->budget_ratio_percent;
            $deptBudget         = $globalTotalOmset > 0 ? ($globalTotalOmset * $deptBudgetRatio / 100) : 0;
            $deptUsed           = (float)$expensesByDepartment->get($dept->id, 0);
            $deptSurplus        = $deptBudget - $deptUsed;
            $deptRealisasiRatio = $globalTotalOmset > 0 ? round(($deptUsed / $globalTotalOmset) * 100, 2) : 0;

            $deptMonthlyData = [];
            if ($isDetailedView) {
                foreach ($activeMonths as $m) {
                    $mOmset  = $monthlyOmsets[$m] ?? 0;
                    $mBudget = $mOmset > 0 ? ($mOmset * $deptBudgetRatio / 100) : 0;
                    $mUsed   = $expensesByMonthDepartment[$m][$dept->id] ?? 0;
                    $deptMonthlyData[$m] = [
                        'budget' => $mBudget,
                        'used'   => $mUsed,
                    ];
                }
            }

            return [
                'id'              => $dept->id,
                'name'            => $dept->name,
                'code'            => $dept->code,
                'budget'          => $deptBudget,
                'used'            => $deptUsed,
                'budget_ratio'    => $deptBudgetRatio,
                'realisasi_ratio' => $deptRealisasiRatio,
                'surplus'         => $deptSurplus,
                'categories'      => $categories,
                'monthly_data'    => $deptMonthlyData,
            ];
        });

        $totalBudget  = $report->sum('budget');
        $totalUsed    = $report->sum('used');
        $totalSurplus = $totalBudget - $totalUsed;

        $months = collect(range(1, 12))->mapWithKeys(
            fn($m) => [$m => Carbon::create(null, $m)->translatedFormat('F')]
        );

        return view('fat.laporan.index', compact(
            'report', 'fiscalYears', 'selectedYear', 'selectedPeriod',
            'globalTotalOmset', 'totalBudget', 'totalUsed', 'totalSurplus',
            'months', 'fiscalYear', 'monthlyOmsets',
            'periodLabel', 'activeMonths', 'isDetailedView'
        ));
    }
}
