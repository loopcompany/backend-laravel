<?php

namespace App\Services;

use App\Models\Order;
use TCPDF;

/**
 * لیبل ۶٫۵ × ۳ سانتی‌متری سفارش — تماماً برداری (بدون تصویر زمینه) و تک‌رنگ
 * تا روی چاپگر حرارتی و لیزری یکسان و خوانا چاپ شود.
 *
 * مختصات به میلی‌متر و از گوشه‌ی بالا-چپ است؛ جهت راست‌به‌چپ متن‌ها را
 * الگوریتم bidi خود TCPDF تشخیص می‌دهد، پس صفحه همیشه LTR می‌ماند.
 */
class OrderLabelPdfService
{
    private const WIDTH = 65;
    private const HEIGHT = 30;

    private const INK = [0, 0, 0];
    private const PAPER = [255, 255, 255];

    private const MAX_ITEM_LINES = 3;

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

        $pdf = new TCPDF('L', 'mm', [self::WIDTH, self::HEIGHT], true, 'UTF-8', false);
        $pdf->SetCreator('Loop');
        $pdf->SetTitle('لیبل سفارش ' . $order->id);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->setCellPaddings(0, 0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->setRTL(false);

        $fonts = resource_path('fonts/vazirmatn');
        $pdf->AddFont('vazirmatn', '', $fonts . '/vazirmatnmedium.php');
        $pdf->AddFont('vazirmatn', 'B', $fonts . '/vazirmatnb.php');
        $pdf->AddFont('vazirmatnblack', '', $fonts . '/vazirmatnblack.php');

        $pdf->AddPage();

        $this->drawFrame($pdf);
        $this->drawHeader($pdf, $order);
        $this->drawQr($pdf, $order);
        $this->drawDetails($pdf, $order);
        $this->drawItems($pdf, $order);
        $this->drawFooter($pdf);

        return $pdf->Output('order-label-' . $order->id . '.pdf', 'S');
    }

    private function drawFrame(TCPDF $pdf): void
    {
        $pdf->RoundedRect(0.9, 0.9, 63.2, 28.2, 1.8, '1111', 'D', $this->stroke(0.3));
    }

    private function drawHeader(TCPDF $pdf, Order $order): void
    {
        // Logo + website on the left.
        $pdf->Image(resource_path('images/order-label-logo.png'), 2.5, 2.0, 0, 5.4, 'PNG');
        $pdf->Line(16.4, 2.7, 16.4, 7.0, $this->stroke(0.25));
        $this->text($pdf, 'clpiran.com', 17.6, 3.2, 21.0, 3.0, 7.6, 'B', 'L');

        // Order number block on the right, with the brand's slanted edge.
        $pdf->RoundedRect(43.6, 0.9, 20.5, 7.6, 1.8, '1000', 'F', [], self::INK);
        $pdf->Polygon([43.6, 0.9, 43.6, 8.5, 41.4, 8.5], 'F', [], self::INK);
        $pdf->Polygon([41.9, 0.9, 42.8, 0.9, 40.6, 8.5, 39.7, 8.5], 'F', [], self::INK);
        $pdf->Line(0.9, 8.5, 41.4, 8.5, $this->stroke(0.3));

        $this->text($pdf, 'شماره سفارش', 45.0, 1.55, 17.9, 1.9, 5.0, '', 'R', self::PAPER);
        $this->text($pdf, '#' . $this->digits($order->id), 44.2, 3.55, 19.2, 4.3, 12.5, 'black', 'C', self::PAPER, 8);
    }

    private function drawQr(TCPDF $pdf, Order $order): void
    {
        $pdf->write2DBarcode(
            route('web.reciept', ['id' => $order->id]),
            'QRCODE,M',
            2.5,
            9.6,
            13.4,
            13.4,
            ['border' => 0, 'padding' => 0, 'fgcolor' => self::INK, 'bgcolor' => self::PAPER],
            'N'
        );
        $this->text($pdf, 'اسکن برای جزئیات', 1.8, 23.2, 14.8, 1.8, 4.8, '', 'C');

        $pdf->Line(17.6, 9.6, 17.6, 24.6, $this->stroke(0.2));
    }

