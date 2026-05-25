<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ForecastExport implements FromCollection, WithHeadings
{
    protected $categories;

    public function __construct($categories)
    {
        $this->categories = $categories;
    }

    public function collection()
    {
        return $this->categories->map(function ($cat) {
            return [
                'ID Kategori' => $cat->id,
                'Nama Kategori' => $cat->name,
                'Nominal Peramalan' => $cat->forecast_value,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID Kategori',
            'Nama Kategori',
            'Nominal Peramalan',
        ];
    }
}
