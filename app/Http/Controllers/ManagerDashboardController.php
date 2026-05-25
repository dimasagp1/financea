<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Expense;
use App\Models\FiscalYear;
use App\Models\GlobalMonthlyBudget;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ManagerDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $managedDeptIds = $user->managedDepartments()->pluck('departments.id');

        $activeFiscalYear = FiscalYear::query()->where('is_active', true)->first();
        $fiscalYears = FiscalYear::query()->orderByDesc('year')->get();

        $baseDepartmentQuery = Department::query()
            ->whereIn('id', $managedDeptIds)
            ->where('fiscal_year_id', $activeFiscalYear?->id)
            ->with([
                'costCategories' => function ($query) {
                    $query->orderBy('code');
                }
            ]);

        $allDepartments = (clone $baseDepartmentQuery)->get();

        $departments = (clone $baseDepartmentQuery)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));
                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%')
                        ->orWhere('odoo_analytic_id', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name')
            ->get();

        $totalWeightAssigned = (float) $allDepartments->sum('budget_ratio_percent');
        $weightGap = 100 - $totalWeightAssigned;
        $totalDepartmentCount = $allDepartments->count();
        $displayedDepartmentCount = $departments->count();

        $currentMonthBudget = null;
        $departmentExpenses = collect();
        $deptYtdExpenses = collect(); 
        $departmentPopupData = [];
        $selectedMonth = $request->input('month', Carbon::now()->month);
        $selectedYear = $request->input('year', $activeFiscalYear?->year ?? Carbon::now()->year);

        if ($activeFiscalYear && $selectedYear != $activeFiscalYear->year) {
            $activeFiscalYear = FiscalYear::query()->where('year', $selectedYear)->first() ?: $activeFiscalYear;
        }

        $currentMonthName = Carbon::create()->year((int)$selectedYear)->month((int)$selectedMonth)->translatedFormat('F Y');

        if ($activeFiscalYear) {
            $currentMonth = $selectedMonth;
            $currentMonthBudget = GlobalMonthlyBudget::where('fiscal_year_id', $activeFiscalYear->id)
                ->where('month', $currentMonth)
                ->where('type', 'actual')
                ->first();

            $departmentExpenses = Expense::query()
                ->whereYear('date', $activeFiscalYear->year)
                ->whereMonth('date', $currentMonth)
                ->selectRaw('department_id, SUM(amount) as total_expense')
                ->groupBy('department_id')
                ->pluck('total_expense', 'department_id');

            $deptYtdExpenses = Expense::query()
                ->whereYear('date', $activeFiscalYear->year)
                ->selectRaw('department_id, SUM(amount) as total_ytd')
                ->groupBy('department_id')
                ->pluck('total_ytd', 'department_id')
                ->map(fn($v) => (float) $v);

            $categoryExpenseRows = Expense::query()
                ->whereYear('date', $activeFiscalYear->year)
                ->whereMonth('date', $currentMonth)
                ->whereIn('department_id', $allDepartments->pluck('id'))
                ->selectRaw('department_id, budget_category_id, SUM(amount) as total_expense')
                ->groupBy('department_id', 'budget_category_id')
                ->get();

            $categoryExpenseMap = $categoryExpenseRows
                ->groupBy('department_id')
                ->map(function ($rows) {
                    return $rows->pluck('total_expense', 'budget_category_id')->map(fn($value) => (float) $value);
                });

            $departmentPopupData = $allDepartments->mapWithKeys(function ($department) use ($currentMonthBudget, $activeFiscalYear, $departmentExpenses, $categoryExpenseMap, $selectedMonth) {
                $deptRatio = (float) $department->budget_ratio_percent;
                $monthAllocated = $currentMonthBudget
                    ? ((float) $currentMonthBudget->amount * $deptRatio) / 100
                    : (((float) $activeFiscalYear->global_budget_amount * $deptRatio) / 100) / 12;

                $monthUsed = (float) ($departmentExpenses->get($department->id, 0) ?? 0);
                $monthRemaining = $monthAllocated - $monthUsed;
                $monthUtilization = $monthAllocated > 0 ? round(($monthUsed / $monthAllocated) * 100, 2) : 0;
                $statusLabel = $monthUsed > $monthAllocated ? 'Overbudget / Kritis' : 'On Budget';

                $categoryRows = collect($department->costCategories)
                    ->map(function ($category) use ($department, $monthAllocated, $categoryExpenseMap, $monthUsed) {
                        $categoryUsed = (float) data_get($categoryExpenseMap, $department->id . '.' . $category->id, 0);
                        $deptRatio = (float) $department->budget_ratio_percent;
                        $catRatio = (float) $category->budget_ratio_percent;

                        $categoryBudget = $deptRatio > 0
                            ? ($monthAllocated * ($catRatio / $deptRatio))
                            : 0;

                        return [
                            'code' => (string) $category->code,
                            'name' => (string) $category->name,
                            'ratio' => $catRatio,
                            'used' => $categoryUsed,
                            'allocated' => $categoryBudget,
                            'utilization' => $categoryBudget > 0 ? round(($categoryUsed / $categoryBudget) * 100, 2) : 0,
                            'share_percent' => $monthUsed > 0 ? round(($categoryUsed / $monthUsed) * 100, 2) : 0,
                        ];
                    })
                    ->sortByDesc('used')
                    ->values();

                $topCategory = $categoryRows->first();

                $alerts = $categoryRows
                    ->filter(fn($row) => (float) ($row['utilization'] ?? 0) >= 90)
                    ->sortByDesc('utilization')
                    ->take(3)
                    ->values();

                return [
                    $department->id => [
                        'department_id' => (int) $department->id,
                        'department_name' => (string) $department->name,
                        'department_ratio' => $deptRatio,
                        'status_label' => $statusLabel,
                        'allocated' => $monthAllocated,
                        'used' => $monthUsed,
                        'remaining' => $monthRemaining,
                        'utilization' => $monthUtilization,
                        'override_month' => sprintf('%04d-%02d', (int) $activeFiscalYear->year, (int) $selectedMonth),
                        'top_category' => (string) ($topCategory['name'] ?? '-'),
                        'categories' => $categoryRows,
                        'alerts' => $alerts,
                    ],
                ];
            })->toArray();
        }

        return view('manager.dashboard', [
            'activeFiscalYear'         => $activeFiscalYear,
            'departments'              => $departments,
            'fiscalYears'              => $fiscalYears,
            'totalWeightAssigned'      => $totalWeightAssigned,
            'weightGap'                => $weightGap,
            'totalBudgetCeiling'       => $deptYtdExpenses->sum(),
            'totalDepartmentCount'     => $totalDepartmentCount,
            'displayedDepartmentCount' => $displayedDepartmentCount,
            'currentMonthBudget'       => $currentMonthBudget,
            'departmentExpenses'       => $departmentExpenses,
            'deptYtdExpenses'          => $deptYtdExpenses,
            'departmentPopupData'      => $departmentPopupData,
            'currentMonthName'         => $currentMonthName,
            'selectedMonth'            => $selectedMonth,
            'selectedYear'             => $selectedYear,
            'filters'                  => [
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }
}
