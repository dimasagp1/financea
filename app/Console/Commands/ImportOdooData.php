<?php
// app/Console/Commands/ImportOdooData.php

namespace App\Console\Commands;

use App\Models\Department;
use App\Services\OdooSyncService;
use Illuminate\Console\Command;

class ImportOdooData extends Command
{
    protected $signature = 'odoo:import 
                            {--type=expenses : Jenis data yang diimport (expenses)}
                            {--department= : Import untuk department tertentu}
                            {--date-from= : Tanggal mulai (Y-m-d)}
                            {--date-to= : Tanggal akhir (Y-m-d)}';
    
    protected $description = 'Import data dari Odoo ke sistem finance monitoring';

    protected OdooSyncService $syncService;

    public function __construct(OdooSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    public function handle()
    {
        $this->info('🚀 Memulai proses import data dari Odoo...');

        $type = (string) $this->option('type');
        $departmentId = $this->option('department');
        $dateFrom = $this->option('date-from');
        $dateTo = $this->option('date-to');

        try {
            if ($type !== 'expenses') {
                $this->warn('Saat ini command hanya mendukung --type=expenses.');
                return 1;
            }

            $department = null;
            if ($departmentId) {
                $department = Department::find($departmentId);
                if (!$department) {
                    $this->error('Department tidak ditemukan.');
                    return 1;
                }
            }

            $result = $this->syncService->syncExpenses($department, $dateFrom, $dateTo);
            $this->displayResults($result);
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function displayResults($result)
    {
        $this->newLine();
        $this->info('✅ Import Selesai!');
        $this->table(
            ['Tipe Data', 'Total', 'Inserted', 'Updated', 'Skipped'],
            [
                [
                    'Expenses',
                    $result['total'] ?? 0,
                    $result['inserted'] ?? 0,
                    $result['updated'] ?? 0,
                    $result['skipped'] ?? 0,
                ],
            ]
        );
    }
}