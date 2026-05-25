<?php

namespace App\Http\Controllers\Fat;

use App\Exports\OdooExpenseExport;
use App\Http\Controllers\Controller;
use App\Imports\OdooExpenseImport;
use App\Models\Department;
use App\Models\FiscalYear;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class OdooExcelController extends Controller
{
    /**
     * Download an Excel template containing categories mapping to Odoo (Kode Akun)
     */
    public function exportTemplate(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
        ]);

        $department = Department::findOrFail($request->department_id);
        $activeFiscalYear = FiscalYear::where('is_active', true)->firstOrFail();

        $categories = $department->budgetCategories()
            ->where('fiscal_year_id', $activeFiscalYear->id)
            ->get();

        $fileName = "Template_Import_Odoo_{$department->name}.xlsx";

        return Excel::download(new OdooExpenseExport($categories), $fileName);
    }

    /**
     * Import expenses from the filled Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'month'         => 'required|date_format:Y-m',
            'file'          => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(
                new OdooExpenseImport($request->department_id, $request->month, auth()->id()),
                $request->file('file')
            );

            return back()->with('success', 'Data realisasi Odoo berhasil diimpor.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengimpor data Odoo: ' . $e->getMessage());
        }
    }
}
