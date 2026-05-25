<?php

namespace App\Services;

use App\Models\BudgetItem;
use App\Models\Department;
use App\Models\Expense;
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

        if ($dateFrom) {
            $domain[] = ['date', '>=', $dateFrom];
        }

        if ($dateTo) {
            $domain[] = ['date', '<=', $dateTo];
        }

        if ($department?->odoo_analytic_id) {
            $domain[] = ['analytic_account_id', '=', (int) $department->odoo_analytic_id];
        }

        $fields = [
            'id',
            'name',
            'date',
            'quantity',
            'debit',
            'credit',
            'analytic_account_id',
            'account_id',
            'ref',
            'move_id',
        ];

        $rows = $this->executeKw('account.move.line', 'search_read', [$domain], ['fields' => $fields]);

        $result = [
            'total' => count($rows),
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];

        foreach ($rows as $row) {
            $analyticId = $this->extractId($row['analytic_account_id'] ?? null);
            $departmentModel = Department::query()->where('odoo_analytic_id', (string) $analyticId)->first();

            if (!$departmentModel) {
                $result['skipped']++;
                continue;
            }

            $accountCode = (string) $this->extractId($row['account_id'] ?? null);
            $item = BudgetItem::query()
                ->where('odoo_account_code', $accountCode)
                ->whereHas('category', function ($query) use ($departmentModel) {
                    $query->where('department_id', $departmentModel->id);
                })
                ->first();

            if (!$item) {
                $result['skipped']++;
                continue;
            }

            $amount = (float) (($row['debit'] ?? 0) - ($row['credit'] ?? 0));
            $amount = abs($amount);
            $qty = (float) ($row['quantity'] ?? 1);
            $qty = $qty > 0 ? $qty : 1;

            $expense = Expense::query()->updateOrCreate(
                ['odoo_move_line_id' => (string) $row['id']],
                [
                    'department_id' => $departmentModel->id,
                    'item_id' => $item->id,
                    'qty' => $qty,
                    'amount' => $amount,
                    'date' => $row['date'] ?? now()->toDateString(),
                    'description' => $row['name'] ?? 'Import from Odoo',
                    'reference' => $row['ref'] ?? null,
                    'odoo_data' => $row,
                    'is_synced' => true,
                    'synced_at' => now(),
                ]
            );

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
        $response = $this->callXmlRpc('/xmlrpc/2/common', 'authenticate', [
            config('services.odoo.database'),
            config('services.odoo.username'),
            config('services.odoo.password'),
            [],
        ]);

        if (!is_int($response) || $response <= 0) {
            throw new \RuntimeException('Autentikasi Odoo gagal. Periksa konfigurasi koneksi Odoo.');
        }

        $this->uid = $response;
    }

    private function executeKw(string $model, string $method, array $args = [], array $kwargs = []): array
    {
        if (!$this->uid) {
            throw new \RuntimeException('Sesi Odoo belum terautentikasi.');
        }

        $response = $this->callXmlRpc('/xmlrpc/2/object', 'execute_kw', [
            config('services.odoo.database'),
            $this->uid,
            config('services.odoo.password'),
            $model,
            $method,
            $args,
            $kwargs,
        ]);

        return is_array($response) ? $response : [];
    }

    private function callXmlRpc(string $path, string $method, array $params): mixed
    {
        $url = rtrim((string) config('services.odoo.url'), '/') . $path;
        $payload = $this->encodeXmlRpcRequest($method, $params);

        $httpResponse = Http::withHeaders(['Content-Type' => 'text/xml'])->withBody($payload, 'text/xml')->post($url);

        if ($httpResponse->failed()) {
            Log::error('Odoo XML-RPC HTTP error', ['status' => $httpResponse->status(), 'body' => $httpResponse->body()]);
            throw new \RuntimeException('Gagal terhubung ke Odoo XML-RPC.');
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
}
