<?php

namespace App\Exports;

use App\Models\OrderUtencilHistory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UtencilReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $rows;

    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    public function collection()
    {
        // Ensure we always have a collection
        return $this->rows instanceof \Illuminate\Support\Collection
            ? $this->rows
            : collect($this->rows);
    }

    public function headings(): array
    {
        return [
            'Order #',
            'Utencil',
            'Direction',
            'Quantity',
            'Pending Qty (Order-wise)',
            'Sender Store',
            'Receiver Store',
            'Dealer',
            'Order Status',
            'Movement Date',
            'Note',
        ];
    }

    public function map($row): array
    {
        /** @var OrderUtencilHistory $row */
        $order = $row->order;

        // Compute pending per order+utencil
        $agg = OrderUtencilHistory::selectRaw('
                SUM(CASE WHEN type = ? THEN quantity ELSE 0 END) as sent_qty,
                SUM(CASE WHEN type = ? THEN quantity ELSE 0 END) as received_qty
            ', [OrderUtencilHistory::TYPE_SENT, OrderUtencilHistory::TYPE_RECEIVED])
            ->where('order_id', $row->order_id)
            ->where('utencil_id', $row->utencil_id)
            ->first();

        $sentQty = $agg ? (float) $agg->sent_qty : 0;
        $receivedQty = $agg ? (float) $agg->received_qty : 0;
        $pending = max(0, $sentQty - $receivedQty);

        $direction = $row->type === OrderUtencilHistory::TYPE_SENT ? 'Sent' : 'Received';

        $statusLabel = $order ? \App\Models\Order::getStatuses()[$order->status] ?? 'Unknown' : 'N/A';

        return [
            $order->order_number ?? 'N/A',
            optional($row->utencil)->name ?? 'N/A',
            $direction,
            number_format($row->quantity, 2),
            number_format($pending, 2),
            optional($order->senderStore)->name ?? 'N/A',
            optional($order->receiverStore)->name ?? 'N/A',
            optional($order->dealer)->name ?? 'N/A',
            $statusLabel,
            $row->created_at ? $row->created_at->format('d-m-Y H:i') : '',
            $row->note ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Bold header row and auto-size columns
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }
}

