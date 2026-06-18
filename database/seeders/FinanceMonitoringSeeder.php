<?php

namespace Database\Seeders;

use App\Models\BudgetCategory;
use App\Models\Department;
use App\Models\Expense;
use App\Models\FiscalYear;
use App\Models\MonthlyBudget;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FinanceMonitoringSeeder extends Seeder
{
    public function run(): void
    {
        $year = (string) now()->year;

        FiscalYear::query()->update(['is_active' => false]);

        $fiscalYear = FiscalYear::updateOrCreate(
            ['year' => $year],
            [
                'global_budget_amount' => 12000000000,
                'is_active' => true,
                'notes' => 'FY aktif untuk monitoring finance',
            ]
        );

        $departments = [
            ['code' => 'DPT-FAT', 'name' => 'Finance & Accounting', 'ratio' => 20, 'odoo_analytic_id' => '101'],
            ['code' => 'DPT-MKT', 'name' => 'Marketing', 'ratio' => 15, 'odoo_analytic_id' => '102'],
            ['code' => 'DPT-OPS', 'name' => 'Operations', 'ratio' => 25, 'odoo_analytic_id' => '103'],
            ['code' => 'DPT-HRD', 'name' => 'Human Resources', 'ratio' => 10, 'odoo_analytic_id' => '104'],
            ['code' => 'DPT-IT', 'name' => 'Information Technology', 'ratio' => 12, 'odoo_analytic_id' => '105'],
            ['code' => 'DPT-PRD', 'name' => 'Production', 'ratio' => 15, 'odoo_analytic_id' => '106'],
            ['code' => 'DPT-RND', 'name' => 'Research & Dev', 'ratio' => 3, 'odoo_analytic_id' => '107'],
        ];

        $departmentModels = [];

        foreach ($departments as $dept) {
            $yearlyAllocated = ($fiscalYear->global_budget_amount * $dept['ratio']) / 100;

            $department = Department::updateOrCreate(
                ['code' => $dept['code']],
                [
                    'name' => $dept['name'],
                    'budget_ratio_percent' => $dept['ratio'],
                    'yearly_allocated_amount' => $yearlyAllocated,
                    'odoo_analytic_id' => $dept['odoo_analytic_id'],
                    'fiscal_year_id' => $fiscalYear->id,
                    'is_active' => true,
                ]
            );

            $departmentModels[$dept['code']] = $department;

            $monthlyDefault = $yearlyAllocated / 12;
            for ($month = 1; $month <= 12; $month++) {
                $monthStr = Carbon::createFromDate((int) $year, $month, 1)->format('Y-m');
                MonthlyBudget::updateOrCreate(
                    [
                        'department_id' => $department->id,
                        'month' => $monthStr,
                    ],
                    [
                        'amount' => $monthlyDefault,
                        'is_overridden' => false,
                        'notes' => 'Seed default allocation',
                    ]
                );
            }
            
            // Generate 1 User per Department (optional, but good for testing)
            User::updateOrCreate(
                ['email' => strtolower(explode('-', $dept['code'])[1]) . '@finance.local'],
                [
                    'name' => 'Manager ' . $dept['name'],
                    'password' => Hash::make('password'),
                    'role' => $dept['code'] === 'DPT-FAT' ? 'fat' : 'departemen',
                    'department_id' => $department->id,
                    'is_active' => true,
                ]
            );
        }

        // Budget categories (total ratio must match department ratio)
        $categories = [
            'DPT-FAT' => [ // Dept ratio: 20
                ['name' => 'Operational Finance', 'ratio' => 5], ['name' => 'Compliance & Audit', 'ratio' => 4],
                ['name' => 'Tax Processing', 'ratio' => 4], ['name' => 'Treasury', 'ratio' => 3],
                ['name' => 'Banking Fees', 'ratio' => 2], ['name' => 'Legal Counsel', 'ratio' => 1],
                ['name' => 'Financial Software', 'ratio' => 1],
            ],
            'DPT-MKT' => [ // Dept ratio: 15
                ['name' => 'Digital Ads', 'ratio' => 4], ['name' => 'Brand Activation', 'ratio' => 3],
                ['name' => 'Event Sponsorship', 'ratio' => 2], ['name' => 'Merchandise', 'ratio' => 2],
                ['name' => 'Influencer Marketing', 'ratio' => 2], ['name' => 'SEO & Content', 'ratio' => 1],
                ['name' => 'Market Research', 'ratio' => 1],
            ],
            'DPT-OPS' => [ // Dept ratio: 25
                ['name' => 'Facility Maintenance', 'ratio' => 8], ['name' => 'Logistics & Shipping', 'ratio' => 7],
                ['name' => 'Vehicle Fuel', 'ratio' => 3], ['name' => 'Warehouse Rent', 'ratio' => 3],
                ['name' => 'Security Services', 'ratio' => 2], ['name' => 'Cleaning Supplies', 'ratio' => 1],
                ['name' => 'Utility Bills', 'ratio' => 1],
            ],
            'DPT-HRD' => [ // Dept ratio: 10
                ['name' => 'Recruitment Ads', 'ratio' => 3], ['name' => 'Employee Training', 'ratio' => 2],
                ['name' => 'Team Building', 'ratio' => 2], ['name' => 'Health Insurance', 'ratio' => 1],
                ['name' => 'Office Snacks', 'ratio' => 1], ['name' => 'Reward & Recognition', 'ratio' => 0.5],
                ['name' => 'HR Software', 'ratio' => 0.5],
            ],
            'DPT-IT' => [ // Dept ratio: 12
                ['name' => 'Cloud Hosting (AWS)', 'ratio' => 4], ['name' => 'Software Licenses', 'ratio' => 3],
                ['name' => 'Hardware Replacements', 'ratio' => 2], ['name' => 'Cybersecurity', 'ratio' => 1],
                ['name' => 'Internet Services', 'ratio' => 1], ['name' => 'IT Consultant', 'ratio' => 0.5],
                ['name' => 'Domain & SSL', 'ratio' => 0.5],
            ],
            'DPT-PRD' => [ // Dept ratio: 15
                ['name' => 'Raw Materials', 'ratio' => 5], ['name' => 'Machine Maintenance', 'ratio' => 3],
                ['name' => 'Packaging', 'ratio' => 3], ['name' => 'Quality Control', 'ratio' => 2],
                ['name' => 'Safety Gear (PPE)', 'ratio' => 1], ['name' => 'Factory Electricity', 'ratio' => 0.5],
                ['name' => 'Waste Management', 'ratio' => 0.5],
            ],
            'DPT-RND' => [ // Dept ratio: 3
                ['name' => 'Lab Equipment', 'ratio' => 1], ['name' => 'Chemical Supplies', 'ratio' => 0.8],
                ['name' => 'Prototyping', 'ratio' => 0.6], ['name' => 'Patent Registration', 'ratio' => 0.3],
                ['name' => 'Research Literature', 'ratio' => 0.1], ['name' => 'External Testing', 'ratio' => 0.1],
                ['name' => 'R&D Software', 'ratio' => 0.1],
            ],
        ];

        $categoryModels = [];
        foreach ($categories as $deptCode => $rows) {
            $dept = $departmentModels[$deptCode];
            $catCounter = 1;
            foreach ($rows as $row) {
                // Generate a code like CAT-FAT-1, CAT-FAT-2
                $catCode = 'CAT-' . explode('-', $deptCode)[1] . '-' . $catCounter;
                $allocated = ($dept->yearly_allocated_amount * $row['ratio']) / 100;

                $category = BudgetCategory::updateOrCreate(
                    [
                        'code' => $catCode,
                        'department_id' => $dept->id,
                        'fiscal_year_id' => $fiscalYear->id,
                    ],
                    [
                        'name' => $row['name'],
                        'budget_ratio_percent' => $row['ratio'],
                        'allocated_amount' => $allocated,
                        'is_active' => true,
                    ]
                );

                $categoryModels[$deptCode][] = $category;
                $catCounter++;
            }
        }

        // Ensure Superadmin exists
        User::updateOrCreate(
            ['email' => 'superadmin@finance.local'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'superadmin',
                'department_id' => null,
                'is_active' => true,
            ]
        );
    }
}
