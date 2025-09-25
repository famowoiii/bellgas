<?php

namespace App\Services;

use App\Models\Order;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class PdfReceiptService
{
    private Dompdf $dompdf;
    
    public function __construct()
    {
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', true);
        $options->set('isPhpEnabled', true);
        
        $this->dompdf = new Dompdf($options);
    }

    /**
     * Generate PDF receipt for an order
     */
    public function generateReceipt(Order $order): string
    {
        try {
            // Load order with all necessary relationships
            $order->load([
                'user',
                'address',
                'items.productVariant.product',
                'events' => function($query) {
                    $query->orderBy('created_at', 'desc');
                }
            ]);

            // Generate HTML content
            $html = $this->generateReceiptHtml($order);

            // Convert HTML to PDF
            $this->dompdf->loadHtml($html);
            $this->dompdf->setPaper('A4', 'portrait');
            $this->dompdf->render();

            // Get PDF content
            $pdfContent = $this->dompdf->output();

            // Save to storage
            $filename = 'receipts/receipt-' . $order->order_number . '-' . time() . '.pdf';
            Storage::put($filename, $pdfContent);

            Log::info('PDF receipt generated successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'filename' => $filename
            ]);

            return $filename;
        } catch (\Exception $e) {
            Log::error('Failed to generate PDF receipt', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate HTML content for the receipt
     */
    private function generateReceiptHtml(Order $order): string
    {
        $data = [
            'order' => $order,
            'company' => [
                'name' => config('app.name', 'BellGas'),
                'address' => config('app.company_address', '123 Main Street, Sydney NSW 2000'),
                'phone' => config('app.company_phone', '+61 2 1234 5678'),
                'email' => config('app.company_email', 'orders@bellgas.com.au'),
                'abn' => config('app.company_abn', '12 345 678 901'),
                'website' => config('app.url'),
            ],
            'generated_at' => now(),
        ];

        return view('pdf.receipt', $data)->render();
    }

    /**
     * Get PDF content as string
     */
    public function getReceiptContent(Order $order): string
    {
        $filename = $this->generateReceipt($order);
        return Storage::get($filename);
    }

    /**
     * Download PDF receipt
     */
    public function downloadReceipt(Order $order): \Symfony\Component\HttpFoundation\Response
    {
        $filename = $this->generateReceipt($order);
        $downloadName = 'Receipt-' . $order->order_number . '.pdf';

        return Storage::download($filename, $downloadName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Get PDF receipt URL (for preview)
     */
    public function getReceiptUrl(Order $order): string
    {
        $filename = $this->generateReceipt($order);
        return Storage::url($filename);
    }

    /**
     * Generate receipt for email attachment
     */
    public function generateForEmail(Order $order): array
    {
        try {
            $filename = $this->generateReceipt($order);
            $content = Storage::get($filename);
            $downloadName = 'Receipt-' . $order->order_number . '.pdf';

            return [
                'filename' => $downloadName,
                'content' => $content,
                'mime_type' => 'application/pdf'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to generate PDF receipt for email', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Clean up old receipts (for maintenance)
     */
    public function cleanupOldReceipts(int $daysOld = 30): int
    {
        $files = Storage::files('receipts');
        $deletedCount = 0;
        $cutoffDate = now()->subDays($daysOld);

        foreach ($files as $file) {
            $lastModified = Storage::lastModified($file);
            
            if ($lastModified && $lastModified < $cutoffDate->timestamp) {
                Storage::delete($file);
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            Log::info('Cleaned up old PDF receipts', [
                'deleted_count' => $deletedCount,
                'days_old' => $daysOld
            ]);
        }

        return $deletedCount;
    }
}