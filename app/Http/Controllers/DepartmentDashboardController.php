<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\FiscalYear;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DepartmentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        if (!$user->isDepartemen()) {
            return redirect()->route('dashboard.index');
        }

        $activeFiscalYear = FiscalYear::query()->where('is_active', true)->first();

        if (!$activeFiscalYear || !$user->department_id) {
            return view('departemen.dashboard', [
                'department' => null,
                'summary' => [
                    'allocated' => 0,
                    'used' => 0,
                    'remaining' => 0,
                    'utilization' => 0,
                    'status' => 'warning',
                ],
                'monthlyLabels' => [],
                'monthlyUsageData' => [],
                'categoryItems' => collect(),
                'categoryExpenseSummary' => collect(),
                'itemExpenseAlerts' => collect(),
                'recentDepartmentSpending' => collect(),
                'pendingApprovals' => 0,
                'topCategoryName' => '-',
                'myBudgetKpis' => [
                    'annual_budget' => 0,
                    'actual_spending' => 0,
                    'monthly_variance' => 0,
                    'remaining_runway' => 0,
                    'actual_percent' => 0,
                    'variance_note' => '-',
                ],
                'myBudgetCategoryColumns' => collect(),
                'myBudgetMonthlyRows' => collect(),
                'myBudgetTotals' => [
                    'planned' => 0,
                    'actual' => 0,
                    'variance' => 0,
                    'categories' => [],
                ],
                'myBudgetTopDrivers' => collect(),
                'myBudgetForecast' => [
                    'projected_year_end' => 0,
                    'variance_amount' => 0,
                    'variance_percent' => 0,
                    'status' => 'safe',
                ],
                'myBudgetAvailableMonths' => collect(),
                'myBudgetAvailableCategories' => collect(),
                'myBudgetFilters' => [
                    'search' => '',
                    'month' => '',
                    'budget_category' => 'all',
                ],
                'activeFiscalYear' => $activeFiscalYear,
            ]);
        }

        $department = Department::query()
            ->with([
                'expenses.budgetCategory',
                'monthlyBudgets',
                'budgetCategories.expenses',
            ])
            ->where('id', $user->department_id)
            ->where('fiscal_year_id', $activeFiscalYear->id)
            ->first();

        if (!$department) {
            abort(404, 'Data departemen tidak ditemukan pada fiscal year aktif.');
        }

        // --- DYNAMIC ALLOCATION LOGIC (Phase 1) ---
        $currentMonthInt = now()->month;
        $currentYearInt = now()->year;

        // 1. Fetch Global Monthly Budget for THIS MONTH
        $globalMonthly = \App\Models\GlobalMonthlyBudget::where('fiscal_year_id', $activeFiscalYear->id)
            ->where('month', $currentMonthInt)
            ->where('year', $currentYearInt)
            ->first();

        // 2. Calculate Baseline Pagu (Global Amount * Dept Ratio %)
        $baselinePagu = $globalMonthly
            ? ($globalMonthly->amount * $department->budget_ratio_percent) / 100
            : 0;

        // 3. Check for specific Month Override in MonthlyBudget
        $monthStr = sprintf('%04d-%02d', $currentYearInt, $currentMonthInt);
        $monthlyForecast = $department->monthlyBudgets->where('month', $monthStr)->first();

        // Final Allocated for THIS MONTH
        $allocated = $monthlyForecast ? $monthlyForecast->amount : $baselinePagu;

        // Realization (Total used THIS MONTH)
        $used = (float) $department->expenses
            ->filter(fn($e) => $e->date->month == $currentMonthInt && $e->date->year == $currentYearInt)
            ->sum('amount');

        $utilization = $allocated > 0 ? round(($used / $allocated) * 100, 2) : 0;
        $percentOfGlobal = ($globalMonthly && $globalMonthly->amount > 0) ? round(($used / $globalMonthly->amount) * 100, 2) : 0;

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

        $months = collect(range(1, 12))->map(fn($month) => Carbon::createFromDate((int) $activeFiscalYear->year, $month, 1));
        
        $monthlyLabels = $months->map(fn(Carbon $date) => $date->locale('id')->translatedFormat('M Y'));

        $monthlyUsageData = $months->map(function (Carbon $date) use ($department) {
            $start = $date->copy()->startOfMonth()->toDateString();
            $end = $date->copy()->endOfMonth()->toDateString();

            return (float) $department->expenses
                ->filter(function($expense) use ($start, $end) {
                    $dateStr = \Carbon\Carbon::parse($expense->date)->toDateString();
                    return $dateStr >= $start && $dateStr <= $end;
                })
                ->sum('amount');
        });

        // Generate Category breakdown for the Bar Chart (Current Month Only)
        $currentMonthStart = Carbon::now()->startOfMonth()->toDateString();
        $currentMonthEnd = Carbon::now()->endOfMonth()->toDateString();
        
        $categoryCurrentMonthData = $department->budgetCategories->map(function ($category) use ($department, $currentMonthStart, $currentMonthEnd) {
            $totalUsed = (float) $department->expenses
                ->where('budget_category_id', $category->id)
                ->filter(function($expense) use ($currentMonthStart, $currentMonthEnd) {
                    $dateStr = \Carbon\Carbon::parse($expense->date)->toDateString();
                    return $dateStr >= $currentMonthStart && $dateStr <= $currentMonthEnd;
                })
                ->sum('amount');

            return [
                'label' => $category->name,
                'used' => $totalUsed,
            ];
        })->values()->toArray();

        $categoryItems = $department->budgetCategories->map(function ($category) {
            $items = $category->expenses->map(function ($expense) {
                return [
                    'name' => $expense->description,
                    'account_code' => $expense->reference,
                    'used' => (float) $expense->amount,
                    'qty' => (float) $expense->qty,
                    'unit_price' => (float) ($expense->qty > 0
                        ? ($expense->amount / $expense->qty)
                        : 0),
                ];
            });

            return [
                'name'                => $category->name,
                'budget_ratio_percent'=> (float) $category->budget_ratio_percent,
                'total' => (float) $items->sum('used'),
                'qty_total' => (float) $items->sum('qty'),
                'items' => $items,
            ];
        });

        $categoryExpenseSummary = $categoryItems->map(function ($category) use ($used) {
            $total = (float) ($category['total'] ?? 0);

            return [
                'name'                => $category['name'],
                'used'                => $total,
                'qty_total'           => (float) ($category['qty_total'] ?? 0),
                'share_percent'       => $used > 0 ? round(($total / $used) * 100, 2) : 0,
                'budget_ratio_percent'=> $category['budget_ratio_percent'] ?? 0,
            ];
        });

        $itemExpenseAlerts = $categoryItems
            ->flatMap(function ($category) use ($used) {
                return collect($category['items'])->map(function ($item) use ($category, $used) {
                    $itemUsed = (float) ($item['used'] ?? 0);
                    $share = $used > 0 ? round(($itemUsed / $used) * 100, 2) : 0;

                    return [
                        'category' => $category['name'],
                        'item' => $item['name'],
                        'used' => $itemUsed,
                        'qty' => (float) ($item['qty'] ?? 0),
                        'share_percent' => $share,
                        'status' => $share >= 30 ? 'danger' : ($share >= 20 ? 'warning' : 'safe'),
                    ];
                });
            })
            ->filter(fn($item) => $item['used'] > 0 && $item['status'] !== 'safe')
            ->sortByDesc('used')
            ->take(5)
            ->values();

        $recentDepartmentSpending = $department->expenses
            ->sortByDesc('date')
            ->take(6)
            ->map(function ($expense) {
                return [
                    'description' => $expense->description,
                    'category' => $expense->budgetCategory?->name ?? '-',
                    'date' => $expense->date,
                    'status' => $expense->is_synced ? 'approved' : 'pending',
                    'amount' => (float) $expense->amount,
                ];
            })
            ->values();

        $pendingApprovals = (int) $recentDepartmentSpending
            ->where('status', 'pending')
            ->count();

        $topCategory = $categoryExpenseSummary->sortByDesc('used')->first();
        $topCategoryName = is_array($topCategory) ? (string) ($topCategory['name'] ?? '-') : '-';

        $myBudgetCategoryColumns = $categoryExpenseSummary
            ->sortByDesc('used')
            ->take(4)
            ->pluck('name')
            ->values();

        $myBudgetAvailableCategories = collect(['all'])
            ->merge($categoryExpenseSummary->pluck('name'))
            ->values();

        $monthlyRows = $months
            ->reverse()
            ->map(function (Carbon $monthDate) use ($department, $myBudgetCategoryColumns, $activeFiscalYear) {
                $monthKey = $monthDate->format('Y-m');
                $mInt = (int) $monthDate->month;
                $yInt = (int) $monthDate->year;

                // 1. Check for manual override
                $plannedMB = $department->monthlyBudgets->where('month', $monthKey)->first();

                if ($plannedMB) {
                    $planned = (float) $plannedMB->amount;
                } else {
                    // 2. Lookup Global Monthly for that specific month
                    $globalMB = \App\Models\GlobalMonthlyBudget::where('fiscal_year_id', $activeFiscalYear->id)
                        ->where('month', $mInt)
                        ->where('year', $yInt)
                        ->first();

                    $planned = $globalMB
                        ? ($globalMB->amount * $department->budget_ratio_percent) / 100
                        : 0; // No fallback to yearly / 12
                }

                $monthStart = $monthDate->copy()->startOfMonth()->toDateString();
                $monthEnd = $monthDate->copy()->endOfMonth()->toDateString();

                $monthExpenses = $department->expenses
                    ->filter(function($expense) use ($mInt, $yInt) {
                        $parsed = \Carbon\Carbon::parse($expense->date);
                        return $parsed->month === $mInt && $parsed->year === $yInt;
                    });

                $actual = (float) $monthExpenses->sum('amount');
                $variance = $actual - $planned;

                $categoryValues = $myBudgetCategoryColumns->mapWithKeys(function ($categoryName) use ($monthExpenses) {
                    $total = (float) $monthExpenses
                        ->filter(fn($expense) => ($expense->budgetCategory?->name ?? null) === $categoryName)
                        ->sum('amount');

                    return [(string) $categoryName => $total];
                })->toArray();

                return [
                    'month_key' => $monthKey,
                    'month_label' => $monthDate->locale('id')->translatedFormat('F Y'),
                    'planned' => $planned,
                    'actual' => $actual,
                    'variance' => $variance,
                    'categories' => $categoryValues,
                ];
            })
            ->values();

        $myBudgetAvailableMonths = $monthlyRows
            ->map(fn($row) => ['key' => $row['month_key'], 'label' => $row['month_label']])
            ->values();

        $myBudgetSearch = trim($request->string('search')->toString());
        $myBudgetMonthFilter = $request->string('month')->toString();
        $myBudgetCategoryFilter = $request->string('budget_category')->toString() ?: 'all';

        if (!$myBudgetAvailableCategories->contains($myBudgetCategoryFilter)) {
            $myBudgetCategoryFilter = 'all';
        }

        if ($myBudgetCategoryFilter !== 'all' && $myBudgetAvailableCategories->contains($myBudgetCategoryFilter)) {
            $myBudgetCategoryColumns = collect([$myBudgetCategoryFilter]);
        }

        $filteredMonthlyRows = $monthlyRows
            ->when($myBudgetMonthFilter !== '', function ($rows) use ($myBudgetMonthFilter) {
                return $rows->where('month_key', $myBudgetMonthFilter);
            })
            ->when($myBudgetSearch !== '', function ($rows) use ($myBudgetSearch) {
                $needle = mb_strtolower($myBudgetSearch);

                return $rows->filter(function ($row) use ($needle) {
                    $monthLabel = mb_strtolower((string) ($row['month_label'] ?? ''));
                    if (str_contains($monthLabel, $needle)) {
                        return true;
                    }

                    $categoryKeys = collect(array_keys($row['categories'] ?? []))
                        ->map(fn($name) => mb_strtolower((string) $name));

                    return $categoryKeys->contains(fn($name) => str_contains($name, $needle));
                });
            })
            ->values();

        $myBudgetMonthlyRows = $filteredMonthlyRows->take(6)->values();

        $myBudgetTotals = [
            'planned' => (float) $myBudgetMonthlyRows->sum('planned'),
            'actual' => (float) $myBudgetMonthlyRows->sum('actual'),
            'variance' => (float) $myBudgetMonthlyRows->sum('variance'),
            'categories' => $myBudgetCategoryColumns->mapWithKeys(function ($categoryName) use ($myBudgetMonthlyRows) {
                $total = (float) $myBudgetMonthlyRows
                    ->sum(fn($row) => (float) ($row['categories'][$categoryName] ?? 0));

                return [(string) $categoryName => $total];
            })->toArray(),
        ];

        // Calculate YTD and Projection properly from $monthlyRows
        $ytdUsed = $monthlyRows->sum('actual');
        $ytdPlanned = $monthlyRows->sum('planned');

        $currentMonth = (int) now()->month;
        $ytdActual = (float) $department->expenses
            ->filter(fn($e) => $e->date->year == $currentYearInt && $e->date->month <= $currentMonth)
            ->sum('amount');

        // Projection logic: (YTD Actual / Months Passed) * 12
        $averageMonthlySpend = $currentMonth > 0 ? $ytdActual / $currentMonth : 0;
        $projectedYearEnd = $averageMonthlySpend * 12;

        // Use a reasonable baseline for the projected variance (Compare projected spend vs Total Annual Budget)
        $annualBudget = (float) $department->yearly_allocated_amount;
        $forecastVariance = $projectedYearEnd - $annualBudget;
        $forecastPercent = $annualBudget > 0 ? round(($forecastVariance / $annualBudget) * 100, 2) : 0;

        $currentMonthDate = Carbon::createFromDate((int) $activeFiscalYear->year, now()->month, 1)->locale('id');
        $currentMonthRow = $monthlyRows->firstWhere('month_key', now()->format('Y-m'));
        $monthlyVariance = (float) ($currentMonthRow['variance'] ?? 0);

        $myBudgetKpis = [
            'annual_budget' => $allocated, // Note: Label in Blade is "Total Anggaran Tahunan" but we use monthly for focus
            'actual_spending' => $used,
            'monthly_variance' => $monthlyVariance,
            'remaining_runway' => max($allocated - $used, 0),
            'actual_percent' => $allocated > 0 ? round(($used / $allocated) * 100, 2) : 0,
        ];

        $myBudgetKpis['variance_note'] = $monthlyVariance >= 0
            ? 'Melebihi jatah ' . $currentMonthDate->translatedFormat('F')
            : 'Di bawah jatah ' . $currentMonthDate->translatedFormat('F');

        $myBudgetTopDrivers = $categoryItems
            ->flatMap(fn($category) => collect($category['items'])->map(function ($item) use ($used) {
                $value = (float) ($item['used'] ?? 0);
                return [
                    'label' => $item['name'],
                    'amount' => $value,
                    'share_percent' => $used > 0 ? round(($value / $used) * 100, 2) : 0,
                ];
            }))
            ->filter(fn($row) => $row['amount'] > 0)
            ->sortByDesc('amount')
            ->take(3)
            ->values();

        $myBudgetForecast = [
            'projected_year_end' => $projectedYearEnd,
            'variance_amount' => $forecastVariance,
            'variance_percent' => $forecastPercent,
            'status' => $forecastVariance > 0 ? 'danger' : 'safe',
        ];

        return view('departemen.dashboard', [
            'department' => $department,
            'summary' => [
                'allocated' => $allocated,
                'used' => $used,
                'remaining' => max($allocated - $used, 0),
                'utilization' => $utilization,
                'percent_of_global' => $percentOfGlobal,
                'status' => $status,
            ],
            'monthlyLabels' => $monthlyLabels,
            'monthlyUsageData' => $monthlyUsageData,
            'categoryCurrentMonthData' => $categoryCurrentMonthData,
            'categoryItems' => $categoryItems,
            'categoryExpenseSummary' => $categoryExpenseSummary,
            'itemExpenseAlerts' => $itemExpenseAlerts,
            'recentDepartmentSpending' => $recentDepartmentSpending,
            'pendingApprovals' => $pendingApprovals,
            'topCategoryName' => $topCategoryName,
            'myBudgetKpis' => $myBudgetKpis,
            'myBudgetCategoryColumns' => $myBudgetCategoryColumns,
            'myBudgetMonthlyRows' => $myBudgetMonthlyRows,
            'myBudgetTotals' => $myBudgetTotals,
            'myBudgetTopDrivers' => $myBudgetTopDrivers,
            'myBudgetForecast' => $myBudgetForecast,
            'myBudgetAvailableMonths' => $myBudgetAvailableMonths,
            'myBudgetAvailableCategories' => $myBudgetAvailableCategories,
            'myBudgetFilters' => [
                'search' => $myBudgetSearch,
                'month' => $myBudgetMonthFilter,
                'budget_category' => $myBudgetCategoryFilter,
            ],
            'activeFiscalYear' => $activeFiscalYear,
        ]);
    }
}
