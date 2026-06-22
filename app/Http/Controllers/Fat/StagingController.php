<?php

namespace App\Http\Controllers\Fat;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\ExpenseStaging;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StagingController extends Controller
{
    private function authorizeMutation()
    {
        $user = auth()->user();
        if (!$user || (!$user->isFAT() && !$user->isSuperAdmin())) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeMutation();
        $query = ExpenseStaging::with(['department', 'budgetCategory', 'checkedBy']);

        // Search reference or description
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by Department
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        } else {
            // Sort to show pending first, then newest
            $query->orderByRaw("FIELD(status, 'pending', 'bon', 'ignored') ASC")
                  ->orderBy('date', 'desc');
        }

        $stagings = $query->paginate(15)->withQueryString();
        $departments = Department::orderBy('name')->get();

        return view('fat.staging.index', compact('stagings', 'departments'));
    }

    public function markAsBon(ExpenseStaging $staging)
    {
        $this->authorizeMutation();

        $staging->update([
            'status' => 'bon',
            'checked_by' => auth()->id(),
            'checked_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Status pengeluaran berhasil diubah menjadi Reported Odoo.');
    }

    public function markAsIgnored(ExpenseStaging $staging)
    {
        $this->authorizeMutation();

        $staging->update([
            'status' => 'ignored',
            'checked_by' => auth()->id(),
            'checked_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Pengeluaran berhasil diabaikan.');
    }

    public function markAsPending(ExpenseStaging $staging)
    {
        $this->authorizeMutation();

        $staging->update([
            'status' => 'pending',
            'checked_by' => null,
            'checked_at' => null,
        ]);

        return redirect()->back()->with('success', 'Status pengeluaran dikembalikan ke Pending.');
    }
}
