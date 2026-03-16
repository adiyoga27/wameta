<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ContactsTemplateExport implements FromArray, WithHeadings, WithStyles
{
    use Exportable;

    public function array(): array
    {
        return [
            [
                '6281234567890',
                'Budi Santoso',
            ],
            [
                '6289876543210',
                'Siti Aminah',
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'phone',
            'name',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true]],
        ];
    }
}
