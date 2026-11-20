<?php

namespace App\Exports;

use App\Models\ManualLabelProductionOrder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ManualLabelOrdersExport extends StringValueBinder implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithCustomValueBinder, WithColumnFormatting
{
    public function __construct(private readonly string $start, private readonly string $end)
    {
        $this->setNumericConversion(false);
        $this->setNullConversion(false);
    }

    public function columnFormats(): array
    {
        return ['C' => 'dd/mm/yyyy', 'F' => 'dd/mm/yyyy', 'G' => 'dd/mm/yyyy'];
    }

    public function query(): Builder
    {
        return ManualLabelProductionOrder::query()->where('order_date', '>=', $this->start)
            ->where('order_date', '<', Carbon::parse($this->end)->addDay()->toDateString())
            ->orderBy('order_date')->orderBy('id');
    }

    public function headings(): array
    {
        return ['label_code', 'production_order', 'order_date', 'name', 'batch', 'mfg_date', 'exp_date', 'pack_spec', 'quantity', 'label_type', 'traditional_medicine_name', 'qr_content', 'storage'];
    }

    public function map($order): array
    {
        return [
            $order->label_code, $order->production_order, $order->order_date ? Date::dateTimeToExcel($order->order_date) : null,
            $order->name, $order->batch, $order->mfg_date ? Date::dateTimeToExcel($order->mfg_date) : null,
            $order->exp_date ? Date::dateTimeToExcel($order->exp_date) : null,
            $order->pack_spec, $order->quantity, $order->label_type,
            $order->traditional_medicine_name === null ? null : (string) (int) $order->traditional_medicine_name,
            $order->qr_content, $order->storage,
        ];
    }
}
