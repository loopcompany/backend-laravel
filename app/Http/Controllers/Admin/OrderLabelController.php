<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderLabelPdfService;
use Illuminate\Http\Response;

class OrderLabelController extends Controller
{
    public function __invoke(Order $order, OrderLabelPdfService $labels): Response
    {
        return response($labels->render($order), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="order-label-' . $order->id . '.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
