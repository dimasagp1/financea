<?php

namespace App\Http\Controllers\Fat;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Services\BudgetCalculationService;
use Illuminate\Http\Request;

class BudgetManagementController extends Controller
{
    public function __construct(private readonly BudgetCalculationService $budgetCalculationService)
    {
    }

    public function updateGlobalBudget(Request $request, FiscalYear $fiscalYear)
    {
        $validated = $request->validate([
            'global_budget_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $fiscalYear->update([
            'global_budget_amount' => $validated['global_budget_amount'],
        ]);

        $this->budgetCalculationService->recalculateAllDepartments($fiscalYear);

        return back()->with('success', 'Global budget berhasil diperbarui dan dialokasikan ulang.');
    }

    public function updateDepartmentRatio(Request $request, Department $department)
    {
        $validated = $request->validate([
            'budget_ratio_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $this->budgetCalculationService->recalculateBudget($department, (float) $validated['budget_ratio_percent']);

        return back()->with('success', 'Rasio departemen berhasil diperbarui dan budget dihitung ulang.');
    }

    public function bulkUpdateDepartmentRatios(Request $request)
    {
        $validated = $request->validate([
            'ratios' => ['required', 'array', 'min:1'],
            'ratios.*.department_id' => ['required', 'exists:departments,id'],
            'ratios.*.budget_ratio_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $totalRatio = collect($validated['ratios'])->sum(function (array $row) {
            return (float) $row['budget_ratio_percent'];
        });

        if (abs($totalRatio - 100) > 0.01) {
            return back()->withErrors([
                'Total bobot alokasi harus tepat 100%.',
            ])->withInput();
        }

        foreach ($validated['ratios'] as $row) {
            $department = Department::query()->findOrFail((int) $row['department_id']);
            $this->budgetCalculationService->recalculateBudget($department, (float) $row['budget_ratio_percent']);
        }

        return back()->with('success', 'Alokasi departemen berhasil disimpan.');
    }

    public function overrideMonthlyBudget(Request $request, Department $department)
    {
        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $this->budgetCalculationService->updateMonthlyBudget(
            $department,
            $validated['month'],
            (float) $validated['amount'],
            (int) auth()->id(),
        );

        return back()->with('success', 'Budget bulanan berhasil dioverride.');
    }
}
