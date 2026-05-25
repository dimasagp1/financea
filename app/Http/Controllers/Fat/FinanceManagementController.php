<?php

namespace App\Http\Controllers\Fat;

use App\Http\Controllers\Controller;
use App\Models\BudgetCategory;

use App\Models\Department;
use App\Models\Expense;
use App\Models\FiscalYear;
use App\Models\GlobalMonthlyBudget;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FinanceManagementController extends Controller
{
    public function departments(Request $request)
    {
        $activeFiscalYear = FiscalYear::query()->where('is_active', true)->first();
        $fiscalYears = FiscalYear::query()->orderByDesc('year')->get();

        $baseDepartmentQuery = Department::query()
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
        $deptYtdExpenses = collect(); // YTD actual expenses per dept
        $departmentPopupData = [];
        $selectedMonth = $request->input('month', Carbon::now()->month);
        $selectedYear = $request->input('year', $activeFiscalYear?->year ?? Carbon::now()->year);

        if ($activeFiscalYear && $selectedYear != $activeFiscalYear->year) {
            $activeFiscalYear = FiscalYear::query()->where('year', $selectedYear)->first() ?: $activeFiscalYear;
            // Overwrite active to be the selected year's data for calculations
        }

        $currentMonthName = Carbon::create()->year((int)$selectedYear)->month((int)$selectedMonth)->translatedFormat('F Y');

        if ($activeFiscalYear) {
            $currentMonth = $selectedMonth;
            $currentMonthBudget = GlobalMonthlyBudget::where('fiscal_year_id', $activeFiscalYear->id)
                ->where('month', $currentMonth)
                ->where('type', 'actual')
                ->first();

            // Calculate current month expenses per department
            $departmentExpenses = Expense::query()
                ->whereYear('date', $activeFiscalYear->year)
                ->whereMonth('date', $currentMonth)
                ->selectRaw('department_id, SUM(amount) as total_expense')
                ->groupBy('department_id')
                ->pluck('total_expense', 'department_id');

            // YTD actual expenses per department (from start of fiscal year to now)
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


        return view('fat.departments.index', [
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

    public function storeDepartment(Request $request)
    {
        $activeFiscalYear = FiscalYear::query()->where('is_active', true)->firstOrFail();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:departments,code'],
            'name' => ['required', 'string', 'max:255'],
            'budget_ratio_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'odoo_analytic_id' => ['nullable', 'string', 'max:100'],
            'head_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        Department::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'budget_ratio_percent' => (float) $validated['budget_ratio_percent'],
            'yearly_allocated_amount' => ($activeFiscalYear->global_budget_amount * (float) $validated['budget_ratio_percent']) / 100,
            'odoo_analytic_id' => $validated['odoo_analytic_id'] ?? null,
            'fiscal_year_id' => $activeFiscalYear->id,
            'head_name' => $validated['head_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Departemen berhasil ditambahkan.');
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:departments,code,' . $department->id],
            'name' => ['required', 'string', 'max:255'],
            'budget_ratio_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'odoo_analytic_id' => ['nullable', 'string', 'max:100'],
            'head_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $globalBudget = (float) optional($department->fiscalYear)->global_budget_amount;

        $department->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'budget_ratio_percent' => (float) $validated['budget_ratio_percent'],
            'yearly_allocated_amount' => ($globalBudget * (float) $validated['budget_ratio_percent']) / 100,
            'odoo_analytic_id' => $validated['odoo_analytic_id'] ?? null,
            'head_name' => $validated['head_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return back()->with('success', 'Departemen berhasil diperbarui.');
    }

    public function destroyDepartment(Department $department)
    {
        if ($department->users()->exists() || $department->expenses()->exists()) {
            return back()->withErrors(['Departemen memiliki relasi user/realisasi dan tidak bisa dihapus.']);
        }

        $department->delete();

        return back()->with('success', 'Departemen berhasil dihapus.');
    }

    public function fiscalYears()
    {
        $fiscalYears = FiscalYear::query()->latest('year')->paginate(12);

        return view('fat.fiscal-years.index', compact('fiscalYears'));
    }

    public function storeFiscalYear(Request $request)
    {
        $validated = $request->validate([
            'year' => ['required', 'digits:4', 'unique:fiscal_years,year'],
            'global_budget_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        FiscalYear::create([
            'year' => $validated['year'],
            'global_budget_amount' => $validated['global_budget_amount'],
            'is_active' => false,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Fiscal year berhasil ditambahkan.');
    }

    public function updateFiscalYear(Request $request, FiscalYear $fiscalYear)
    {
        $validated = $request->validate([
            'year' => ['required', 'digits:4', 'unique:fiscal_years,year,' . $fiscalYear->id],
            'global_budget_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $fiscalYear->update([
            'year' => $validated['year'],
            'global_budget_amount' => $validated['global_budget_amount'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Fiscal year berhasil diperbarui.');
    }

    public function activateFiscalYear(FiscalYear $fiscalYear)
    {
        FiscalYear::query()->update(['is_active' => false]);
        $fiscalYear->update(['is_active' => true]);

        return back()->with('success', 'Fiscal year aktif berhasil diperbarui.');
    }

    public function users()
    {
        $departments = Department::query()->orderBy('name')->get();
        $users = User::query()->with(['department', 'managedDepartments'])->latest()->paginate(12);

        return view('fat.users.index', compact('users', 'departments'));
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'in:superadmin,fat,departemen,manager'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'manager_department_ids' => ['nullable', 'array'],
            'manager_department_ids.*' => ['exists:departments,id'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'department_id' => $validated['role'] === 'departemen' ? ($validated['department_id'] ?? null) : null,
            'is_active' => true,
        ]);

        if ($validated['role'] === 'manager' && !empty($validated['manager_department_ids'])) {
            $user->managedDepartments()->sync($validated['manager_department_ids']);
        }

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', 'in:superadmin,fat,departemen,manager'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'manager_department_ids' => ['nullable', 'array'],
            'manager_department_ids.*' => ['exists:departments,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'department_id' => $validated['role'] === 'departemen' ? ($validated['department_id'] ?? null) : null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);

        if ($validated['role'] === 'manager') {
            $user->managedDepartments()->sync($validated['manager_department_ids'] ?? []);
        } else {
            $user->managedDepartments()->detach();
        }

        return back()->with('success', 'User berhasil diperbarui.');
    }

    public function destroyUser(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->withErrors(['Akun yang sedang login tidak dapat dihapus.']);
        }

        $user->delete();

        return back()->with('success', 'User berhasil dihapus.');
    }

    // ───────────────────────────────────────────────────────────────
    // Category CRUD (per Department)
    // ───────────────────────────────────────────────────────────────

    public function storeCategory(Request $request, Department $department)
    {
        $activeYear = FiscalYear::where('is_active', true)->firstOrFail();

        $validated = $request->validate([
            'code'                 => 'required|string|max:50',
            'name'                 => 'required|string|max:255',
            'budget_ratio_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'description'          => 'nullable|string',
        ]);

        $exists = BudgetCategory::where('department_id', $department->id)
            ->where('fiscal_year_id', $activeYear->id)
            ->where('code', $validated['code'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['code' => 'Kode kategori ini sudah ada di departemen ini.']);
        }

        $category = BudgetCategory::create([
            'department_id'        => $department->id,
            'fiscal_year_id'       => $activeYear->id,
            'code'                 => $validated['code'],
            'name'                 => $validated['name'],
            'budget_ratio_percent' => $validated['budget_ratio_percent'],
            'description'          => $validated['description'] ?? null,
        ]);

        $newDeptRatio = (float) $department->budgetCategories()->sum('budget_ratio_percent');
        $department->update(['budget_ratio_percent' => $newDeptRatio]);

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Kategori cost berhasil ditambahkan.',
                'dept_id' => (int) $department->id,
                'new_dept_ratio' => round($newDeptRatio, 2),
                'category' => [
                    'id' => (int) $category->id,
                    'code' => (string) $category->code,
                    'name' => (string) $category->name,
                    'budget_ratio_percent' => (float) $category->budget_ratio_percent,
                    'description' => (string) ($category->description ?? ''),
                ],
            ]);
        }

        return back()->with('success', 'Kategori cost berhasil ditambahkan.');
    }

    public function updateCategory(Request $request, BudgetCategory $category)
    {
        $validated = $request->validate([
            'code'                 => 'sometimes|required|string|max:50',
            'name'                 => 'sometimes|required|string|max:255',
            'budget_ratio_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'description'          => 'nullable|string',
        ]);

        $fillable = ['budget_ratio_percent' => $validated['budget_ratio_percent']];
        if (isset($validated['code']))        $fillable['code']        = $validated['code'];
        if (isset($validated['name']))        $fillable['name']        = $validated['name'];
        if (array_key_exists('description', $validated)) $fillable['description'] = $validated['description'];

        $category->update($fillable);

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Kategori cost berhasil diperbarui.');
    }

    public function destroyCategory(BudgetCategory $category)
    {
        if ($category->expenses()->exists()) {
            if (request()->wantsJson() || request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kategori ini sudah memiliki data pengeluaran dan tidak bisa dihapus.',
                ], 422);
            }

            return back()->withErrors(['Kategori ini sudah memiliki data pengeluaran dan tidak bisa dihapus.']);
        }

        $department = $category->department;
        $deletedCategoryId = (int) $category->id;

        $category->delete();

        $newDeptRatio = (float) $department->budgetCategories()->sum('budget_ratio_percent');
        $department->update(['budget_ratio_percent' => $newDeptRatio]);

        if (request()->wantsJson() || request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Kategori cost berhasil dihapus.',
                'dept_id' => (int) $department->id,
                'new_dept_ratio' => round($newDeptRatio, 2),
                'deleted_category_id' => $deletedCategoryId,
            ]);
        }

        return back()->with('success', 'Kategori cost berhasil dihapus.');
    }

}
