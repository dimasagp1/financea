<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BudgetCategory;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\GlobalMonthlyBudget;
use App\Models\Expense;
use Illuminate\Http\Request;

class BudgetApiController extends Controller
{
    public function check(Request $request)
    {
        // Validasi Payload
        $validated = $request->validate([
            'department_id'   => 'required|integer',
            'department_name' => 'nullable|string',
            'category_name'   => 'required|string',
            'month'           => 'required|date_format:Y-m',
            'requested_amount' => 'required|numeric|min:0',
            'reference'       => 'nullable|string'
        ]);

        $year  = (int) date('Y', strtotime($validated['month']));
        $month = (int) date('m', strtotime($validated['month']));

        // Cari Fiscal Year yang aktif
        $activeYear = FiscalYear::where('is_active', true)->first();

        if (!$activeYear) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tidak ada Tahun Fiskal yang aktif.',
            ], 400);
        }

        // Resolusi Department: coba lookup by name dulu, fallback ke ID
        $resolvedDeptId = $validated['department_id'];
        if (!empty($validated['department_name'])) {
            $dept = Department::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($validated['department_name']) . '%'])->first();
            if ($dept) {
                $resolvedDeptId = $dept->id;
            }
        }

        // Cari Kategori Anggaran (Cost Center)
        if ($validated['category_name'] === 'TEST_CONNECTION') {
            return response()->json([
                'status' => 'success',
                'is_allowed' => true,
                'message' => 'Koneksi API berhasil terhubung!'
            ]);
        }

        $category = BudgetCategory::where('department_id', $resolvedDeptId)
            ->where('name', $validated['category_name'])
            ->where('fiscal_year_id', $activeYear->id)
            ->first();

        if (!$category) {
            // Fallback: search globally within active fiscal year for sharing budget support
            $category = BudgetCategory::where('name', $validated['category_name'])
                ->where('fiscal_year_id', $activeYear->id)
                ->first();
            if ($category) {
                $resolvedDeptId = $category->department_id;
            }
        }

        if (!$category) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kategori anggaran (' . $validated['category_name'] . ') tidak ditemukan di departemen ini untuk tahun fiskal aktif.',
            ], 404);
        }

        // Ambil Pagu Global Bulan Ini
        $globalMonthly = GlobalMonthlyBudget::where('fiscal_year_id', $activeYear->id)
            ->where('month', $month)
            ->where('year', $year)
            ->where('type', 'actual')
            ->first();

        // Jika tidak ada pagu global untuk bulan ini, izinkan PR (data belum dikonfigurasi)
        if (!$globalMonthly) {
            return response()->json([
                'status'           => 'success',
                'is_allowed'       => true,
                'budget_limit'     => 0,
                'current_usage'    => 0,
                'remaining_budget' => 0,
                'message'          => 'Data pagu anggaran bulan ini belum dikonfigurasi di sistem Finance. PR diizinkan.',
            ]);
        }

        // 1. Kalkulasi Limit Anggaran Kategori
        $budgetLimit = 0;
        if ($globalMonthly && $category->department && $category->department->budget_ratio_percent > 0) {
            $baselinePagu = ($globalMonthly->amount * $category->department->budget_ratio_percent) / 100;
            $ratioFraction = $category->budget_ratio_percent / $category->department->budget_ratio_percent;
            $budgetLimit = $baselinePagu * $ratioFraction;
        }

        // 2. Kalkulasi Penggunaan Saat Ini (Current Usage)
        $currentUsage = $category->expenses()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->sum('amount');

        // 3. Kalkulasi Sisa Anggaran (Setelah ditambah requested_amount)
        $requestedAmount = (float) $validated['requested_amount'];
        $remainingBudget = $budgetLimit - $currentUsage - $requestedAmount;

        // 4. Status allowed (Hard Block jika < 0)
        $isAllowed = $remainingBudget >= 0;

        $message = $isAllowed 
            ? 'Anggaran mencukupi.' 
            : 'Batasan anggaran terlampaui. Total pengajuan melebihi sisa anggaran kategori ' . $validated['category_name'] . ' bulan ini.';

        // Cari data expense yang sudah ter-record untuk dicocokkan revisinya
        $recordedExpenseAmount = null;
        if (!empty($validated['reference'])) {
            $expense = Expense::where('reference', $validated['reference'])
                ->where('budget_category_id', $category->id)
                ->first();
            if ($expense) {
                $recordedExpenseAmount = (float) $expense->amount;
            }
        }

        return response()->json([
            'status' => 'success',
            'is_allowed' => $isAllowed,
            'budget_limit' => $budgetLimit,
            'current_usage' => $currentUsage,
            'requested_amount' => $requestedAmount,
            'remaining_budget' => $remainingBudget,
            'recorded_expense_amount' => $recordedExpenseAmount,
            'message' => $message
        ]);
    }

    public function monthlyStatus()
    {
        $activeYear = FiscalYear::where('is_active', true)->first();
        if (!$activeYear) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada Tahun Fiskal yang aktif.',
                'has_budgets' => false
            ]);
        }

        $count = GlobalMonthlyBudget::where('fiscal_year_id', $activeYear->id)->count();

        return response()->json([
            'status' => 'success',
            'fiscal_year' => $activeYear->year,
            'global_budget_amount' => $activeYear->global_budget_amount,
            'has_budgets' => $count > 0,
            'months_count' => $count
        ]);
    }

    public function generateMonthly()
    {
        $activeYear = FiscalYear::where('is_active', true)->first();
        if (!$activeYear) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada Tahun Fiskal yang aktif.'
            ], 400);
        }

        $existing = GlobalMonthlyBudget::where('fiscal_year_id', $activeYear->id)->count();
        if ($existing >= 12) {
            return response()->json([
                'status' => 'success',
                'message' => 'Pagu anggaran bulanan sudah lengkap (12 bulan) untuk Tahun Fiskal ' . $activeYear->year
            ]);
        }

        $monthlyAmount = $activeYear->global_budget_amount / 12;

        for ($m = 1; $m <= 12; $m++) {
            GlobalMonthlyBudget::updateOrCreate(
                [
                    'fiscal_year_id' => $activeYear->id,
                    'month' => $m,
                    'year' => $activeYear->year,
                    'type' => 'actual'
                ],
                [
                    'amount' => $monthlyAmount,
                    'notes' => 'Generated automatically from Procurement interface'
                ]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil men-generate pagu anggaran bulanan (12 bulan) untuk Tahun Fiskal ' . $activeYear->year,
            'monthly_amount' => $monthlyAmount
        ]);
    }

    public function listMonthly()
    {
        $activeYear = FiscalYear::where('is_active', true)->first();
        if (!$activeYear) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada Tahun Fiskal yang aktif.',
                'data' => []
            ], 400);
        }

        $budgets = GlobalMonthlyBudget::where('fiscal_year_id', $activeYear->id)
            ->orderBy('month', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'fiscal_year' => $activeYear->year,
            'data' => $budgets
        ]);
    }

    public function detailMonthly(Request $request)
    {
        $monthParam = $request->query('month', date('Y-m'));
        $year  = (int) date('Y', strtotime($monthParam));
        $month = (int) date('m', strtotime($monthParam));

        $activeYear = FiscalYear::where('is_active', true)->first();
        if (!$activeYear) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada Tahun Fiskal yang aktif.',
                'data' => []
            ], 400);
        }

        $globalMonthly = GlobalMonthlyBudget::where('fiscal_year_id', $activeYear->id)
            ->where('month', $month)
            ->where('year', $year)
            ->where('type', 'actual')
            ->first();

        $departments = Department::where('is_active', true)->get();
        $result = [];

        foreach ($departments as $dept) {
            $categoriesData = [];
            $categories = BudgetCategory::where('department_id', $dept->id)
                ->where('fiscal_year_id', $activeYear->id)
                ->where('is_active', true)
                ->get();

            foreach ($categories as $cat) {
                // Calculate Pagu
                $pagu = 0;
                if ($globalMonthly) {
                    $pagu = ($globalMonthly->amount * $cat->budget_ratio_percent) / 100;
                }

                // Calculate Realisasi
                $realisasi = $cat->expenses()
                    ->whereYear('date', $year)
                    ->whereMonth('date', $month)
                    ->sum('amount');

                $categoriesData[] = [
                    'category_id' => $cat->id,
                    'category_name' => $cat->name,
                    'pagu' => (float) $pagu,
                    'realisasi' => (float) $realisasi,
                ];
            }

            $result[] = [
                'department_id' => $dept->id,
                'department_name' => $dept->name,
                'categories' => $categoriesData
            ];
        }

        return response()->json([
            'status' => 'success',
            'month' => $monthParam,
            'fiscal_year' => $activeYear->year,
            'data' => $result
        ]);
    }

    public function categories(Request $request)
    {
        $deptName = $request->query('department_name');
        
        $activeYear = FiscalYear::where('is_active', true)->first();
        if (!$activeYear) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada Tahun Fiskal yang aktif.',
                'data' => []
            ], 400);
        }

        $query = BudgetCategory::where('fiscal_year_id', $activeYear->id)
            ->where('is_active', true);

        if (!empty($deptName)) {
            $dept = Department::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($deptName) . '%'])->first();
            if ($dept) {
                $query->where('department_id', $dept->id);
            }
        }

        $categories = $query->with('department')->get()->map(function($cat) {
            return [
                'name' => $cat->name,
                'department_name' => $cat->department?->name
            ];
        })->toArray();

        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }

    public function recordExpense(Request $request)
    {
        $validated = $request->validate([
            'department_id'   => 'required|integer',
            'department_name' => 'nullable|string',
            'category_name'   => 'required|string',
            'amount'          => 'required|numeric|min:0.01',
            'date'            => 'required|date',
            'reference'       => 'required|string',
            'description'     => 'nullable|string',
            'qty'             => 'nullable|numeric|min:0',
        ]);

        $year  = (int) date('Y', strtotime($validated['date']));
        $month = (int) date('m', strtotime($validated['date']));

        $activeYear = FiscalYear::where('is_active', true)->first();
        if (!$activeYear) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tidak ada Tahun Fiskal yang aktif di sistem Finance.',
            ], 400);
        }

        // Resolve department
        $resolvedDeptId = $validated['department_id'];
        if (!empty($validated['department_name'])) {
            $dept = Department::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($validated['department_name']) . '%'])->first();
            if ($dept) {
                $resolvedDeptId = $dept->id;
            }
        }

        // Find Budget Category
        $category = BudgetCategory::where('department_id', $resolvedDeptId)
            ->where('name', $validated['category_name'])
            ->where('fiscal_year_id', $activeYear->id)
            ->first();

        if (!$category) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kategori anggaran (' . $validated['category_name'] . ') tidak ditemukan di departemen ini untuk tahun fiskal aktif.',
            ], 404);
        }

        // Check if exists to prevent duplication
        $exists = Expense::where('reference', $validated['reference'])
            ->where('budget_category_id', $category->id)
            ->first();

        if ($exists) {
            $exists->update([
                'amount' => $validated['amount'],
                'qty' => $validated['qty'] ?? 1,
                'date' => $validated['date'],
                'description' => $validated['description'] ?? "Procurement PR Realization Update",
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Realisasi anggaran diperbarui.',
                'data' => $exists
            ]);
        }

        $expense = Expense::create([
            'department_id'      => $resolvedDeptId,
            'budget_category_id' => $category->id,
            'qty'                => $validated['qty'] ?? 1,
            'amount'             => $validated['amount'],
            'date'               => $validated['date'],
            'description'        => $validated['description'] ?? "Procurement PR Realization",
            'reference'          => $validated['reference'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Realisasi anggaran berhasil dicatat.',
            'data' => $expense
        ]);
    }

    public function removeExpense(Request $request)
    {
        $validated = $request->validate([
            'reference' => 'required|string',
        ]);

        Expense::where('reference', $validated['reference'])->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Realisasi anggaran untuk referensi tersebut telah dihapus.'
        ]);
    }

    public function departments()
    {
        $departments = Department::where('is_active', true)->get();
        return response()->json([
            'status' => 'success',
            'data' => $departments
        ]);
    }
}
