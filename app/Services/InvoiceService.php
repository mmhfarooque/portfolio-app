<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    /**
     * Generate invoice HTML for an order.
     */
    public function generateHtml(Order $order): string
    {
        $business = [
            'name' => Setting::get('business_name', Setting::get('site_name', 'Photography')),
            'address' => Setting::get('business_address', ''),
            'email' => Setting::get('contact_email', ''),
            'phone' => Setting::get('contact_phone', ''),
        ];

        $invoiceNumber = $this->generateInvoiceNumber($order);

        return view('invoices.template', compact('order', 'business', 'invoiceNumber'))->render();
    }

    /**
     * Generate PDF invoice (requires barryvdh/laravel-dompdf).
     */
    public function generatePdf(Order $order): ?string
    {
        // Check if DomPDF is available
        if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return null;
        }

        $html = $this->generateHtml($order);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('a4')
            ->setOption('dpi', 150);

        $filename = "invoices/{$order->order_number}.pdf";
        Storage::put($filename, $pdf->output());

        return $filename;
    }

    /**
     * Generate unique invoice number.
     */
    public function generateInvoiceNumber(Order $order): string
    {
        return 'INV-' . $order->created_at->format('Y') . '-' . str_pad($order->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get invoice download URL.
     */
    public function getDownloadUrl(Order $order): ?string
    {
        $filename = "invoices/{$order->order_number}.pdf";

        if (Storage::exists($filename)) {
            return Storage::url($filename);
        }

        return null;
    }

    /**
     * Check if PDF generation is available.
     */
    public function isPdfAvailable(): bool
    {
        return class_exists(\Barryvdh\DomPDF\Facade\Pdf::class);
    }
}
