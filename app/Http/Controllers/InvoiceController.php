<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\InvoicePdfService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    protected $invoiceService;

    public function __construct(InvoicePdfService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Download order invoice (Public - No authentication required)
     * 
     * @param int $orderId
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function downloadInvoice(Request $request,$orderId)
    {
        $extra = $request->query('extra');
        $order = Order::with(['user', 'technician', 'category'])->findOrFail($orderId);
        return $this->invoiceService->downloadInvoice($order, null, $extra);
    }

    /**
     * Download order invoice (User)
     * 
     * @param int $orderId
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function downloadUserInvoice(Request $request, $orderId)
    {
        $user = $request->user();
        
        $order = Order::where('id', $orderId)
            ->where('user_id', $user->id)
            ->with(['user', 'technician', 'category'])
            ->firstOrFail();

        return $this->invoiceService->downloadInvoice($order);
    }

    /**
     * Download order invoice (Technician)
     * 
     * @param int $orderId
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function downloadTechnicianInvoice(Request $request, $orderId)
    {
        $technician = $request->user('technician');
        
        $order = Order::where('id', $orderId)
            ->where('technician_id', $technician->id)
            ->with(['user', 'technician', 'category'])
            ->firstOrFail();

        return $this->invoiceService->downloadInvoice($order);
    }

    /**
     * Download order invoice (Admin)
     * 
     * @param int $orderId
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function downloadAdminInvoice(Request $request, $orderId)
    {
        $extra = $request->query('extra');
        $order = Order::with(['user', 'technician', 'category'])->findOrFail($orderId);

        return $this->invoiceService->downloadInvoice($order, null, $extra);
    }

    /**
     * Preview order invoice in browser (Admin)
     * 
     * @param int $orderId
     * @return \Illuminate\Http\Response
     */
    public function previewInvoice($orderId)
    {
        $order = Order::with(['user', 'technician', 'category'])->findOrFail($orderId);

        return $this->invoiceService->previewInvoice($order);
    }
}
