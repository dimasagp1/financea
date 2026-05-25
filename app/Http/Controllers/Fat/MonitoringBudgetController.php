<?php

namespace App\Http\Controllers\Fat;

use App\Http\Controllers\Controller;
use App\Models\BudgetCategory;
use App\Models\Department;
use App\Models\Expense;
use App\Models\FiscalYear;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MonitoringBudgetController extends Controller
{
    private function checkFatOrSuperAdmin()
    {
        if (!auth()->user()->isFAT() && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function checkDepartmentAccess($departmentId)
    {
        $user = auth()->user();
        if ($user->isDepartemen() && $user->department_id !== $departmentId) {
            abort(403, 'Unauthorized access to this department.');
        }
        
        if ($user->isManager() && !$user->managedDepartments->contains($departmentId)) {
            abort(403, 'Unauthorized access: You do not manage this department.');
        }
    }

    public function index(Request $request)
    {
        $activeFiscalYear = FiscalYear::where('is_active', true)->firstOrFail();
        $user = auth()->user();

        // --- MONTHLY CONTEXT ---
        $month = $request->input('month', now()->month);
        $year  = $request->input('year', now()->year);
        $currentDate = Carbon::createFromDate($year, $month, 1);
        $currentMonthName = $currentDate->translatedFormat('F Y');
        $selectedMonth = (int) $month;

        // Global Monthly Budget for this period
        $globalMonthly = \App\Models\GlobalMonthlyBudget::where('fiscal_year_id', $activeFiscalYear->id)
            ->where('month', (int) $month)
            ->where('year', (int) $year)
            ->where('type', 'actual')
            ->first();

        $is_fat_or_superadmin = $user->isSuperAdmin() || $user->isFAT();
        $managerDepartmentsList = collect();
        $selectedDeptId = $request->input('dept_id');

        // Load departments (all or user's own/managed)
        if ($is_fat_or_superadmin) {
            $departments = Department::where('fiscal_year_id', $activeFiscalYear->id)
                ->orderBy('name')->get();
        } elseif ($user->isManager()) {
            $managerDepartmentsList = $user->managedDepartments()
                ->where('fiscal_year_id', $activeFiscalYear->id)
                ->orderBy('name')->get();
            
            if ($selectedDeptId) {
                $departments = $managerDepartmentsList->where('id', $selectedDeptId);
            } else {
                $departments = $managerDepartmentsList->take(1);
            }
        } else {
            $departments = Department::where('id', $user->department_id)->get();
        }

        // All expenses for this month across all departments
        $allExpenses = Expense::whereYear('date', $currentDate->year)
            ->whereMonth('date', $currentDate->month)
            ->whereIn('department_id', $departments->pluck('id'))
            ->get();

        $expensesByDeptCat = $allExpenses->groupBy('department_id')
            ->map(fn($rows) => $rows->groupBy('budget_category_id')
                ->map(fn($catRows) => $catRows->sum('amount')));

        $expensesByDept = $allExpenses->groupBy('department_id')
            ->map(fn($rows) => $rows->sum('amount'));

        // Build data per department
        $grandBudget = 0;
        $grandUsed   = 0;

        $departmentsData = $departments->map(function ($dept) use (
            $globalMonthly,
            $expensesByDeptCat, $expensesByDept,
            &$grandBudget, &$grandUsed
        ) {
            // Pagu for this department
            $paguBaseline = $globalMonthly
                ? ($globalMonthly->amount * $dept->budget_ratio_percent) / 100
                : 0;
            $monthlyBudgetAmount = $paguBaseline;

            $deptUsed = (float) ($expensesByDept->get($dept->id, 0));
            $deptRemaining = $monthlyBudgetAmount - $deptUsed;
            $deptUtilization = $monthlyBudgetAmount > 0
                ? round(($deptUsed / $monthlyBudgetAmount) * 100, 2)
                : 0;

            $grandBudget += $monthlyBudgetAmount;
            $grandUsed   += $deptUsed;

            // Load categories for this department
            $categories = BudgetCategory::where('department_id', $dept->id)
                ->where('fiscal_year_id', $dept->fiscal_year_id)
                ->orderBy('code')
                ->get()
                ->map(function ($cat) use ($monthlyBudgetAmount, $dept, $expensesByDeptCat) {
                    $ratioFraction = $dept->budget_ratio_percent > 0
                        ? ($cat->budget_ratio_percent / $dept->budget_ratio_percent)
                        : 0;
                    $cat->calculated_budget = $monthlyBudgetAmount * $ratioFraction;
                    $catActual = 0;
                    $deptExpenses = $expensesByDeptCat->get($dept->id);
                    if ($deptExpenses) {
                        $catActual = (float) $deptExpenses->get($cat->id, 0);
                    }
                    $cat->total_used = $catActual;
                    $cat->utilization = $cat->calculated_budget > 0
                        ? ($cat->total_used / $cat->calculated_budget) * 100
                        : 0;
                    $cat->remaining = $cat->calculated_budget - $cat->total_used;

                    if ($cat->utilization > 100) $cat->status = 'danger';
                    elseif ($cat->utilization <= 20) $cat->status = 'warning';
                    elseif ($cat->utilization <= 80) $cat->status = 'success';
                    else $cat->status = 'warning';

                    return $cat;
                });

            $dept->enriched_categories = $categories;
            $dept->monthly_budget = $monthlyBudgetAmount;
            $dept->monthly_used   = $deptUsed;
            $dept->monthly_remaining = $deptRemaining;
            $dept->monthly_utilization = $deptUtilization;

            return $dept;
        });

        $grandSummary = [
            'total_budget'    => $grandBudget,
            'total_used'      => $grandUsed,
            'total_remaining' => $grandBudget - $grandUsed,
        ];

        $departmentPopupData = $departmentsData->mapWithKeys(function ($dept) use ($year, $month) {
            $categories = collect($dept->enriched_categories ?? [])->map(function ($cat) use ($dept) {
                $used = (float) ($cat->total_used ?? 0);
                $deptUsed = (float) ($dept->monthly_used ?? 0);

                return [
                    'id' => (int) $cat->id,
                    'code' => (string) $cat->code,
                    'name' => (string) $cat->name,
                    'ratio' => (float) $cat->budget_ratio_percent,
                    'used' => $used,
                    'allocated' => (float) ($cat->calculated_budget ?? 0),
                    'utilization' => (float) ($cat->utilization ?? 0),
                    'share_percent' => $deptUsed > 0 ? round(($used / $deptUsed) * 100, 2) : 0,
                ];
            })->values();

            $alerts = $categories
                ->filter(fn($row) => (float) ($row['utilization'] ?? 0) >= 90)
                ->sortByDesc('utilization')
                ->take(3)
                ->values();

            return [
                $dept->id => [
                    'department_id' => (int) $dept->id,
                    'department_name' => (string) $dept->name,
                    'department_ratio' => (float) $dept->budget_ratio_percent,
                    'status_label' => ((float) $dept->monthly_used > (float) $dept->monthly_budget) ? 'Overbudget / Kritis' : 'On Budget',
                    'allocated' => (float) $dept->monthly_budget,
                    'used' => (float) $dept->monthly_used,
                    'remaining' => (float) $dept->monthly_remaining,
                    'utilization' => (float) $dept->monthly_utilization,
                    'override_month' => sprintf('%04d-%02d', (int) $year, (int) $month),
                    'top_category' => (string) ($categories->sortByDesc('used')->first()['name'] ?? '-'),
                    'categories' => $categories,
                    'alerts' => $alerts,
                ],
            ];
        })->toArray();

        return view('fat.monitoring.index', [
            'activeFiscalYear'     => $activeFiscalYear,
            'departmentsData'      => $departmentsData,
            'grandSummary'         => $grandSummary,
            'currentMonthName'     => $currentMonthName,
            'selectedMonth'        => $selectedMonth,
            'selectedYear'         => $year,
            'globalMonthly'        => $globalMonthly,
            'is_fat_or_superadmin' => $is_fat_or_superadmin,
            'departmentPopupData'  => $departmentPopupData,
            'managerDepartmentsList' => $managerDepartmentsList,
        ]);
    }

    public function storeCategory(Request $request)
    {
        $this->checkFatOrSuperAdmin();

        $department = Department::findOrFail($request->department_id);

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'budget_ratio_percent' => ['required', 'numeric', 'min:0', 'max:' . $department->budget_ratio_percent],
            'description' => 'nullable|string',
        ]);

        // Validate uniqueness of code per department & fiscal year
        $activeYear = FiscalYear::where('is_active', true)->firstOrFail();

        $exists = BudgetCategory::where('department_id', $validated['department_id'])
            ->where('fiscal_year_id', $activeYear->id)
            ->where('code', $validated['code'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['code' => 'Kode kategori ini sudah ada di departemen ini.']);
        }

        BudgetCategory::create([
            'department_id' => $validated['department_id'],
            'fiscal_year_id' => $activeYear->id,
            'code' => $validated['code'],
            'name' => $validated['name'],
            'budget_ratio_percent' => $validated['budget_ratio_percent'],
            'description' => $validated['description'],
        ]);

        return back()->with('success', 'Kategori budget berhasil ditambahkan.');
    }

    public function updateCategory(Request $request, BudgetCategory $category)
    {
        $this->checkFatOrSuperAdmin();

        $validated = $request->validate([
            'name'                 => 'sometimes|required|string|max:255',
            'budget_ratio_percent' => ['required', 'numeric', 'min:0', 'max:' . $category->department->budget_ratio_percent],
            'description'          => 'nullable|string',
        ]);

        $fillable = ['budget_ratio_percent' => $validated['budget_ratio_percent']];
        if (isset($validated['name']))        $fillable['name']        = $validated['name'];
        if (array_key_exists('description', $validated)) $fillable['description'] = $validated['description'];

        $category->update($fillable);

        // Recalculate dept ratio as sum of all category ratios
        $dept = $category->department;
        $newDeptRatio = (float) $dept->budgetCategories()->sum('budget_ratio_percent');
        $dept->update(['budget_ratio_percent' => $newDeptRatio]);

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success'         => true,
                'new_dept_ratio'  => round($newDeptRatio, 2),
                'dept_id'         => $dept->id,
            ]);
        }
        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroyCategory(BudgetCategory $category)
    {
        $this->checkFatOrSuperAdmin();

        if ($category->expenses()->exists()) {
            return back()->withErrors(['Kategori ini sudah memiliki data pengeluaran dan tidak bisa dihapus.']);
        }

        $category->delete();
        return back()->with('success', 'Kategori berhasil dihapus.');
    }

    public function storeExpense(Request $request)
    {
        $validated = $request->validate([
            'budget_category_id' => 'required|exists:budget_categories,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string|max:255',
        ]);

        $category = BudgetCategory::findOrFail($validated['budget_category_id']);
        $this->checkDepartmentAccess($category->department_id);

        // TODO: Validate amount against Monthly Budget Remaining?
        // User didn't strictly ask for validation failure, just "monitoring".
        // But warning might be good. For now, allow over-budget.

        Expense::create([
            'department_id' => $category->department_id,
            'budget_category_id' => $category->id,
            'date' => $validated['date'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            // Default fields
            'qty' => 1,
        ]);

        return back()->with('success', 'Realisasi anggaran berhasil dicatat.');
    }

    public function show(Request $request, BudgetCategory $category)
    {
        $this->checkDepartmentAccess($category->department_id);
        $user = auth()->user();
        $activeYear = FiscalYear::where('is_active', true)->firstOrFail();

        // Determine "Content Month".
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $currentDate = Carbon::createFromDate($year, $month, 1);
        $monthName = $currentDate->translatedFormat('F Y');

        // DYNAMIC ALLOCATION LOGIC (Phase 1)
        // 1. Fetch Global Monthly Budget for SELECTED MONTH
        $globalMonthly = \App\Models\GlobalMonthlyBudget::where('fiscal_year_id', $activeYear->id)
            ->where('month', (int) $month)
            ->where('year', (int) $year)
            ->where('type', 'actual')
            ->first();

        // 2. Calculate Baseline Pagu (Global Amount * Dept Ratio %)
        $baselinePagu = $globalMonthly
            ? ($globalMonthly->amount * $category->department->budget_ratio_percent) / 100
            : 0;

        $monthlyBudgetAmount = $baselinePagu;

        // Calculate budget for this specific category
        $ratioFraction = $category->department->budget_ratio_percent > 0 
            ? ($category->budget_ratio_percent / $category->department->budget_ratio_percent) 
            : 0;
        $category->calculated_budget = $monthlyBudgetAmount * $ratioFraction;

        // Load filtered expenses
        $expenses = $category->expenses()
            ->whereYear('date', $currentDate->year)
            ->whereMonth('date', $currentDate->month)
            ->latest('date')
            ->get();

        $category->total_used = $expenses->sum('amount');
        $category->remaining = $category->calculated_budget - $category->total_used;
        $category->utilization = $category->calculated_budget > 0 ? ($category->total_used / $category->calculated_budget) * 100 : 0;

        // Utilization threshold:
        // 0-20 warning, 21-80 success, 81-100 warning, >100 danger
        if ($category->utilization > 100) {
            $category->status = 'danger';
        } elseif ($category->utilization <= 20) {
            $category->status = 'warning';
        } elseif ($category->utilization <= 80) {
            $category->status = 'success';
        } elseif ($category->utilization <= 100) {
            $category->status = 'warning';
        } else {
            $category->status = 'danger';
        }

        return view('fat.monitoring.show', [
            'category' => $category,
            'expenses' => $expenses,
            'activeFiscalYear' => $activeYear,
            'is_fat_or_superadmin' => ($user->isFAT() || $user->isSuperAdmin()),
            'currentMonthName' => $monthName,
            'selectedMonth' => $month,
            'selectedYear' => $year
        ]);
    }

    public function updateExpense(Request $request, Expense $expense)
    {
        $this->checkDepartmentAccess($expense->department_id);

        $validated = $request->validate([
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string|max:255',
        ]);

        $expense->update($validated);

        return back()->with('success', 'Pengeluaran berhasil diperbarui.');
    }

    public function destroyExpense(Expense $expense)
    {
        $this->checkDepartmentAccess($expense->department_id);

        $expense->delete();

        return back()->with('success', 'Pengeluaran berhasil dihapus.');
    }
}
