<?php

namespace App\Http\Controllers\Fat;

use App\Http\Controllers\Controller;
use App\Models\GlobalMonthlyBudget;
use App\Models\FiscalYear;
use Illuminate\Http\Request;
use Carbon\Carbon;

class GlobalBudgetController extends Controller
{
    private function checkFatOrSuperAdmin()
    {
        if (!auth()->user()->isFAT() && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index(Request $request)
    {
        $this->checkFatOrSuperAdmin();

        $activeFiscalYear = FiscalYear::where('is_active', true)->firstOrFail();
        $type = $request->input('type', 'actual'); // default to actual

        $budgets = GlobalMonthlyBudget::where('fiscal_year_id', $activeFiscalYear->id)
            ->where('type', $type)
            ->orderBy('month')
            ->get();

        return view('fat.global_budgets.index', compact('activeFiscalYear', 'budgets', 'type'));
    }

    public function create(Request $request)
    {
        $this->checkFatOrSuperAdmin();
        $activeFiscalYear = FiscalYear::where('is_active', true)->firstOrFail();
        $type = $request->input('type', 'actual');

        return view('fat.global_budgets.create', compact('activeFiscalYear', 'type'));
    }

    public function store(Request $request)
    {
        $this->checkFatOrSuperAdmin();

        $activeFiscalYear = FiscalYear::where('is_active', true)->firstOrFail();

        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:actual,forecast',
            'notes' => 'nullable|string',
        ]);

        // Check if exists
        $exists = GlobalMonthlyBudget::where('fiscal_year_id', $activeFiscalYear->id)
            ->where('month', $validated['month'])
            ->where('year', $activeFiscalYear->year)
            ->where('type', $validated['type'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['month' => 'Budget untuk bulan dan tipe ini sudah ada. Silakan edit yang sudah ada.']);
        }

        GlobalMonthlyBudget::create([
            'fiscal_year_id' => $activeFiscalYear->id,
            'month' => $validated['month'],
            'year' => $activeFiscalYear->year,
            'amount' => $validated['amount'],
            'type' => $validated['type'],
            'notes' => $validated['notes'],
        ]);

        return redirect()->route('fat.global-budgets.index', ['type' => $validated['type']])
            ->with('success', 'Budget Global Bulanan berhasil dibuat.');
    }

    public function edit(GlobalMonthlyBudget $globalBudget)
    {
        $this->checkFatOrSuperAdmin();
        return view('fat.global_budgets.edit', compact('globalBudget'));
    }

    public function update(Request $request, GlobalMonthlyBudget $globalBudget)
    {
        $this->checkFatOrSuperAdmin();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $globalBudget->update($validated);

        return redirect()->route('fat.global-budgets.index', ['type' => $globalBudget->type])
            ->with('success', 'Budget Global berhasil diperbarui.');
    }

    public function destroy(GlobalMonthlyBudget $globalBudget)
    {
        $this->checkFatOrSuperAdmin();
        $type = $globalBudget->type;
        $globalBudget->delete();
        
        return redirect()->route('fat.global-budgets.index', ['type' => $type])
            ->with('success', 'Budget Global berhasil dihapus.');
    }
}
