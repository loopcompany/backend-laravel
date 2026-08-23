<?php

namespace App\Services;

use App\Models\Order;
use Elibyy\TCPDF\Facades\TCPDF;
use Illuminate\Support\Facades\Storage;
use Log;
use Morilog\Jalali\Jalalian;
use TCPDF_FONTS;

class InvoicePdfService
{
    /**
     * Generate PDF invoice for an order
     *
     * @param Order $order
     * @return \TCPDF
     * @param string $extra
     */
    public function generateOrderInvoice(Order $order, string $extra = null)
    {
        Log::info($extra);
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('Loop System');
        $pdf->SetAuthor('Loop');
        $pdf->SetTitle('فاکتور سفارش شماره ' . $order->id);
        $pdf->SetSubject('Invoice');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(15, 0, 15);
        $pdf->SetAutoPageBreak(true, 15);
        // Add a page
        $pdf->AddPage();

        // Set Persian font (DejaVu Sans supports Persian/Farsi characters)

        $fontPath = public_path('assets/Yekan/iranyekanwebmediumfanum.ttf');

        $fontName = TCPDF_FONTS::addTTFfont($fontPath, 'TrueTypeUnicode', '', 32);

        // set RTL + font
        $pdf->setRTL(true);
        $pdf->SetFont($fontName, '', 12);

        // Generate HTML content
        $html = $this->generateInvoiceHtml($order, $extra);

        // Write HTML content
        $pdf->writeHTML($html, true, false, true, false, '');

        return $pdf;
    }

