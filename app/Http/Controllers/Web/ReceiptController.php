<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PdfReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReceiptController extends Controller
{
    public function __construct(
        private PdfReceiptService $pdfService
    ) {}

    /**
     * Download PDF receipt using web session authentication
     */
    public function downloadPdf(Request $request, Order $order)
    {
        // Check authorization for both customers and admins
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Authentication required'
            ], 401);
        }

        // Customer can only access their own orders
        // Admin/Merchant can access any order
        if ($order->user_id !== $user->id && !in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'message' => 'Unauthorized access to this receipt'
            ], 403);
        }

        try {
            return $this->pdfService->downloadReceipt($order);
        } catch (\Exception $e) {
            \Log::error('Failed to generate PDF receipt via web route', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to generate PDF receipt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview receipt data (JSON)
     */
    public function preview(Request $request, Order $order)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Authentication required'
            ], 401);
        }

        if ($order->user_id !== $user->id && !in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'message' => 'Unauthorized access to this receipt'
            ], 403);
        }

        try {
            $order->load([
                'user',
                'address', 
                'items.productVariant.product',
                'events'
            ]);

            return response()->json([
                'success' => true,
                'order' => $order,
                'receipt_info' => [
                    'receipt_number' => 'RCP-' . $order->order_number,
                    'generated_at' => now()->toISOString(),
                    'can_download' => true
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to load receipt data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin specific method to download any receipt
     */
    public function adminDownload(Request $request, Order $order)
    {
        $user = Auth::user();
        
        if (!$user || !in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'message' => 'Admin access required'
            ], 403);
        }

        try {
            return $this->pdfService->downloadReceipt($order);
        } catch (\Exception $e) {
            \Log::error('Admin failed to generate PDF receipt', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'admin_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to generate PDF receipt',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}