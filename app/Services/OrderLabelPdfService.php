<?php

namespace App\Services;

use App\Models\Order;
use TCPDF;

class OrderLabelPdfService
{
    /** A single 65 × 30 mm label, ready to print at 100% scale. */
    public function render(Order $order): string
    {
        $order->loadMissing([
            'user',
            'category.brandCategoryField.field',
            'category.modelCategoryField.field',
            'details.fieldDetail',
            'extra_services',
            'procurement.items',
        ]);

        $pdf = new TCPDF('L', 'mm', [65, 30], true, 'UTF-8', false);
        $pdf->SetCreator('Loop');
        $pdf->SetTitle('لیبل سفارش ' . $order->id);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        // The supplied artwork includes white margins above and below its frame.
        // At this scale the frame fills the requested 65 × 30 mm label.
        $pdf->Image(resource_path('images/order-label-blank.jpg'), 0, -3.25, 65, 36.45, 'JPG');

        $this->text($pdf, '#' . $order->id, 49.7, 4.13, 12.6, 2.6, 8, 'C');

        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(2.6, 9.15, 12.2, 1.9, 'F');
        $this->text($pdf, 'گیرنده:', 2.8, 9.3, 6.5, 1.6, 5.1, 'R');

        $recipient = trim((string) ($order->delivery_receiver_name ?: trim(
            ($order->user?->name ?? '') . ' ' . ($order->user?->last_name ?? '')
        )));
        $this->text($pdf, $recipient ?: '—', 2.8, 11.25, 13.7, 2.3, 6.5, 'C');
        $this->text($pdf, (string) ($order->user?->code ?: $order->user_id), 17.3, 11.45, 13.9, 2.3, 7.0, 'C');
        $this->text($pdf, $this->receiptStatus($order), 33.1, 11.25, 11.1, 2.3, 6.7, 'C');

        $items = $this->items($order);
        if (count($items) > 3) {
            $remaining = count($items) - 2;
            $items = array_slice($items, 0, 2);
            $items[] = ['name' => "+{$remaining} مورد دیگر", 'quantity' => ''];
        }

        foreach ($items as $index => $item) {
            $y = 17.7 + ($index * 2.5);
            $this->text($pdf, $item['name'], 4.1, $y, 16.7, 2.45, 6.2, 'C');
            $this->text($pdf, (string) $item['quantity'], 25.9, $y, 6.3, 2.45, 6.7, 'C');
        }

        $this->text($pdf, $this->shippingStatus($order), 35.0, 19.75, 9.2, 3.0, 6.4, 'C');

        // Replace the example QR printed in the artwork with this order's receipt URL.
        $pdf->setRTL(false);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(47.9, 9.05, 14.75, 14.4, 'F');
        $pdf->write2DBarcode(
            route('web.reciept', ['id' => $order->id]),
            'QRCODE,M',
            49.5,
            10.55,
            11.5,
            11.5,
            ['border' => 0, 'padding' => 0, 'fgcolor' => [0, 0, 0], 'bgcolor' => [255, 255, 255]],
            'N'
        );

        return $pdf->Output('order-label-' . $order->id . '.pdf', 'S');
    }

    /** @return list<array{name: string, quantity: int|string}> */
    private function items(Order $order): array
    {
        if ($order->procurement?->items->isNotEmpty()) {
            return $order->procurement->items->map(fn ($item) => [
                'name' => $this->clean((string) $item->title),
                'quantity' => max(1, (int) $item->quantity),
            ])->all();
        }

        $brandField = $order->category?->brandCategoryField?->field;
        $modelField = $order->category?->modelCategoryField?->field;
        $brandDetail = $brandField ? $order->details->firstWhere('field_id', $brandField->id) : null;
        $modelDetail = $modelField ? $order->details->firstWhere('field_id', $modelField->id) : null;
        $brand = $brandDetail?->fieldDetail?->title ?: $brandDetail?->value;
        $model = $modelDetail?->fieldDetail?->title ?: $modelDetail?->value;
        $primary = $this->clean(trim(($brand ?: '') . ' ' . ($model ?: '')));

        $items = [[
            'name' => $primary ?: $this->clean((string) ($order->category?->title ?: 'محصول سفارش')),
            'quantity' => 1,
        ]];

        foreach ($order->extra_services as $extra) {
            $items[] = [
                'name' => $this->clean(trim(($extra->title ?: '') . ' ' . ($extra->model ?: ''))),
                'quantity' => max(1, (int) ($extra->number ?: 1)),
            ];
        }

        return $items;
    }

    private function receiptStatus(Order $order): string
    {
        return match ((int) $order->status) {
            2 => 'موفق',
            3, 4, 5, 6 => 'ناموفق',
            default => 'در جریان',
        };
    }

    private function shippingStatus(Order $order): string
    {
        if ($order->shipment_status) {
            return 'توسط ' . $order->shipment_status;
        }

        return match ($order->delivery_method) {
            'user' => 'توسط کاربر',
            'loop_technician' => 'تکنسین لوپ',
            'courier' => 'توسط پیک',
            default => 'ثبت نشده',
        };
    }

    private function clean(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    private function text(TCPDF $pdf, string $value, float $x, float $y, float $width, float $height, float $size, string $align): void
    {
        $value = $this->clean($value);
        $rtl = (bool) preg_match('/[\x{0600}-\x{06FF}]/u', $value);
        $pdf->setRTL($rtl);
        $pdf->SetTextColor(4, 20, 51);
        $pdf->SetFont('dejavusans', 'B', $size);

        while ($size > 5 && $pdf->GetStringWidth($value) > $width) {
            $size -= 0.25;
            $pdf->SetFontSize($size);
        }

        while (mb_strlen($value) > 1 && $pdf->GetStringWidth($value) > $width) {
            $value = mb_substr($value, 0, -1);
        }

        // TCPDF measures X from the right edge while RTL is enabled.
        $pdf->SetXY($rtl ? 65 - $x - $width : $x, $y);
        $pdf->Cell($width, $height, $value, 0, 0, $align, false, '', 0, false, 'T', 'M');
    }
}
