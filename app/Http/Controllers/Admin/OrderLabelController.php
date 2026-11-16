<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderLabelPdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\Process\Process;

class OrderLabelController extends Controller
{
    public function __invoke(Order $order, OrderLabelPdfService $labels, Request $request): Response
    {
        $pdf = $labels->render($order);

        if ($request->query('format') === 'jpeg') {
            $converter = new Process(['pdftoppm', '-jpeg', '-singlefile', '-r', '300', '-']);
            $converter->setInput($pdf);
            $converter->setTimeout(10);
            $converter->mustRun();

            return response($converter->getOutput(), 200, [
                'Content-Type' => 'image/jpeg',
                'Content-Disposition' => 'attachment; filename="order-label-' . $order->id . '.jpg"',
                'Cache-Control' => 'private, no-store',
            ]);
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="order-label-' . $order->id . '.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
