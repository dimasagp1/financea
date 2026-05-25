<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Services\OdooSyncService;
use Illuminate\Http\Request;

class OdooImportController extends Controller
{
    public function __construct(private readonly OdooSyncService $odooSyncService)
    {
    }

    public function syncExpenses(Request $request)
    {
        $validated = $request->validate([
            'department_id' => ['nullable', 'exists:departments,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $department = isset($validated['department_id'])
            ? Department::find($validated['department_id'])
            : null;

        $result = $this->odooSyncService->syncExpenses(
            $department,
            $validated['date_from'] ?? null,
            $validated['date_to'] ?? null,
        );

        return redirect()->route('dashboard.index')->with('success',
            "Sinkronisasi selesai. Total: {$result['total']}, Baru: {$result['inserted']}, Update: {$result['updated']}, Skip: {$result['skipped']}"
        );
    }
}
