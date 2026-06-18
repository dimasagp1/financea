<?php

namespace App\Services;

use App\Models\BudgetCategory;
use App\Models\Department;
use App\Models\Expense;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OdooSyncService
{
    private ?int $uid = null;

    public function syncExpenses(?Department $department = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $this->authenticate();

        $domain = [
            ['move_id.state', '=', 'posted'],
        ];

        // Default to start of the active fiscal year if no date_from is specified, ensuring all transactions of the active year are synced.
        $activeYearModel = \App\Models\FiscalYear::where('is_active', true)->first();
        $activeYear = $activeYearModel ? $activeYearModel->year : now()->year;
        $effectiveDateFrom = $dateFrom ?: "{$activeYear}-01-01";
        $domain[] = ['date', '>=', $effectiveDateFrom];

        if ($dateTo) {
            $domain[] = ['date', '<=', $dateTo];
        }

        if ($department?->odoo_analytic_id) {
            // Odoo 17 analytic distribution filter
            $domain[] = ['analytic_distribution', 'in', [(int) $department->odoo_analytic_id]];
        }

        $fields = [
            'id',
            'name',
            'date',
            'quantity',
            'debit',
            'credit',
            'analytic_distribution',
            'account_id',
            'ref',
            'move_id',
        ];

        $rows = $this->executeKw('account.move.line', 'search_read', [$domain], ['fields' => $fields]);

        $result = [
            'total'    => count($rows),
            'inserted' => 0,
            'updated'  => 0,
            'skipped'  => 0,
            'pending'  => 0, // multi-mapped COAs waiting for per-transaction assignment
        ];

        // Pre-load all COA mappings with their targets (eager load for performance)
        $allMappings = \App\Models\OdooCoaMapping::with('targets.department', 'targets.budgetCategory')->get()->keyBy('odoo_account_id');

        // Pre-load existing per-transaction manual assignments
        $odooIds = array_column($rows, 'id');
        $txMappings = \App\Models\OdooTransactionMapping::whereIn('odoo_move_line_id', array_map('strval', $odooIds))
            ->with('department', 'budgetCategory')
            ->get()
            ->keyBy('odoo_move_line_id');

        foreach ($rows as $row) {
            // 1. Resolve Odoo Account ID
            $odooAccountId = null;
            if (is_array($row['account_id'])) {
                $odooAccountId = $row['account_id'][0] ?? null;
            } else {
                $odooAccountId = $row['account_id'] ?? null;
            }

            $departmentModel = null;
            $category        = null;

            // 2. Check manual COA mapping
            $mapping = $odooAccountId ? ($allMappings[$odooAccountId] ?? null) : null;

            if ($mapping) {
                $targets = $mapping->targets;

                if ($targets->count() === 1) {
                    // Single target → auto-assign
                    $target          = $targets->first();
                    $departmentModel = $target->department;
                    $category        = $target->budgetCategory;
                } elseif ($targets->count() > 1) {
                    // Multiple targets → check per-transaction assignment
                    $txMapping = $txMappings[(string) $row['id']] ?? null;
                    if ($txMapping) {
                        $departmentModel = $txMapping->department;
                        $category        = $txMapping->budgetCategory;
                    } else {
                        // Not yet assigned by user → skip and mark as pending
                        $result['pending']++;
                        continue;
                    }
                }
            }

            // 3. Fallback to automatic analytic matching if no COA mapping found
            if (!$departmentModel) {
                $analyticDistribution = $row['analytic_distribution'] ?? null;
                if (is_string($analyticDistribution)) {
                    $analyticDistribution = json_decode($analyticDistribution, true);
                }

                $analyticId = null;
                if (is_array($analyticDistribution) && !empty($analyticDistribution)) {
                    $keys       = array_keys($analyticDistribution);
                    $analyticId = $keys[0] ?? null;
                }

                if ($analyticId) {
                    $departmentModel = Department::query()->where('odoo_analytic_id', (string) $analyticId)->first();
                }
            }

            // 4. Fallback to automatic category matching if department found but no category
            if ($departmentModel && !$category) {
                $accountId     = null;
                $accountCodeStr = null;

                if (is_array($row['account_id'])) {
                    $accountId   = (string) ($row['account_id'][0] ?? '');
                    $displayName = trim((string) ($row['account_id'][1] ?? ''));

                    if (preg_match('/^[a-zA-Z0-9\\.\\-]+/', $displayName, $matches)) {
                        $accountCodeStr = $matches[0];
                    }
                } else {
                    $accountId = (string) $row['account_id'];
                }

                $category = BudgetCategory::query()
                    ->where(function ($query) use ($accountId, $accountCodeStr) {
                        $query->where('code', $accountId);
                        if ($accountCodeStr) {
                            $query->orWhere('code', $accountCodeStr);
                        }
                    })
                    ->where('department_id', $departmentModel->id)
                    ->first();
            }

            // 5. Skip if we still don't have both department and category
            if (!$departmentModel || !$category) {
                $result['skipped']++;
                continue;
            }

            $amount = (float) (($row['debit'] ?? 0) - ($row['credit'] ?? 0));
            $qty    = (float) ($row['quantity'] ?? 1);
            $qty    = $qty > 0 ? $qty : 1;

            $expense = Expense::query()->updateOrCreate(
                ['odoo_move_line_id' => (string) $row['id']],
                [
                    'department_id'      => $departmentModel->id,
                    'budget_category_id' => $category->id,
                    'qty'                => $qty,
                    'amount'             => $amount,
                    'date'               => $row['date'] ?? now()->toDateString(),
                    'description'        => $row['name'] ?? 'Import from Odoo',
                    'reference'          => $row['ref'] ?? null,
                    'odoo_data'          => $row,
                    'is_synced'          => true,
                    'synced_at'          => now(),
                ]
            );

            // Update the transaction mapping's expense_id if it was a manual assignment
            if (isset($txMapping) && $txMapping) {
                $txMapping->update(['expense_id' => $expense->id]);
                unset($txMapping); // reset for next iteration
            }

            if ($expense->wasRecentlyCreated) {
                $result['inserted']++;
            } else {
                $result['updated']++;
            }
        }

        return $result;
    }

    private function authenticate(): void
    {
        if ($this->uid !== null) {
            return;
        }

        $cacheKey = 'odoo_uid_' . md5($this->getOdooUrl() . $this->getOdooDatabase() . $this->getOdooUsername());
        $cachedUid = \Illuminate\Support\Facades\Cache::get($cacheKey);

        if ($cachedUid && is_int($cachedUid) && $cachedUid > 0) {
            $this->uid = $cachedUid;
            return;
        }

        $response = $this->callXmlRpc('/xmlrpc/2/common', 'authenticate', [
            $this->getOdooDatabase(),
            $this->getOdooUsername(),
            $this->getOdooPassword(),
            [],
        ]);

        if (!is_int($response) || $response <= 0) {
            throw new \RuntimeException('Autentikasi Odoo gagal. Periksa konfigurasi koneksi Odoo.');
        }

        $this->uid = $response;
        \Illuminate\Support\Facades\Cache::put($cacheKey, $response, 7200); // Cache for 2 hours
    }

    private function executeKw(string $model, string $method, array $args = [], array $kwargs = []): array
    {
        if (!$this->uid) {
            throw new \RuntimeException('Sesi Odoo belum terautentikasi.');
        }

        try {
            $response = $this->callXmlRpc('/xmlrpc/2/object', 'execute_kw', [
                $this->getOdooDatabase(),
                $this->uid,
                $this->getOdooPassword(),
                $model,
                $method,
                $args,
                $kwargs,
            ]);

            return is_array($response) ? $response : [];
        } catch (\Exception $e) {
            $cacheKey = 'odoo_uid_' . md5($this->getOdooUrl() . $this->getOdooDatabase() . $this->getOdooUsername());
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
            $this->uid = null;
            throw $e;
        }
    }

    private function callXmlRpc(string $path, string $method, array $params): mixed
    {
        $url = rtrim((string) $this->getOdooUrl(), '/') . $path;
        $payload = $this->encodeXmlRpcRequest($method, $params);

        $httpResponse = Http::withHeaders(['Content-Type' => 'text/xml'])
            ->timeout(8)
            ->withBody($payload, 'text/xml')
            ->post($url);

        if ($httpResponse->failed()) {
            Log::error('Odoo XML-RPC HTTP error', ['status' => $httpResponse->status(), 'body' => $httpResponse->body()]);
            throw new \RuntimeException('Gagal terhubung ke Odoo XML-RPC (Timeout / Unreachable).');
        }

        $decoded = $this->decodeXmlRpcResponse($httpResponse->body());

        if (is_array($decoded) && isset($decoded['faultCode'])) {
            throw new \RuntimeException('Odoo XML-RPC fault: ' . ($decoded['faultString'] ?? 'Unknown fault'));
        }

        return $decoded;
    }

    private function encodeXmlRpcRequest(string $method, array $params): string
    {
        $encodedParams = collect($params)
            ->map(fn ($param) => '<param><value>' . $this->encodeXmlRpcValue($param) . '</value></param>')
            ->implode('');

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<methodCall>'
            . '<methodName>' . $this->xmlEscape($method) . '</methodName>'
            . '<params>' . $encodedParams . '</params>'
            . '</methodCall>';
    }

    private function encodeXmlRpcValue(mixed $value): string
    {
        if (is_int($value)) {
            return '<int>' . $value . '</int>';
        }

        if (is_float($value)) {
            return '<double>' . $value . '</double>';
        }

        if (is_bool($value)) {
            return '<boolean>' . ($value ? '1' : '0') . '</boolean>';
        }

        if (is_string($value)) {
            return '<string>' . $this->xmlEscape($value) . '</string>';
        }

        if ($value === null) {
            return '<string></string>';
        }

        if (is_array($value)) {
            if ($this->isAssocArray($value)) {
                $members = collect($value)
                    ->map(function ($memberValue, $memberKey) {
                        return '<member>'
                            . '<name>' . $this->xmlEscape((string) $memberKey) . '</name>'
                            . '<value>' . $this->encodeXmlRpcValue($memberValue) . '</value>'
                            . '</member>';
                    })
                    ->implode('');

                return '<struct>' . $members . '</struct>';
            }

            $items = collect($value)
                ->map(fn ($item) => '<value>' . $this->encodeXmlRpcValue($item) . '</value>')
                ->implode('');

            return '<array><data>' . $items . '</data></array>';
        }

        return '<string>' . $this->xmlEscape((string) $value) . '</string>';
    }

    private function decodeXmlRpcResponse(string $xml): mixed
    {
        $document = @simplexml_load_string($xml);

        if ($document === false) {
            throw new \RuntimeException('Response XML-RPC dari Odoo tidak valid.');
        }

        if (isset($document->fault)) {
            return $this->decodeXmlRpcValueNode($document->fault->value);
        }

        if (!isset($document->params->param->value)) {
            return null;
        }

        return $this->decodeXmlRpcValueNode($document->params->param->value);
    }

    private function decodeXmlRpcValueNode(\SimpleXMLElement $valueNode): mixed
    {
        if (!count($valueNode->children())) {
            return (string) $valueNode;
        }

        if (isset($valueNode->int)) {
            return (int) $valueNode->int;
        }

        if (isset($valueNode->i4)) {
            return (int) $valueNode->i4;
        }

        if (isset($valueNode->double)) {
            return (float) $valueNode->double;
        }

        if (isset($valueNode->boolean)) {
            return ((string) $valueNode->boolean) === '1';
        }

        if (isset($valueNode->string)) {
            return (string) $valueNode->string;
        }

        if (isset($valueNode->array)) {
            $result = [];
            foreach ($valueNode->array->data->value as $item) {
                $result[] = $this->decodeXmlRpcValueNode($item);
            }
            return $result;
        }

        if (isset($valueNode->struct)) {
            $result = [];
            foreach ($valueNode->struct->member as $member) {
                $name = (string) $member->name;
                $result[$name] = $this->decodeXmlRpcValueNode($member->value);
            }
            return $result;
        }

        if (isset($valueNode->{'dateTime.iso8601'})) {
            return (string) $valueNode->{'dateTime.iso8601'};
        }

        if (isset($valueNode->base64)) {
            return base64_decode((string) $valueNode->base64) ?: '';
        }

        return (string) $valueNode;
    }

    private function isAssocArray(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function extractId(mixed $value): mixed
    {
        if (is_array($value) && isset($value[0])) {
            return $value[0];
        }

        return $value;
    }

    public function testConnection(string $url, string $db, string $username, string $password): array
    {
        try {
            $commonUrl = rtrim($url, '/') . '/xmlrpc/2/common';
            $payload = '<?xml version="1.0" encoding="UTF-8"?>'
                . '<methodCall>'
                . '<methodName>authenticate</methodName>'
                . '<params>'
                . '<param><value><string>' . htmlspecialchars($db, ENT_XML1, 'UTF-8') . '</string></value></param>'
                . '<param><value><string>' . htmlspecialchars($username, ENT_XML1, 'UTF-8') . '</string></value></param>'
                . '<param><value><string>' . htmlspecialchars($password, ENT_XML1, 'UTF-8') . '</string></value></param>'
                . '<param><value><array><data></data></array></value></param>'
                . '</params>'
                . '</methodCall>';

            $httpResponse = Http::withHeaders(['Content-Type' => 'text/xml'])
                ->withBody($payload, 'text/xml')
                ->timeout(10)
                ->post($commonUrl);

            if ($httpResponse->failed()) {
                return [
                    'success' => false,
                    'message' => 'HTTP Error: Gagal terhubung ke URL Odoo. Status Code: ' . $httpResponse->status()
                ];
            }

            $decoded = $this->decodeXmlRpcResponse($httpResponse->body());

            if (is_array($decoded) && isset($decoded['faultCode'])) {
                return [
                    'success' => false,
                    'message' => 'Odoo Error: ' . ($decoded['faultString'] ?? 'Unknown fault')
                ];
            }

            if (is_int($decoded) && $decoded > 0) {
                return [
                    'success' => true,
                    'message' => 'Koneksi berhasil! Autentikasi dengan database "' . $db . '" sukses.'
                ];
            }

            return [
                'success' => false,
                'message' => 'Autentikasi gagal. Username atau password salah.'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    public function fetchRawExpenses(string $dateFrom, string $dateTo, ?int $analyticId = null): array
    {
        $cacheKey = "odoo_raw_expenses_{$dateFrom}_{$dateTo}_" . ($analyticId ?: 'all');
        try {
            $this->authenticate();

            $domain = [
                ['move_id.state', '=', 'posted'],
                ['date', '>=', $dateFrom],
                ['date', '<=', $dateTo],
            ];

            if ($analyticId) {
                $domain[] = ['analytic_distribution', 'in', [$analyticId]];
            }

            $fields = [
                'id',
                'name',
                'date',
                'quantity',
                'debit',
                'credit',
                'analytic_distribution',
                'account_id',
                'ref',
                'move_id',
            ];

            $rows = $this->executeKw('account.move.line', 'search_read', [$domain], ['fields' => $fields]);
            if (is_array($rows)) {
                \Illuminate\Support\Facades\Cache::put($cacheKey, $rows, 900); // 15 minutes
            }
            return $rows;
        } catch (\Exception $e) {
            Log::warning("Failed to fetch raw expenses from Odoo: " . $e->getMessage() . ". Falling back to cache.");
            return \Illuminate\Support\Facades\Cache::get($cacheKey) ?: [];
        }
    }

    public function getLatestTransactionDate(): ?string
    {
        try {
            $this->authenticate();
            $latest = $this->executeKw('account.move.line', 'search_read', [[['move_id.state', '=', 'posted']]], ['fields' => ['date'], 'limit' => 1, 'order' => 'date desc']);
            return $latest[0]['date'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function fetchCoaAccounts(): array
    {
        $cacheKey = 'odoo_coa_accounts';
        try {
            $this->authenticate();
            $accounts = $this->executeKw('account.account', 'search_read', [[['deprecated', '=', false]]], ['fields' => ['id', 'code', 'name']]);
            if (is_array($accounts)) {
                \Illuminate\Support\Facades\Cache::put($cacheKey, $accounts, 3600); // 1 hour
            }
            return $accounts;
        } catch (\Exception $e) {
            Log::warning("Failed to fetch COA accounts from Odoo: " . $e->getMessage() . ". Falling back to cache.");
            return \Illuminate\Support\Facades\Cache::get($cacheKey) ?: [];
        }
    }

    public function syncSingleExpense(string $moveLineId): ?Expense
    {
        try {
            $this->authenticate();
            $fields = [
                'id',
                'name',
                'date',
                'quantity',
                'debit',
                'credit',
                'analytic_distribution',
                'account_id',
                'ref',
                'move_id',
            ];
            $rows = $this->executeKw('account.move.line', 'search_read', [[['id', '=', (int)$moveLineId]]], ['fields' => $fields]);
            if (empty($rows)) {
                return null;
            }
            $row = $rows[0];

            $odooAccountId = null;
            if (is_array($row['account_id'])) {
                $odooAccountId = $row['account_id'][0] ?? null;
            } else {
                $odooAccountId = $row['account_id'] ?? null;
            }

            $departmentModel = null;
            $category        = null;

            $mapping = $odooAccountId ? \App\Models\OdooCoaMapping::with('targets.department', 'targets.budgetCategory')->where('odoo_account_id', $odooAccountId)->first() : null;

            if ($mapping) {
                $targets = $mapping->targets;
                if ($targets->count() === 1) {
                    $target          = $targets->first();
                    $departmentModel = $target->department;
                    $category        = $target->budgetCategory;
                } elseif ($targets->count() > 1) {
                    $txMapping = \App\Models\OdooTransactionMapping::where('odoo_move_line_id', (string) $row['id'])->first();
                    if ($txMapping) {
                        $departmentModel = $txMapping->department;
                        $category        = $txMapping->budgetCategory;
                    }
                }
            }

            if (!$departmentModel) {
                $analyticDistribution = $row['analytic_distribution'] ?? null;
                if (is_string($analyticDistribution)) {
                    $analyticDistribution = json_decode($analyticDistribution, true);
                }
                $analyticId = null;
                if (is_array($analyticDistribution) && !empty($analyticDistribution)) {
                    $keys       = array_keys($analyticDistribution);
                    $analyticId = $keys[0] ?? null;
                }
                if ($analyticId) {
                    $departmentModel = Department::query()->where('odoo_analytic_id', (string) $analyticId)->first();
                }
            }

            if ($departmentModel && !$category) {
                $accountCodeStr = null;
                if (is_array($row['account_id'])) {
                    $displayName = trim((string) ($row['account_id'][1] ?? ''));
                    if (preg_match('/^[a-zA-Z0-9\\.\\-]+/', $displayName, $matches)) {
                        $accountCodeStr = $matches[0];
                    }
                }
                if ($accountCodeStr) {
                    $category = \App\Models\BudgetCategory::query()
                        ->where('code', $accountCodeStr)
                        ->where('department_id', $departmentModel->id)
                        ->first();
                }
            }

            if (!$departmentModel || !$category) {
                Expense::query()->where('odoo_move_line_id', (string) $row['id'])->delete();
                return null;
            }

            $amount = (float) (($row['debit'] ?? 0) - ($row['credit'] ?? 0));
            $qty    = (float) ($row['quantity'] ?? 1);
            $qty    = $qty > 0 ? $qty : 1;

            $expense = Expense::query()->updateOrCreate(
                ['odoo_move_line_id' => (string) $row['id']],
                [
                    'department_id'      => $departmentModel->id,
                    'budget_category_id' => $category->id,
                    'qty'                => $qty,
                    'amount'             => $amount,
                    'date'               => $row['date'] ?? now()->toDateString(),
                    'description'        => $row['name'] ?? 'Import from Odoo',
                    'reference'          => $row['ref'] ?? null,
                    'odoo_data'          => $row,
                    'is_synced'          => true,
                    'synced_at'          => now(),
                ]
            );

            $txMapping = \App\Models\OdooTransactionMapping::where('odoo_move_line_id', (string) $row['id'])->first();
            if ($txMapping) {
                $txMapping->update(['expense_id' => $expense->id]);
            }

            return $expense;
        } catch (\Exception $e) {
            Log::error('Gagal sync single expense ' . $moveLineId . ': ' . $e->getMessage());
            return null;
        }
    }

    public function syncMultipleExpenses(array $moveLineIds): array
    {
        if (empty($moveLineIds)) {
            return [];
        }

        $this->authenticate();

        $integerIds = array_map('intval', $moveLineIds);

        $domain = [
            ['id', 'in', $integerIds],
        ];

        $fields = [
            'id',
            'name',
            'date',
            'quantity',
            'debit',
            'credit',
            'analytic_distribution',
            'account_id',
            'ref',
            'move_id',
        ];

        $rows = $this->executeKw('account.move.line', 'search_read', [$domain], ['fields' => $fields]);

        $allMappings = \App\Models\OdooCoaMapping::with('targets.department', 'targets.budgetCategory')->get()->keyBy('odoo_account_id');

        $txMappings = \App\Models\OdooTransactionMapping::whereIn('odoo_move_line_id', array_map('strval', $moveLineIds))
            ->with('department', 'budgetCategory')
            ->get()
            ->keyBy('odoo_move_line_id');

        $syncedExpenses = [];

        foreach ($rows as $row) {
            $odooAccountId = null;
            if (is_array($row['account_id'])) {
                $odooAccountId = $row['account_id'][0] ?? null;
            } else {
                $odooAccountId = $row['account_id'] ?? null;
            }

            $departmentModel = null;
            $category        = null;

            $mapping = $odooAccountId ? ($allMappings[$odooAccountId] ?? null) : null;

            if ($mapping) {
                $targets = $mapping->targets;
                if ($targets->count() === 1) {
                    $target          = $targets->first();
                    $departmentModel = $target->department;
                    $category        = $target->budgetCategory;
                } elseif ($targets->count() > 1) {
                    $txMapping = $txMappings[(string) $row['id']] ?? null;
                    if ($txMapping) {
                        $departmentModel = $txMapping->department;
                        $category        = $txMapping->budgetCategory;
                    }
                }
            }

            if (!$departmentModel) {
                $analyticDistribution = $row['analytic_distribution'] ?? null;
                if (is_string($analyticDistribution)) {
                    $analyticDistribution = json_decode($analyticDistribution, true);
                }

                $analyticId = null;
                if (is_array($analyticDistribution) && !empty($analyticDistribution)) {
                    $keys       = array_keys($analyticDistribution);
                    $analyticId = $keys[0] ?? null;
                }

                if ($analyticId) {
                    $departmentModel = Department::query()->where('odoo_analytic_id', (string) $analyticId)->first();
                }
            }

            if ($departmentModel && !$category) {
                $accountCodeStr = null;
                if (is_array($row['account_id'])) {
                    $displayName = trim((string) ($row['account_id'][1] ?? ''));
                    if (preg_match('/^[a-zA-Z0-9\\.\\-]+/', $displayName, $matches)) {
                        $accountCodeStr = $matches[0];
                    }
                }
                if ($accountCodeStr) {
                    $category = BudgetCategory::query()
                        ->where('code', $accountCodeStr)
                        ->where('department_id', $departmentModel->id)
                        ->first();
                }
            }

            if (!$departmentModel || !$category) {
                Expense::query()->where('odoo_move_line_id', (string) $row['id'])->delete();
                continue;
            }

            $amount = (float) (($row['debit'] ?? 0) - ($row['credit'] ?? 0));
            $qty    = (float) ($row['quantity'] ?? 1);
            $qty    = $qty > 0 ? $qty : 1;

            $expense = Expense::query()->updateOrCreate(
                ['odoo_move_line_id' => (string) $row['id']],
                [
                    'department_id'      => $departmentModel->id,
                    'budget_category_id' => $category->id,
                    'qty'                => $qty,
                    'amount'             => $amount,
                    'date'               => $row['date'] ?? now()->toDateString(),
                    'description'        => $row['name'] ?? 'Import from Odoo',
                    'reference'          => $row['ref'] ?? null,
                    'odoo_data'          => $row,
                    'is_synced'          => true,
                    'synced_at'          => now(),
                ]
            );

            $txM = $txMappings[(string) $row['id']] ?? null;
            if ($txM) {
                $txM->update(['expense_id' => $expense->id]);
            }

            $syncedExpenses[] = $expense;
        }

        return $syncedExpenses;
    }

    private function getOdooUrl(): string
    {
        return (string) Setting::get('odoo_url', config('services.odoo.url', 'http://localhost:8069'));
    }

    private function getOdooDatabase(): string
    {
        return (string) Setting::get('odoo_database', config('services.odoo.database', ''));
    }

    private function getOdooUsername(): string
    {
        return (string) Setting::get('odoo_username', config('services.odoo.username', ''));
    }

    private function getOdooPassword(): string
    {
        return (string) Setting::get('odoo_password', config('services.odoo.password', ''));
    }
}
