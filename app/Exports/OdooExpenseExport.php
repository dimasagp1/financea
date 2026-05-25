<?php

namespace App\Exports;

use App\Models\BudgetCategory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OdooExpenseExport implements FromCollection, WithHeadings
{
    private $categories;

    public function __construct($categories)
    {
        $this->categories = $categories;
    }

    public function collection()
    {
        return $this->categories->map(function ($cat) {
            return [
                'no_akun'   => $cat->code,
                'nama_akun' => $cat->name,
                'nominal'   => 0, // Default for template
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No Akun',
            'Nama Akun',
            'Nominal',
        ];
    }
}
