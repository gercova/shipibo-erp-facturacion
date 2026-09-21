<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class AllProductsExport implements FromView, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(
        private readonly mixed $products
    ) {
    }

    public function view(): View
    {
        return view('admin.products.export_all_excel', [
            'products' => $this->products,
        ]);
    }

    public function title(): string
    {
        return 'Catálogo General';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(6, $sheet->getHighestRow());

                // Ensure internal codes, barcodes and SUNAT codes are formatted as text to avoid scientific notation
                $sheet->getStyle("B6:D{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_TEXT);

                // Auto-fit rows and freeze pane under the header row (row 6)
                $sheet->freezePane('A7');
            },
        ];
    }
}
