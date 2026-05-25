<?php

namespace App\Http\Controllers\Fat;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\MonthlyBudget;
use Illuminate\Http\Request;
use App\Exports\ForecastExport;
use App\Imports\ForecastImport;
use Maatwebsite\Excel\Facades\Excel;

class ForecastController extends Controller
{
    public function index(Request $request)
    {
        $activeFiscalYear = FiscalYear::where('is_active', true)->firstOrFail();
        $user = auth()->user();

        // Month and Year filtering
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $currentDate = \Carbon\Carbon::createFromDate($year, $month, 1);
        $monthStr = $currentDate->format('Y-m');
        $monthName = $currentDate->translatedFormat('F Y');

        // Fetch Global Monthly Budget (Forecast Type)
        $globalMonthly = \App\Models\GlobalMonthlyBudget::where('fiscal_year_id', $activeFiscalYear->id)
            ->where('month', (int) $month)
            ->where('year', (int) $year)
            ->where('type', 'forecast')
            ->first();

        // Determine accessible departments
        if ($user->isDepartemen()) {
            $departments = Department::where('id', $user->department_id)->orderBy('name')->get();
        } else {
            $departments = Department::orderBy('name')->get();
        }

        // Fetch all overridden forecasts for this month
        $monthlyForecasts = MonthlyBudget::whereIn('department_id', $departments->pluck('id'))
            ->where('month', $monthStr)
            ->get()
            ->keyBy('department_id');

        // Fetch all expenses to calculate Actual
        $allExpenses = \App\Models\Expense::whereYear('date', $currentDate->year)
            ->whereMonth('date', $currentDate->month)
            ->select('department_id', 'budget_category_id', \DB::raw('SUM(amount) as total'))
            ->groupBy('department_id', 'budget_category_id')
            ->get();
        
        $expensesByDept = $allExpenses->groupBy('department_id')->map(function($v) { return $v->sum('total'); });
        $expensesByDeptCat = $allExpenses->keyBy(function($item) { return $item->department_id . '.' . $item->budget_category_id; });

        $grandPagu = 0;
        $grandForecast = 0;
        $grandActual = 0;
        
        $departmentsData = [];

        foreach ($departments as $dept) {
            $monthlyForecast = $monthlyForecasts->get($dept->id);
            
            $paguAmount = $globalMonthly
                ? ($globalMonthly->amount * $dept->budget_ratio_percent) / 100
                : 0;

            $forecastAmount = $monthlyForecast ? $monthlyForecast->amount : $paguAmount;
            
            $deptActual = (float) $expensesByDept->get($dept->id, 0);
            $deptRemaining = $forecastAmount - $deptActual;
            $deptUtilization = $forecastAmount > 0 ? ($deptActual / $forecastAmount) * 100 : 0;

            $grandPagu += $paguAmount;
            $grandForecast += $forecastAmount;
            $grandActual += $deptActual;

            $categories = $dept->budgetCategories()
                ->where('fiscal_year_id', $activeFiscalYear->id)
                ->orderBy('code')
                ->get()
                ->map(function ($cat) use ($forecastAmount, $paguAmount, $dept, $expensesByDeptCat) {
                    $catRatio = (float) $cat->budget_ratio_percent;
                    $deptRatio = (float) $dept->budget_ratio_percent;

                    if ($deptRatio > 0) {
                        $ratioFraction = $catRatio / $deptRatio;
                    } elseif ($catRatio > 0) {
                        $ratioFraction = $catRatio / 100;
                    } else {
                        // Equal fallback if no ratios
                        $catCount = $dept->budgetCategories()->where('fiscal_year_id', $cat->fiscal_year_id)->count() ?: 1;
                        $ratioFraction = 1 / $catCount;
                    }

                    $cat->allocated_pagu = $paguAmount * $ratioFraction;
                    $cat->forecast_value = $forecastAmount * $ratioFraction;
                    
                    $catActual = 0;
                    $expenseRow = $expensesByDeptCat->get($dept->id . '.' . $cat->id);
                    if ($expenseRow) {
                        $catActual = (float) $expenseRow->total;
                    }
                    $cat->actual_spending = $catActual;
                    $cat->remaining = $cat->forecast_value - $catActual;
                    $cat->utilization = $cat->forecast_value > 0 ? ($catActual / $cat->forecast_value) * 100 : 0;

                    return $cat;
                });

            $departmentsData[] = (object) [
                'id' => $dept->id,
                'name' => $dept->name,
                'budget_ratio_percent' => $dept->budget_ratio_percent,
                'calculated_pagu' => $paguAmount,
                'calculated_forecast' => $forecastAmount,
                'monthly_used' => $deptActual,
                'remaining' => $deptRemaining,
                'utilization' => $deptUtilization,
                'is_overridden' => $monthlyForecast ? $monthlyForecast->is_overridden : false,
                'categories' => $categories,
            ];
        }

        $grandUtilization = $grandForecast > 0 ? ($grandActual / $grandForecast) * 100 : 0;

        return view('fat.forecasts.index', [
            'departmentsData' => collect($departmentsData),
            'activeFiscalYear' => $activeFiscalYear,
            'currentMonthName' => $monthName,
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'summary' => [
                'pagu_monthly' => $grandPagu,
                'forecast_monthly' => $grandForecast,
                'actual_monthly' => $grandActual,
                'remaining' => $grandForecast - $grandActual,
                'utilization' => $grandUtilization,
                'diff_pagu_forecast' => $grandPagu - $grandForecast,
                'global_amount' => $globalMonthly ? $globalMonthly->amount : 0,
            ],
            'globalMonthly' => $globalMonthly,
            'is_fat_or_superadmin' => ($user->isFAT() || $user->isSuperAdmin()),
        ]);
    }

    public function show(Department $forecast)
    {
        return redirect()->route('fat.forecasts.index');
    }

    public function update(Request $request, Department $forecast)
    {
        if (!auth()->user()->isFAT() && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Hanya FAT/Superadmin yang bisa mengubah forecast.');
        }

        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
            'amount' => 'required|numeric|min:0',
        ]);

        MonthlyBudget::updateOrCreate(
            [
                'department_id' => $forecast->id,
                'month' => $validated['month'],
            ],
            [
                'amount' => $validated['amount'],
                'is_overridden' => true,
                'created_by' => auth()->id(),
            ]
        );

        return redirect()->back()
            ->with('success', "Forecast {$forecast->name} berhasil diperbarui.");
    }

    public function destroy(Department $forecast, Request $request)
    {
        if (!auth()->user()->isFAT() && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $month = $request->input('month');

        if ($month) {
            MonthlyBudget::where('department_id', $forecast->id)
                ->where('month', $month)
                ->delete();

            return redirect()->back()
                ->with('success', "Forecast {$forecast->name} dikembalikan ke default.");
        }

        return back()->with('error', 'Bulan tidak valid.');
    }

    public function exportExcel(Request $request)
    {
        return back()->with('error', 'Fitur export matrix ditiadakan pada view bulanan.');
    }

    public function importExcel(Request $request)
    {
        return back()->with('error', 'Fitur import matrix ditiadakan pada view bulanan.');
    }
}