    /** Middle column: user code, receipt status and shipping, stacked. */
    private function drawDetails(TCPDF $pdf, Order $order): void
    {
        $rows = [
            ['شناسه کاربری', $this->digits($order->user?->code ?: $order->user_id)],
            ['وضعیت رسید', $this->receiptStatus($order)],
            ['وضعیت ارسال', $this->shippingStatus($order)],
        ];

        foreach ($rows as $index => [$label, $value]) {
            $y = 9.5 + ($index * 5.2);
            if ($index > 0) {
                $pdf->Line(18.7, $y - 0.2, 35.9, $y - 0.2, $this->stroke(0.12));
            }
            $this->text($pdf, $label, 18.7, $y, 17.2, 1.9, 4.8, '', 'R');
            $this->text($pdf, $value ?: '—', 18.7, $y + 1.95, 17.2, 2.8, 7.0, 'B', 'R');
        }

        $pdf->Line(36.9, 9.6, 36.9, 24.6, $this->stroke(0.2));
    }

    /** Right column: recipient on top, products with quantities below. */
    private function drawItems(TCPDF $pdf, Order $order): void
    {
        $recipient = trim((string) ($order->delivery_receiver_name ?: trim(
            ($order->user?->name ?? '') . ' ' . ($order->user?->last_name ?? '')
        )));

        $this->text($pdf, 'گیرنده', 38.0, 9.5, 25.2, 1.9, 4.8, '', 'R');
        $this->text($pdf, $recipient ?: '—', 38.0, 11.45, 25.2, 3.0, 8.2, 'B', 'R');

        $pdf->Line(38.0, 14.9, 63.2, 14.9, $this->stroke(0.12));

        $this->text($pdf, 'محصولات', 42.4, 15.3, 20.8, 1.9, 4.8, '', 'R');
        $this->text($pdf, 'تعداد', 38.0, 15.3, 4.4, 1.9, 4.8, '', 'C');

        $items = $this->items($order);
        if (count($items) > self::MAX_ITEM_LINES) {
            $remaining = count($items) - (self::MAX_ITEM_LINES - 1);
            $items = array_slice($items, 0, self::MAX_ITEM_LINES - 1);
            $items[] = ['name' => 'و ' . $this->digits($remaining) . ' مورد دیگر', 'quantity' => ''];
        }

        foreach ($items as $index => $item) {
            $y = 17.3 + ($index * 2.4);
            $this->text($pdf, $item['name'], 42.4, $y, 20.8, 2.4, 6.6, $item['quantity'] === '' ? '' : 'B', 'R');
            if ($item['quantity'] !== '') {
                $this->text($pdf, $this->digits($item['quantity']), 38.0, $y, 4.4, 2.4, 6.6, 'B', 'C');
            }
        }
    }

    private function drawFooter(TCPDF $pdf): void
    {
        $pdf->Line(1.8, 25.4, 63.2, 25.4, $this->stroke(0.2));
        $this->text($pdf, $this->digits('021-91693909'), 2.0, 25.85, 17.0, 2.7, 6.6, 'B', 'L');
        $this->text($pdf, 'با لوپ تا بی‌نهایت در کنار هم هستیم', 24.0, 25.85, 39.0, 2.7, 5.4, '', 'R');
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

    private function digits(int|string|null $value): string
    {
        return strtr((string) $value, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }

    private function stroke(float $width): array
    {
        return ['width' => $width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => self::INK];
    }

    /**
     * One line of text fitted to its box: shrinks down to $minSize, then cuts with an ellipsis.
     *
     * @param  string  $style  '' (medium), 'B' (bold) or 'black'
     */
    private function text(
        TCPDF $pdf,
        string $value,
        float $x,
        float $y,
        float $width,
        float $height,
        float $size,
        string $style,
        string $align,
        array $color = self::INK,
        float $minSize = 4.6,
    ): void {
        $value = $this->clean($value);
        $style === 'black' ? $pdf->SetFont('vazirmatnblack', '', $size) : $pdf->SetFont('vazirmatn', $style, $size);
        $pdf->SetTextColorArray($color);

        while ($size > $minSize && $pdf->GetStringWidth($value) > $width) {
            $size -= 0.2;
            $pdf->SetFontSize($size);
        }

        if ($pdf->GetStringWidth($value) > $width) {
            while (mb_strlen($value) > 1 && $pdf->GetStringWidth($value . '…') > $width) {
                $value = rtrim(mb_substr($value, 0, -1));
            }
            $value .= '…';
        }

        $pdf->SetXY($x, $y);
        $pdf->Cell($width, $height, $value, 0, 0, $align, false, '', 0, true, 'T', 'M');
    }
}
