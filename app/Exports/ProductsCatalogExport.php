<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ProductsCatalogExport implements FromView, ShouldAutoSize, WithEvents
{
    public function __construct(
        private readonly mixed $products
    ) {
    }

    public function view(): View
    {
        return view('admin.products.format_excel', [
            'products' => $this->products,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $richText = new RichText();
                $richText->createText("Plantilla Mixta de Catálogo:\n- tipo_item: PRODUCTO o SERVICIO\n- almacen_destino y stock_inicial: Obligatorios solo para PRODUCTO\n- Para nuevos productos deje product_id vacío.");

                $event->sheet->getDelegate()->getComment('A1')
                    ->setAuthor('Sistema')
                    ->setText($richText);

                $sheet = $event->sheet->getDelegate();
                $lastRow = max(2, $sheet->getHighestRow());

                $sheet->getStyle("J2:L{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_TEXT);
            },
        ];
    }
}
