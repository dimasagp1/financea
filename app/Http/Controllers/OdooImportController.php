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

    /**
     * Sync Odoo expenses for a specific month (used from COA mapping page).
     * This ensures that after mapping a COA to a department/category, transactions
     * for the selected month are immediately pulled into the FAT expenses table.
     */
    public function syncMonth(Request $request)
    {
        $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $month = $request->input('month');
        $dateFrom = "{$month}-01";
        $dateTo = date('Y-m-t', strtotime($dateFrom));

        try {
            $result = $this->odooSyncService->syncExpenses(null, $dateFrom, $dateTo);
        } catch (\Exception $e) {
            return redirect()->route('fat.odoo.coa-mapping', ['month' => $month])
                ->with('error', 'Sinkronisasi gagal: ' . $e->getMessage());
        }

        $message = "Sinkronisasi data " . \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y') . " selesai. "
            . "Baru: {$result['inserted']}, Update: {$result['updated']}, Skip: {$result['skipped']} dari total {$result['total']} transaksi.";

        return redirect()->route('fat.odoo.coa-mapping', ['month' => $month])
            ->with('success', $message);
    }

    /**
     * Unsync Odoo expenses for a specific month (delete local synced expenses & manual transaction mappings).
     */
    public function unsyncMonth(Request $request)
    {
        $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $month = $request->input('month');
        $dateFrom = "{$month}-01";
        $dateTo = date('Y-m-t', strtotime($dateFrom));

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($dateFrom, $dateTo) {
                // Find expenses in the date range that were synced
                $expensesQuery = \App\Models\Expense::where('date', '>=', $dateFrom)
                    ->where('date', '<=', $dateTo)
                    ->where(function ($q) {
                        $q->where('is_synced', true)
                          ->orWhereNotNull('odoo_move_line_id');
                    });

                $moveLineIds = $expensesQuery->pluck('odoo_move_line_id')->filter()->toArray();

                if (!empty($moveLineIds)) {
                    // Delete manual per-transaction assignments/mappings
                    \App\Models\OdooTransactionMapping::whereIn('odoo_move_line_id', $moveLineIds)->delete();
                }

                // Delete the actual expenses
                $expensesQuery->delete();
            });

            // Clear Odoo raw cache for that month
            $cacheKey = "odoo_raw_expenses_{$dateFrom}_{$dateTo}_all";
            \Illuminate\Support\Facades\Cache::forget($cacheKey);

        } catch (\Exception $e) {
            return redirect()->route('fat.odoo.coa-mapping', ['month' => $month])
                ->with('error', 'Gagal mengosongkan data sinkronisasi: ' . $e->getMessage());
        }

        $message = "Sinkronisasi data " . \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y') . " berhasil dibatalkan dan dikosongkan.";

        return redirect()->route('fat.odoo.coa-mapping', ['month' => $month])
            ->with('success', $message);
    }

    public function croscheck(Request $request)
    {
        $departments = Department::orderBy('name')->get();

        // 1. Get filtered month (default to latest transaction month from Odoo, or current month)
        $month = $request->input('month');
        if (!$month) {
            $latestDate = $this->odooSyncService->getLatestTransactionDate();
            $month = $latestDate ? date('Y-m', strtotime($latestDate)) : now()->format('Y-m');
        }
        $dateFrom = "{$month}-01";
        $dateTo = date('Y-m-t', strtotime($dateFrom));

        $analyticId = null;
        if ($request->filled('department_id')) {
            $dept = Department::find($request->input('department_id'));
            if ($dept && $dept->odoo_analytic_id) {
                $analyticId = (int) $dept->odoo_analytic_id;
            }
        }

        $isOffline = false;
        try {
            // Fetch directly from Odoo via XML-RPC
            $rawExpenses = $this->odooSyncService->fetchRawExpenses($dateFrom, $dateTo, $analyticId);
        } catch (\Exception $e) {
            $rawExpenses = [];
        }

        if (empty($rawExpenses)) {
            $isOffline = true;
            session()->now('warning', 'Odoo tidak dapat dihubungi atau data transaksi kosong. Menampilkan data cache jika ada.');
        }

        // Apply search filter if search input is filled
        if ($request->filled('search')) {
            $search = strtolower($request->input('search'));
            $rawExpenses = array_filter($rawExpenses, function ($item) use ($search) {
                $nameMatch = isset($item['name']) && str_contains(strtolower($item['name']), $search);
                $refMatch = isset($item['ref']) && str_contains(strtolower($item['ref']), $search);
                
                $coaMatch = false;
                if (isset($item['account_id'])) {
                    if (is_array($item['account_id'])) {
                        $coaMatch = str_contains(strtolower($item['account_id'][1]), $search);
                    } else {
                        $coaMatch = str_contains(strtolower($item['account_id']), $search);
                    }
                }
                
                return $nameMatch || $refMatch || $coaMatch;
            });
        }

        // Filter by COA prefixes if coa_prefixes array is filled
        if ($request->filled('coa_prefixes')) {
            $prefixes = (array) $request->input('coa_prefixes');
            $rawExpenses = array_filter($rawExpenses, function ($item) use ($prefixes) {
                if (isset($item['account_id'])) {
                    $displayName = is_array($item['account_id']) ? trim((string)$item['account_id'][1]) : trim((string)$item['account_id']);
                    if (preg_match('/^[a-zA-Z0-9]+/', $displayName, $matches)) {
                        $code = $matches[0];
                        foreach ($prefixes as $prefix) {
                            if (str_starts_with($code, $prefix)) {
                                return true;
                            }
                        }
                    }
                }
                return false;
            });
        }

        // Group raw expenses by Odoo COA Account
        $groupedExpenses = [];
        foreach ($rawExpenses as $item) {
            $accountId = is_array($item['account_id']) ? $item['account_id'][0] : $item['account_id'];
            $accountName = is_array($item['account_id']) ? $item['account_id'][1] : "Account ID: {$accountId}";
            
            if (!isset($groupedExpenses[$accountId])) {
                $groupedExpenses[$accountId] = [
                    'id' => $accountId,
                    'name' => $accountName,
                    'code' => '',
                    'total_amount' => 0,
                    'items' => []
                ];
                
                if (preg_match('/^[a-zA-Z0-9\.\-]+/', trim((string)$accountName), $matches)) {
                    $groupedExpenses[$accountId]['code'] = $matches[0];
                }
            }
            
            $amount = (float) (($item['debit'] ?? 0) - ($item['credit'] ?? 0));
            $groupedExpenses[$accountId]['total_amount'] += $amount;
            $groupedExpenses[$accountId]['items'][] = $item;
        }

        // Sort COA groups by code
        usort($groupedExpenses, function($a, $b) {
            return strcmp($a['code'], $b['code']);
        });

        // Paginate local array of COA groups
        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $itemCollection = collect($groupedExpenses);
        $perPage = 10;
        $currentPageItems = $itemCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();
        $expenses = new \Illuminate\Pagination\LengthAwarePaginator($currentPageItems, count($itemCollection), $perPage);
        $expenses->setPath($request->url());
        $expenses->withQueryString();

        // Get matching local expenses for all items in the current page groups to check status
        $odooIds = [];
        foreach ($currentPageItems as $group) {
            foreach ($group['items'] as $item) {
                $odooIds[] = $item['id'];
            }
        }
        $existingLocalExpenses = \App\Models\Expense::whereIn('odoo_move_line_id', array_map('strval', $odooIds))
            ->get()
            ->keyBy('odoo_move_line_id');

        $localAnalyticIds   = Department::whereNotNull('odoo_analytic_id')->pluck('odoo_analytic_id')->toArray();
        $localCategoryCodes = \App\Models\BudgetCategory::pluck('code')->toArray();
        // Eager-load targets (and their relations) so the view can check isMultiMapped per COA
        $coaMappings = \App\Models\OdooCoaMapping::with('targets.department', 'targets.budgetCategory')->get()->keyBy('odoo_account_id');

        // Load per-transaction assignments for all items on this page
        $txMappings = \App\Models\OdooTransactionMapping::whereIn('odoo_move_line_id', array_map('strval', $odooIds))
            ->with('department', 'budgetCategory')
            ->get()
            ->keyBy('odoo_move_line_id');

        return view('fat.odoo.croscheck', compact(
            'expenses',
            'departments',
            'existingLocalExpenses',
            'localAnalyticIds',
            'localCategoryCodes',
            'month',
            'coaMappings',
            'txMappings'
        ));
    }

    public function coaMapping(Request $request)
    {
        // 1. Get filtered month (default to latest transaction month from Odoo, or current month)
        $month = $request->input('month');
        if (!$month) {
            $latestDate = $this->odooSyncService->getLatestTransactionDate();
            $month = $latestDate ? date('Y-m', strtotime($latestDate)) : now()->format('Y-m');
        }
        $dateFrom = "{$month}-01";
        $dateTo = date('Y-m-t', strtotime($dateFrom));

        $isOffline = false;
        try {
            // Fetch all COA accounts from Odoo
            $odooCoas = $this->odooSyncService->fetchCoaAccounts();
            
            // Fetch raw move lines for the selected month to get real transaction data
            $odooMoveLines = $this->odooSyncService->fetchRawExpenses($dateFrom, $dateTo);
        } catch (\Exception $e) {
            $odooCoas = [];
            $odooMoveLines = [];
        }

        if (empty($odooCoas)) {
            $isOffline = true;
            // Reconstruct COAs from local mappings
            $localMappings = \App\Models\OdooCoaMapping::all();
            $odooCoas = $localMappings->map(fn($m) => [
                'id' => (int) $m->odoo_account_id,
                'code' => $m->odoo_account_code,
                'name' => $m->odoo_account_name,
            ])->toArray();
            $odooMoveLines = [];
            
            session()->now('warning', 'Odoo tidak dapat dihubungi. Menampilkan data pemetaan lokal.');
        }

        // Calculate transaction count and total amount per COA for the selected month
        $coaStats = [];
        foreach ($odooMoveLines as $line) {
            $accountId = null;
            if (is_array($line['account_id'])) {
                $accountId = $line['account_id'][0] ?? null;
            } else {
                $accountId = $line['account_id'] ?? null;
            }

            if (!$accountId) continue;

            $amount = (float) (($line['debit'] ?? 0) - ($line['credit'] ?? 0));

            if (!isset($coaStats[$accountId])) {
                $coaStats[$accountId] = [
                    'amount' => 0,
                    'count' => 0,
                ];
            }
            $coaStats[$accountId]['amount'] += $amount;
            $coaStats[$accountId]['count']++;
        }

        // Attach stats to COAs
        foreach ($odooCoas as &$coa) {
            $id = $coa['id'];
            $coa['total_amount'] = $coaStats[$id]['amount'] ?? 0;
            $coa['transaction_count'] = $coaStats[$id]['count'] ?? 0;
        }
        unset($coa);

        // Filter: Show only COAs with transactions in this month, or search query
        // Default to true if not searching, but if only_active is explicitly passed, respect it
        $showOnlyWithTransactions = $isOffline ? false : ($request->has('only_active') 
            ? $request->boolean('only_active') 
            : !$request->filled('search'));

        if ($showOnlyWithTransactions) {
            $odooCoas = array_filter($odooCoas, function ($coa) {
                return $coa['transaction_count'] > 0;
            });
        }

        // Apply search filter if query is present
        if ($request->filled('search')) {
            $search = strtolower($request->input('search'));
            $odooCoas = array_filter($odooCoas, function ($coa) use ($search) {
                $codeMatch = isset($coa['code']) && str_contains(strtolower($coa['code']), $search);
                $nameMatch = isset($coa['name']) && str_contains(strtolower($coa['name']), $search);
                return $codeMatch || $nameMatch;
            });
        }

        // Filter by COA prefixes if coa_prefixes array is filled
        if ($request->filled('coa_prefixes')) {
            $prefixes = (array) $request->input('coa_prefixes');
            $odooCoas = array_filter($odooCoas, function ($coa) use ($prefixes) {
                $code = trim((string) ($coa['code'] ?? ''));
                foreach ($prefixes as $prefix) {
                    if (str_starts_with($code, $prefix)) {
                        return true;
                    }
                }
                return false;
            });
        }

        // Sort: Sort by transaction count and amount descending, so active COAs appear first
        usort($odooCoas, function ($a, $b) {
            if ($a['transaction_count'] !== $b['transaction_count']) {
                return $b['transaction_count'] <=> $a['transaction_count'];
            }
            if ($a['total_amount'] != $b['total_amount']) {
                return $b['total_amount'] <=> $a['total_amount'];
            }
            return strnatcmp($a['code'] ?? '', $b['code'] ?? '');
        });

        // Get all Departments
        $departments = Department::orderBy('name')->get();
        
        // We want budget categories grouped by department
        $categoriesByDept = \App\Models\BudgetCategory::orderBy('name')
            ->get()
            ->groupBy('department_id');

        // Paginate local array of Odoo COA Accounts
        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $itemCollection = collect($odooCoas);
        $perPage = 20;
        $currentPageItems = $itemCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();
        $coas = new \Illuminate\Pagination\LengthAwarePaginator($currentPageItems, count($itemCollection), $perPage);
        $coas->setPath($request->url());
        $coas->withQueryString();

        // Get existing local mappings WITH their targets
        $coaIds = array_column($currentPageItems, 'id');
        $existingMappings = \App\Models\OdooCoaMapping::whereIn('odoo_account_id', $coaIds)
            ->with('targets.department', 'targets.budgetCategory')
            ->get()
            ->keyBy('odoo_account_id');

        // Fetch transaction assignments for this month's move lines
        $moveLineIds = array_map(fn($line) => strval($line['id']), $odooMoveLines);
        $txMappings = \App\Models\OdooTransactionMapping::whereIn('odoo_move_line_id', $moveLineIds)
            ->get()
            ->keyBy('odoo_move_line_id');

        $mappedLinesByTarget = [];
        $unassignedLinesByCoa = [];

        foreach ($odooMoveLines as $line) {
            $lineIdStr = strval($line['id']);
            $accountId = null;
            if (is_array($line['account_id'])) {
                $accountId = $line['account_id'][0] ?? null;
            } else {
                $accountId = $line['account_id'] ?? null;
            }

            if (!$accountId) continue;

            $mapping = $txMappings->get($lineIdStr);
            if ($mapping) {
                $targetId = $mapping->odoo_coa_mapping_target_id;
                if (!isset($mappedLinesByTarget[$targetId])) {
                    $mappedLinesByTarget[$targetId] = [];
                }
                $mappedLinesByTarget[$targetId][] = $line;
            } else {
                if (!isset($unassignedLinesByCoa[$accountId])) {
                    $unassignedLinesByCoa[$accountId] = [];
                }
                $unassignedLinesByCoa[$accountId][] = $line;
            }
        }

        return view('fat.odoo.coa_mapping', compact(
            'coas', 
            'departments', 
            'categoriesByDept', 
            'existingMappings', 
            'month', 
            'showOnlyWithTransactions',
            'mappedLinesByTarget',
            'unassignedLinesByCoa',
            'odooMoveLines',
            'txMappings'
        ));
    }

    /**
     * Add a mapping target (dept+category) for a given COA.
     * Called via AJAX from the COA mapping page.
     */
    public function addCoaMappingTarget(Request $request)
    {
        $request->validate([
            'odoo_account_id'   => 'required|integer',
            'odoo_account_code' => 'required|string',
            'odoo_account_name' => 'required|string',
            'department_id'     => 'required|exists:departments,id',
            'budget_category_id'=> 'nullable|exists:budget_categories,id',
            'month'             => 'nullable|string',
        ]);

        // Upsert the header mapping
        $mapping = \App\Models\OdooCoaMapping::updateOrCreate(
            ['odoo_account_id' => $request->input('odoo_account_id')],
            [
                'odoo_account_code' => $request->input('odoo_account_code'),
                'odoo_account_name' => $request->input('odoo_account_name'),
            ]
        );

        // Add target (ignore duplicate)
        try {
            $target = $mapping->targets()->updateOrCreate(
                ['department_id' => $request->input('department_id'), 'budget_category_id' => $request->input('budget_category_id') ?: null],
                ['department_id' => $request->input('department_id'), 'budget_category_id' => $request->input('budget_category_id') ?: null]
            );
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Target sudah ada atau terjadi kesalahan.']);
        }

        // Trigger auto-sync for the selected month to fetch transactions for this new mapping immediately
        if ($request->filled('month')) {
            $month = $request->input('month');
            $dateFrom = "{$month}-01";
            $dateTo = date('Y-m-t', strtotime($dateFrom));
            try {
                $this->odooSyncService->syncExpenses(null, $dateFrom, $dateTo);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Auto-sync failed on mapping target addition: ' . $e->getMessage());
            }
        }

        $targets = $mapping->targets()->with('department', 'budgetCategory')->get();

        return response()->json([
            'success' => true,
            'target_id' => $target->id,
            'targets' => $targets->map(fn($t) => [
                'id'             => $t->id,
                'dept_name'      => $t->department?->name,
                'cat_name'       => $t->budgetCategory?->name,
                'cat_code'       => $t->budgetCategory?->code,
            ])
        ]);
    }

    /**
     * Remove a specific mapping target.
     */
    public function removeCoaMappingTarget(Request $request)
    {
        $request->validate([
            'target_id' => 'required|exists:odoo_coa_mapping_targets,id',
            'month'     => 'nullable|string',
        ]);

        $target = \App\Models\OdooCoaMappingTarget::with('mapping')->findOrFail($request->input('target_id'));
        $mapping = $target->mapping;

        // Delete any local expenses matching this target's department, category, and COA
        if ($mapping) {
            \App\Models\Expense::where('department_id', $target->department_id)
                ->where('budget_category_id', $target->budget_category_id)
                ->where(function($q) use ($mapping) {
                    $q->whereJsonContains('odoo_data->account_id', (int) $mapping->odoo_account_id)
                      ->orWhereJsonContains('odoo_data->account_id', (string) $mapping->odoo_account_id);
                })
                ->delete();
        }

        $target->delete();

        // Trigger sync for the selected month to refresh database state
        if ($request->filled('month')) {
            $month = $request->input('month');
            $dateFrom = "{$month}-01";
            $dateTo = date('Y-m-t', strtotime($dateFrom));
            try {
                $this->odooSyncService->syncExpenses(null, $dateFrom, $dateTo);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Auto-sync failed on mapping target removal: ' . $e->getMessage());
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Store per-transaction manual assignment for a multi-mapped COA.
     * Called from Croscheck page when user assigns a transaction to a specific category.
     */
    public function storeTransactionMapping(Request $request)
    {
        $request->validate([
            'odoo_move_line_id'          => 'nullable|string',
            'odoo_move_line_ids'         => 'nullable|array',
            'odoo_move_line_ids.*'       => 'string',
            'odoo_coa_mapping_target_id' => 'required|exists:odoo_coa_mapping_targets,id',
        ]);

        $target = \App\Models\OdooCoaMappingTarget::with('mapping')->findOrFail($request->input('odoo_coa_mapping_target_id'));

        $moveLineIds = $request->input('odoo_move_line_ids') ?: [];
        if ($request->filled('odoo_move_line_id')) {
            $moveLineIds[] = $request->input('odoo_move_line_id');
        }

        foreach ($moveLineIds as $lineId) {
            \App\Models\OdooTransactionMapping::updateOrCreate(
                ['odoo_move_line_id' => $lineId],
                [
                    'odoo_coa_mapping_id'        => $target->odoo_coa_mapping_id,
                    'odoo_coa_mapping_target_id' => $target->id,
                    'department_id'              => $target->department_id,
                    'budget_category_id'         => $target->budget_category_id,
                ]
            );
        }

        if (!empty($moveLineIds)) {
            $this->odooSyncService->syncMultipleExpenses($moveLineIds);
        }

        return response()->json([
            'success'   => true,
            'dept_name' => $target->department?->name,
            'cat_name'  => $target->budgetCategory?->name,
        ]);
    }

    /**
     * Remove manual transaction assignment.
     */
    public function removeTransactionMapping(Request $request)
    {
        $request->validate([
            'odoo_move_line_id' => 'required|string',
        ]);

        $lineId = $request->input('odoo_move_line_id');

        // Delete the mapping
        \App\Models\OdooTransactionMapping::where('odoo_move_line_id', $lineId)->delete();

        // Also delete the corresponding Expense record
        \App\Models\Expense::where('odoo_move_line_id', $lineId)->delete();

        return response()->json([
            'success' => true
        ]);
    }

    /** @deprecated Use addCoaMappingTarget instead */
    public function storeCoaMapping(Request $request)
    {
        return $this->addCoaMappingTarget($request);
    }
}
