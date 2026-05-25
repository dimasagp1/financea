<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Department;
use App\Models\FiscalYear;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        if ($user->isDepartemen()) {
            return redirect()->route('departemen.dashboard');
        }

        if ($user->isManager()) {
            return redirect()->route('manager.dashboard');
        }

        // Hide main dashboard for FAT role and redirect Superadmin
        // to department management as the initial page after login
        if ($user->isFAT() || $user->isSuperAdmin()) {
            return redirect()->route('fat.departments.index');
        }

        $activeFiscalYear = FiscalYear::query()->where('is_active', true)->first();

        // Determine Date Context
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $currentDate = Carbon::createFromDate($year, $month, 1);
        $currentMonth = $currentDate->month;
        $currentMonthName = $currentDate->translatedFormat('F Y');

        if (!$activeFiscalYear) {
            return view('dashboard.index', [
                'activeFiscalYear' => null,
                'departments' => collect(),
                'summary' => [
                    'global_budget' => 0,
                    'allocated' => 0,
                    'used' => 0,
                    'remaining' => 0,
                ],
                'userRole' => $user->role,
            ]);
        }

        $departmentQuery = Department::query()
            ->with(['expenses', 'budgetCategories.expenses', 'monthlyBudgets'])
            ->where('fiscal_year_id', $activeFiscalYear->id);

        $selectedDepartmentId = $request->filled('department_id')
            ? (int) $request->input('department_id')
            : null;

        if ($selectedDepartmentId) {
            $departmentQuery->where('id', $selectedDepartmentId);
        }

        if ($user->isDepartemen()) {
            $departmentQuery->where('id', $user->department_id);
        }

        // Fetch ALL Global Monthly Budgets for the active FY to populate charts correctly
        $allGlobalMonthly = \App\Models\GlobalMonthlyBudget::where('fiscal_year_id', $activeFiscalYear->id)
            ->where('year', (int) $year)
            ->get()
            ->keyBy('month');

        $globalMonthly = $allGlobalMonthly->get((int) $month);

        $departments = $departmentQuery->get()->map(function (Department $department) use ($month, $year, $globalMonthly, $allGlobalMonthly) {
            // --- 1. Monthly Breakdown & Current Month Stats ---
            $monthlyBreakdown = [];
            $currentMonthStats = ['standard' => 0, 'actual' => 0, 'diff' => 0];

            for ($m = 1; $m <= 12; $m++) {
                // Determine Standard (Budget) for Month $m
                // Check if override exists in monthlyBudgets
                $monthStr = sprintf('%04d-%02d', $year, $m);
                $monthlyBudget = $department->monthlyBudgets->where('month', $monthStr)->first();

                if ($monthlyBudget) {
                    $std = (float) $monthlyBudget->amount;
                } else {
                    // Logic Phase 1: Monthly Global * Dept Ratio
                    $gMB = $allGlobalMonthly->get($m);
                    if ($gMB) {
                        $std = ($gMB->amount * $department->budget_ratio_percent) / 100;
                    } else {
                        $std = 0;
                    }
                }

                // Determine Actual (Expenses) for Month $m
                $act = $department->expenses->filter(function ($exp) use ($m, $year) {
                    return $exp->date->month == $m && $exp->date->year == $year;
                })->sum('amount');

                $monthlyBreakdown[] = [
                    'month' => $m,
                    'name' => \Carbon\Carbon::create()->month($m)->translatedFormat('F'),
                    'standard' => $std,
                    'actual' => $act,
                    'diff' => $std - $act,
                    'is_current' => ($m == $month),
                ];

                if ($m == $month) {
                    $currentMonthStats = [
                        'standard' => $std,
                        'actual' => $act,
                        'diff' => $std - $act
                    ];
                }
            }

            $monthlyAllocated = $currentMonthStats['standard'];
            $monthlyUsed = $currentMonthStats['actual'];
            $monthlyUtilization = $monthlyAllocated > 0 ? round(($monthlyUsed / $monthlyAllocated) * 100, 2) : 0;
            $percentOfGlobal = ($globalMonthly && $globalMonthly->amount > 0) ? round(($monthlyUsed / $globalMonthly->amount) * 100, 2) : 0;

            // Thresholds: 0-20 warning, 21-80 success, 81-100 warning, >100 danger
            if ($monthlyUtilization > 100) {
                $status = 'danger';
            } elseif ($monthlyUtilization <= 20) {
                $status = 'warning';
            } elseif ($monthlyUtilization <= 80) {
                $status = 'success';
            } elseif ($monthlyUtilization <= 100) {
                $status = 'warning';
            } else {
                $status = 'danger';
            }

            $categoryBreakdown = $department->budgetCategories->map(function ($category) use ($monthlyAllocated, $department, $month, $year) {
                // Category budget is the fraction of its relative ratio against Department's total ratio.
                $ratioFraction = $department->budget_ratio_percent > 0
                    ? ($category->budget_ratio_percent / $department->budget_ratio_percent)
                    : 0;
                $catBudget = $monthlyAllocated * $ratioFraction;

                $monthlyExpenses = $category->expenses->filter(function ($exp) use ($month, $year) {
                    return $exp->date->month == $month && $exp->date->year == $year;
                });
                $catTotalUsed = (float) $monthlyExpenses->sum('amount');

                $percentOfDept = $monthlyAllocated > 0 ? ($catTotalUsed / $monthlyAllocated) * 100 : 0;
                $utilization = $catBudget > 0 ? ($catTotalUsed / $catBudget) * 100 : 0;

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'ratio' => (float) $category->budget_ratio_percent,
                    'category_budget' => $catBudget,
                    'used' => $catTotalUsed,
                    'utilization' => $utilization,
                    'percent_of_dept_budget' => $percentOfDept,
                    'expenses' => $monthlyExpenses->map(function ($e) {
                        return [
                            'name' => $e->description,
                            'amount' => (float) $e->amount,
                            'share_percent' => 0, // Calculated below if needed
                        ];
                    })
                ];
            });

            // Calculate Alerts (similar to DepartmentDashboardController)
            $itemExpenseAlerts = $categoryBreakdown->flatMap(function ($cat) use ($monthlyUsed) {
                return collect($cat['expenses'])->map(function ($item) use ($cat, $monthlyUsed) {
                    $share = $monthlyUsed > 0 ? round(($item['amount'] / $monthlyUsed) * 100, 2) : 0;
                    return [
                        'category' => $cat['name'],
                        'item' => $item['name'],
                        'used' => $item['amount'],
                        'share_percent' => $share,
                        'status' => $share >= 30 ? 'danger' : ($share >= 20 ? 'warning' : 'safe'),
                    ];
                });
            })->filter(fn($i) => $i['used'] > 0 && $i['status'] !== 'safe')
                ->sortByDesc('used')->take(3)->values();

            return [
                'id' => $department->id,
                'name' => $department->name,
                'ratio' => (float) $department->budget_ratio_percent,
                'allocated' => $monthlyAllocated,
                'used' => $monthlyUsed,
                'utilization' => $monthlyUtilization,
                'percent_of_global' => $percentOfGlobal,
                'remaining' => max($monthlyAllocated - $monthlyUsed, 0),
                'status' => $status,
                'current_month' => $currentMonthStats,
                'monthly_breakdown' => $monthlyBreakdown,
                'categories' => $categoryBreakdown,
                'item_alerts' => $itemExpenseAlerts,
                'top_category' => $categoryBreakdown->sortByDesc('used')->first()['name'] ?? '-',
            ];
        });

        // Determine Global Budget for the Summary Card
        $globalBudgetAmount = $globalMonthly ? (float) $globalMonthly->amount : 0;

        // Summary uses the mapped 'allocated' and 'used' which are now MONTHLY.
        $summaryUsed = (float) $departments->sum('used');
        $utilization = $globalBudgetAmount > 0 ? ($summaryUsed / $globalBudgetAmount) * 100 : 0;

        // Thresholds: 0-20 warning, 21-80 success, 81-100 warning, >100 danger
        if ($utilization > 100) {
            $status = 'danger';
        } elseif ($utilization <= 20) {
            $status = 'warning';
        } elseif ($utilization <= 80) {
            $status = 'success';
        } elseif ($utilization <= 100) {
            $status = 'warning';
        } else {
            $status = 'danger';
        }

        $summary = [
            'global_budget' => $globalBudgetAmount,
            'allocated' => (float) $departments->sum('allocated'),
            'used' => $summaryUsed,
            'remaining' => max($globalBudgetAmount - $summaryUsed, 0),
            'utilization' => $utilization,
            'status' => $status
        ];

        // --- New FAT Dashboard Analytics Data ---

        // 1. Department Budget Allocation (Doughnut Chart)
        $deptAllocationData = $departments->map(function ($dept) {
            return [
                'name' => $dept['name'],
                'allocated' => $dept['allocated'],
                'used' => $dept['used'],
            ];
        })->values();

        // 2. Department Expense Contribution (Doughnut Chart)
        $deptContributionData = $departments->map(function ($dept) {
            return [
                'name' => $dept['name'],
                'used' => $dept['used']
            ];
        })->values();

        // 3. Global Monthly Trend (Line Chart)
        // We already have allGlobalMonthly for standard, we need to calculate actual for all months
        $globalTrendData = collect(range(1, 12))->map(function ($m) use ($activeFiscalYear, $year, $allGlobalMonthly) {
            $gMB = $allGlobalMonthly->get($m);
            $target = $gMB ? (float) $gMB->amount : 0;

            $actual = \App\Models\Expense::whereYear('date', $year)
                ->whereMonth('date', $m)
                ->whereHas('budgetCategory.department', function ($q) use ($activeFiscalYear) {
                    $q->where('fiscal_year_id', $activeFiscalYear->id);
                })
                ->sum('amount');

            return [
                'month' => \Carbon\Carbon::create()->month($m)->translatedFormat('M'),
                'target' => $target,
                'actual' => (float) $actual
            ];
        });

        // Determine Top Spending Department for detailed analysis
        $topDept = $departments->sortByDesc('used')->first();
        $topDeptCategoryData = collect();
        if ($topDept) {
            $topDeptCategoryData = $topDept['categories']->map(function ($cat) {
                return [
                    'name' => $cat['name'],
                    'allocated' => (float) $cat['category_budget'],
                    'used' => (float) $cat['used']
                ];
            })->values();
        }

        // Category percentage chart data (all categories across all departments)
        $globalAmount = $globalMonthly ? (float) $globalMonthly->amount : 0;
        $categoryChartData = $departments->flatMap(function ($dept) use ($globalAmount) {
            return $dept['categories']->map(function ($cat) use ($dept, $globalAmount) {
                $standardPct = (float) $cat['ratio'];
                $actualPct = $globalAmount > 0 ? round(($cat['used'] / $globalAmount) * 100, 2) : 0;
                return [
                    'label' => $cat['name'],
                    'department' => $dept['name'],
                    'standard_pct' => $standardPct,
                    'actual_pct' => $actualPct,
                    'category_budget' => (float) $cat['category_budget'],
                    'used' => (float) $cat['used'],
                ];
            });
        })->values();

        return view('dashboard.index', [
            'activeFiscalYear' => $activeFiscalYear,
            'departments' => $departments,
            'summary' => $summary,
            'userRole' => $user->role,
            'currentMonthName' => $currentMonthName,
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'selectedDepartmentId' => $selectedDepartmentId,
            'expandDepartmentId' => $request->filled('expand_department') ? (int) $request->input('expand_department') : null,
            // Chart Data
            'deptAllocationData' => $deptAllocationData,
            'deptContributionData' => $deptContributionData,
            'globalTrendData' => $globalTrendData,
            'topDept' => $topDept,
            'topDeptCategoryData' => $topDeptCategoryData,
            'categoryChartData' => $categoryChartData,
        ]);
    }
}