    /**
     * Generate HTML content for invoice
     *
     * @param Order $order
     * @param string $extra
     * @return string
     */
    protected function generateInvoiceHtml(Order $order, string $extra = null): string
    {
        $user = $order->user;
        $groupedDetails = $order->details->groupBy('field_id');

        $technician = $order->technician;
        $category = $order->category;
        $basePrice = $order->technician_price ?? $order->pakar_price ?? 0;
        $extraPrice = $order->extra_price ?? 0;
        $discountPrice = $order->discount_price ?? 0;
        $platform = '';

        if ($order->platform == 'android' || $order->platform == 'ios') {
            $platform = 'اپلیکیشن';
        } else if ($order->platform == 'web') {
            $platform = 'وبسایت';
        } else if ($order->platform == 'phone') {
            $platform = 'تلفنی';
        } else {
            $platform = 'حضوری';
        }
        $totalBeforeDiscount = $basePrice + $extraPrice;
        $finalPrice = max(0, $totalBeforeDiscount - $discountPrice);

        $orderDate = $order->created_at
            ? Jalalian::fromDateTime($order->created_at)->format('Y/m/d')
            : '-';
        $orderTime = $order->created_at
            ? Jalalian::fromDateTime($order->created_at)->format('H:i')
            : '-';

        $completedDate = $order->completed_at
            ? Jalalian::fromDateTime($order->completed_at)->format('Y/m/d H:i')
            : '-';
        $userOrganizationRow = '';

        if ($user->isOrganization()) {
            $userOrganizationRow = '
                    <tr>
                        <td class="label" align="left">وضعیت کاربری</td>
                        <td class="dots"></td>
                        <td class="value">' . $user->organization->organization_name . '</td>
                    </tr>
                ';
        }

        $loop_des = '';
        if ($order->loop_description && ($order->status == 0 || $order->status == 1) && !$extra) {
            $loop_des = '<tr>
                        <td class="label" align="left">توضیحات لوپ</td>
                        <td class="dots"></td>
                        <td class="value">' . $order->loop_description . '</td>
                    </tr>';
        }
        $loop_cost_estimate = '';
        if ($order->loop_cost_estimate && ($order->status == 0 || $order->status == 1) && !$extra) {
            $LTR = "\xE2\x80\x8E";
            $loop_cost_estimate = '<tr>
                        <td class="label" align="left">حدود جمع هزینه</td>
                        <td class="dots"></td>
                        <td class="value">تومان ' .$LTR. number_format($order->loop_cost_estimate) . ' </td>
                    </tr>';
        }
        $total_price = '';
        if ($order->status == '2' && !$extra) {
            $showing_price = $order->payment_price(true);
            $LTR = "\xE2\x80\x8E";
            $total_price = '<tr>
                        <td class="label" align="left">جمع هزینه کل</td>
                        <td class="dots"></td>
                        <td class="value">تومان ' .$LTR. number_format($showing_price) . ' </td>
                    </tr>';
        }else if($extra){
            $showing_price = $order->extra_price;
            $LTR = "\xE2\x80\x8E";

            $total_price = '<tr>
                        <td class="label" align="left">جمع هزینه کل</td>
                        <td class="dots"></td>
                        <td class="value">تومان ' . $LTR . number_format($showing_price) . ' </td>
                    </tr>';
        }
        $travel_and_tuition_fees='';
        if(!$extra && $order->status == '2'){
            $travel_and_tuition_fees='<tr>
                        <td class="label" align="left">هزینه ایاب ذهاب و کارشناسی</td>
                        <td class="dots"></td>
                        <td class="value">' . number_format(200000) . ' تومان</td>
                    </tr>';
        }
        $payment_status = '';

        if ($order->status == '2') {
            if ($order->payment_status == 1) {
                $pay_way = '';
                if (!$order->user_transaction) {
                    $pay_way = 'لوپ';
                } else if ($order->user_transaction->type == '2') {
                    $pay_way = 'درگاه پرداخت';
                } else if ($order->user_transaction->type == '3') {
                    $pay_way = 'اعتباری';
                }

                $payment_status = '<tr>
                            <td class="label">وضعیت سفارش</td>
                            <td class="dots"></td>
                            <td class="value">تسویه شده ' . $pay_way . '</td>
                        </tr>';
            }
        }

        $imagePath = asset('assets/withloop.png');
        $logoPath = asset('assets/logo-top.png');
        $status = $order->status;
        if ($extra) {
            $star = asset('assets/yellow-star.png');

        } else if (in_array($status, [3, 4, 5])) {
            $star = asset('assets/red-star.png');
        } elseif ($status == 2) {
            $star = asset('assets/green-star.png');
        } else { // 0 , 1
            $star = asset('assets/blue-star.png');
        }
        $extra_service_items = '';
        $detailsRows = '';

        if (!$extra) {
            $groupedDetails = $order->details->groupBy('field_id');


            foreach ($groupedDetails as $fieldId => $details) {

                $field = $details->first()->field;

                if (!$field) {
                    continue;
                }

                $values = [];

                foreach ($details as $detail) {

                    if (!($detail->field && $detail->fieldDetail)) {
                        continue;
                    }

                    $title = $detail->fieldDetail->title;
                    $value = $detail->value;

                    switch ($detail->field->type) {

                        case 'input':
                        case 'counter':
                            $values[] = $title . ' : ' . $value;
                            break;

                        case 'checkbox':
                            $count = ($detail->fieldDetail->has_counter && $value > 1)
                                ? ' ' . $value
                                : '';

                            $values[] = ' ' . $title . $count;
                            break;

                        case 'radioButton':
                            $count = ($detail->fieldDetail->has_counter && $value > 1)
                                ? ' ' . $value
                                : '';

                            $values[] = '' . $title . $count;
                            break;

                        default:
                            $values[] = $title;
                    }
                }

                $detailsValue = implode(' ، ', $values);

                $detailsRows .= '
                                <tr>
                                    <td class="label" align="left">' . $field->title . '</td>
                                    <td class="dots"></td>
                                    <td class="value">' . $detailsValue . '</td>
                                </tr>
                            ';
            }
        } else {
            $extra_services = $order->extra_services;
            $detailsValue = '';
            $extra_service_items = '';
            foreach ($extra_services as $extra_service) {

                $values[] = $extra_service->title;


                $detailsValue = implode(' ، ', $values);

            }
            $extra_service_items .= '
                                <tr>
                                    <td class="label" align="left">قطعه / محصول</td>
                                    <td class="dots"></td>
                                    <td class="value">' . $detailsValue . '</td>
                                </tr>
                            ';
        }

        $html = '
        <html dir="rtl"> 
        <body>

            <style>
                body{
                    direction: rtl;
                    text-align:right;
                    font-size:12px;
                    font-family: byekan
                }

                .title{
                    text-align:center;
                    font-size:20px;
                    font-weight:bold;
                    margin-bottom:10px;
                }

                .row{
                    width:100%;
                    margin-bottom:6px;
                }

                table{
                    width:100%;
                }

                td{
                    font-size:11px;
                }

                .label{
                    width:35%;
                    font-weight:bold;
                }

                .dots{
                    width:40%;
                    border-bottom:1px dotted #555;
                }

                .value{
                    width:25%;
                    text-align:right;
                    direction:ltr;
                    padding-right:10px
                }

                .section{
                    margin-top:15px;
                    margin-bottom:5px;
                    font-size:14px;
                    font-weight:bold;
                }

                .total{
                    font-size:14px;
                    font-weight:bold;
                    margin-top:10px;
                }

                .footer{
                    text-align:center;
                    margin-top:30px;
                    font-size:10px;
                }
            </style>

            <table border="0" cellpadding="10" cellspacing="0" width="100%">
                <tr>
                    <td width="70%" align="left">
                        <img src="' . $logoPath . '" style="width:200px;height:120px;">
                    </td>
                    <td width="30%" align="right">
                        <img src="' . $star . '" style="width:60px;height:60px;">
                    </td>
                </tr>
            </table>

            <div class="row">
                <table>
                    <tr>
                        <td class="label" align="left">شماره سفارش</td>
                        <td class="dots"></td>
                        <td class="value">' . $order->id . '</td>
                    </tr>

                    <tr>
                        <td class="label" align="left">کد کاربری</td>
                        <td class="dots"></td>
                        <td class="value">' . $user->code . '</td>
                    </tr>
                    <tr>
                        <td class="label" align="left">ثبت سفارش</td>
                        <td class="dots"></td>
                        <td class="value">' . $platform . '</td>
                    </tr>

                    <tr>
                        <td class="label" align="left">نوع محصول</td>
                        <td class="dots"></td>
                        <td class="value">' . $category->title . '</td>
                    </tr>

                    <tr>
                        <td class="label" align="left">نام کاربری</td>
                        <td class="dots"></td>
                        <td class="value">' . ($user->name . ' ' . $user->last_name) . '</td>
                    </tr>
                    ' . $userOrganizationRow . '
                    <tr>
                        <td class="label" align="left">تاریخ ثبت سفارش</td>
                        <td class="dots"></td>
                        <td class="value">' . $orderDate . '</td>
                    </tr>
                    <tr>
                        <td class="label" align="left">ساعت ثبت سفارش</td>
                        <td class="dots"></td>
                        <td class="value">' . $orderTime . '</td>
                    </tr>
                    ' . $extra_service_items . '
                    ' . $detailsRows . '
                    ' . $loop_des . '
                    ' . $loop_cost_estimate . '
                    '.$travel_and_tuition_fees.'
                    ' . $total_price . '
                    ' . $payment_status . '
                </table>
            </div>
            <div style="text-align:center;margin-top:40px;">
                <img src="' . $imagePath . '" style="width:400px;">
            </div>
                    </body>
        </html>
            ';

        return $html;
    }


    /**
     * Download invoice as PDF
     *
     * @param Order $order
     * @param string $filename
     * @param string $extra
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function downloadInvoice(Order $order, string $filename = null, string $extra = null)
    {
        $pdf = $this->generateOrderInvoice($order, $extra);

        if (!$filename) {
            $filename = 'invoice_' . $order->id . '_' . date('YmdHis') . '.pdf';
        }

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->Output('', 'S');
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Save invoice to storage
     *
     * @param Order $order
     * @param string $path
     * @return string
     */
    public function saveInvoice(Order $order, string $path = null): string
    {
        $pdf = $this->generateOrderInvoice($order, '');

        if (!$path) {
            $path = 'invoices/' . date('Y/m') . '/invoice_' . $order->id . '_' . date('YmdHis') . '.pdf';
        }

        $pdfContent = $pdf->Output('', 'S');
        Storage::disk('public')->put($path, $pdfContent);

        return $path;
    }

    /**
     * Preview invoice in browser
     *
     * @param Order $order
     * @return \Illuminate\Http\Response
     */
    public function previewInvoice(Order $order)
    {
        $pdf = $this->generateOrderInvoice($order, '');

        return response($pdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="invoice_' . $order->id . '.pdf"'
        ]);
    }
}
