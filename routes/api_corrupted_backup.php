<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\DocsController;
use App\Http\Controllers\Api\EmailTestController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PickupController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\StripeTestController;
use App\Services\StripeApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

// Public product routes (no auth required for browsing)
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/categories', [ProductController::class, 'categories']);
    Route::get('/{product}', [ProductController::class, 'show']);
});

// Public category routes
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{category}', [CategoryController::class, 'show']);
    Route::get('/{category}/products', [CategoryController::class, 'products']);
});

// Stripe webhook (no auth required)
Route::post('webhook/stripe', [StripeWebhookController::class, 'handle']);

// Test webhook endpoint (for testing without signature verification)
Route::post('webhook/stripe-test', function (Request $request) {
    $payload = $request->all();
    
    return response()->json([
        'message' => 'Test webhook received successfully',
        'payload' => $payload,
        'headers' => [
            'stripe-signature' => $request->header('Stripe-Signature'),
            'content-type' => $request->header('Content-Type'),
            'user-agent' => $request->header('User-Agent')
        ],
        'timestamp' => now()
    ]);
});

// Test email endpoint (no auth required for testing)
Route::post('test/email-receipt', [EmailTestController::class, 'testEmail']);

// Protected routes
Route::middleware(['jwt.auth'])->group(function () {
    
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
n    // Delivery Settings (Admin/Merchant only for management, public for checking)
    Route::prefix('delivery-settings')->group(function () {
        // Public routes for checking delivery availability
        Route::get('/zones', [AppHttpControllersApiDeliverySettingsController::class, 'getZones']);
        Route::post('/check-availability', [AppHttpControllersApiDeliverySettingsController::class, 'checkAvailability']);
        Route::get('/today-hours', [AppHttpControllersApiDeliverySettingsController::class, 'getTodayHours']);
        Route::post('/calculate-fee', [AppHttpControllersApiDeliverySettingsController::class, 'calculateFee']);

        // Admin/Merchant only routes
        Route::middleware('role:ADMIN,MERCHANT')->group(function () {
            Route::get('/', [AppHttpControllersApiDeliverySettingsController::class, 'index']);
            Route::put('/', [AppHttpControllersApiDeliverySettingsController::class, 'update']);
        });
    });

    // Checkout
    Route::prefix('checkout')->group(function () {
        Route::post('create-payment-intent', [CheckoutController::class, 'createPaymentIntent']);
        Route::post('create-payment-intent-for-order', [CheckoutController::class, 'createPaymentIntentForOrder']);
    });
n    // Delivery Settings (Admin/Merchant only for management, public for checking)
    Route::prefix('delivery-settings')->group(function () {
        // Public routes for checking delivery availability
        Route::get('/zones', [AppHttpControllersApiDeliverySettingsController::class, 'getZones']);
        Route::post('/check-availability', [AppHttpControllersApiDeliverySettingsController::class, 'checkAvailability']);
        Route::get('/today-hours', [AppHttpControllersApiDeliverySettingsController::class, 'getTodayHours']);
        Route::post('/calculate-fee', [AppHttpControllersApiDeliverySettingsController::class, 'calculateFee']);

        // Admin/Merchant only routes
        Route::middleware('role:ADMIN,MERCHANT')->group(function () {
            Route::get('/', [AppHttpControllersApiDeliverySettingsController::class, 'index']);
            Route::put('/', [AppHttpControllersApiDeliverySettingsController::class, 'update']);
        });
    });

    // Shopping Cart
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/', [CartController::class, 'store']);
        Route::put('/{id}', [CartController::class, 'update']);
        Route::delete('/{id}', [CartController::class, 'destroy']);
        Route::delete('/', [CartController::class, 'clear']);
        Route::get('/count', [CartController::class, 'count']);
    });
n    // Delivery Settings (Admin/Merchant only for management, public for checking)
    Route::prefix('delivery-settings')->group(function () {
        // Public routes for checking delivery availability
        Route::get('/zones', [AppHttpControllersApiDeliverySettingsController::class, 'getZones']);
        Route::post('/check-availability', [AppHttpControllersApiDeliverySettingsController::class, 'checkAvailability']);
        Route::get('/today-hours', [AppHttpControllersApiDeliverySettingsController::class, 'getTodayHours']);
        Route::post('/calculate-fee', [AppHttpControllersApiDeliverySettingsController::class, 'calculateFee']);

        // Admin/Merchant only routes
        Route::middleware('role:ADMIN,MERCHANT')->group(function () {
            Route::get('/', [AppHttpControllersApiDeliverySettingsController::class, 'index']);
            Route::put('/', [AppHttpControllersApiDeliverySettingsController::class, 'update']);
        });
    });

    // Order Management
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{order}', [OrderController::class, 'show']);
        Route::put('/{order}', [OrderController::class, 'update']);
        Route::patch('/{order}/cancel', [OrderController::class, 'cancel']);
        Route::post('/{order}/reorder', [OrderController::class, 'reorder']);
        Route::post('/{order}/confirm-payment', [OrderController::class, 'confirmPayment']);
        Route::get('/admin/stats', [OrderController::class, 'stats']);
    });
n    // Delivery Settings (Admin/Merchant only for management, public for checking)
    Route::prefix('delivery-settings')->group(function () {
        // Public routes for checking delivery availability
        Route::get('/zones', [AppHttpControllersApiDeliverySettingsController::class, 'getZones']);
        Route::post('/check-availability', [AppHttpControllersApiDeliverySettingsController::class, 'checkAvailability']);
        Route::get('/today-hours', [AppHttpControllersApiDeliverySettingsController::class, 'getTodayHours']);
        Route::post('/calculate-fee', [AppHttpControllersApiDeliverySettingsController::class, 'calculateFee']);

        // Admin/Merchant only routes
        Route::middleware('role:ADMIN,MERCHANT')->group(function () {
            Route::get('/', [AppHttpControllersApiDeliverySettingsController::class, 'index']);
            Route::put('/', [AppHttpControllersApiDeliverySettingsController::class, 'update']);
        });
    });

    // Address management
    Route::prefix('addresses')->group(function () {
        Route::get('/', [AddressController::class, 'index']);
        Route::post('/', [AddressController::class, 'store']);
        Route::get('/{address}', [AddressController::class, 'show']);
        Route::put('/{address}', [AddressController::class, 'update']);
        Route::delete('/{address}', [AddressController::class, 'destroy']);
    });
n    // Delivery Settings (Admin/Merchant only for management, public for checking)
    Route::prefix('delivery-settings')->group(function () {
        // Public routes for checking delivery availability
        Route::get('/zones', [AppHttpControllersApiDeliverySettingsController::class, 'getZones']);
        Route::post('/check-availability', [AppHttpControllersApiDeliverySettingsController::class, 'checkAvailability']);
        Route::get('/today-hours', [AppHttpControllersApiDeliverySettingsController::class, 'getTodayHours']);
        Route::post('/calculate-fee', [AppHttpControllersApiDeliverySettingsController::class, 'calculateFee']);

        // Admin/Merchant only routes
        Route::middleware('role:ADMIN,MERCHANT')->group(function () {
            Route::get('/', [AppHttpControllersApiDeliverySettingsController::class, 'index']);
            Route::put('/', [AppHttpControllersApiDeliverySettingsController::class, 'update']);
        });
    });

    // Product management for merchants/admins
    Route::prefix('products')->group(function () {
        Route::post('/', [ProductController::class, 'store']); // Merchant/Admin only
        Route::put('/{product}', [ProductController::class, 'update']); // Merchant/Admin only
        Route::delete('/{product}', [ProductController::class, 'destroy']); // Merchant/Admin only
        Route::patch('/{product}/toggle', [\App\Http\Controllers\Api\ProductToggleController::class, 'toggleStatus']); // Toggle active status
    });
n    // Delivery Settings (Admin/Merchant only for management, public for checking)
    Route::prefix('delivery-settings')->group(function () {
        // Public routes for checking delivery availability
        Route::get('/zones', [AppHttpControllersApiDeliverySettingsController::class, 'getZones']);
        Route::post('/check-availability', [AppHttpControllersApiDeliverySettingsController::class, 'checkAvailability']);
        Route::get('/today-hours', [AppHttpControllersApiDeliverySettingsController::class, 'getTodayHours']);
        Route::post('/calculate-fee', [AppHttpControllersApiDeliverySettingsController::class, 'calculateFee']);

        // Admin/Merchant only routes
        Route::middleware('role:ADMIN,MERCHANT')->group(function () {
            Route::get('/', [AppHttpControllersApiDeliverySettingsController::class, 'index']);
            Route::put('/', [AppHttpControllersApiDeliverySettingsController::class, 'update']);
        });
    });

    // Category management (Admin only)
    Route::prefix('admin/categories')->middleware('role:ADMIN,MERCHANT')->group(function () {
        Route::post('/', [CategoryController::class, 'store']);
        Route::put('/{category}', [CategoryController::class, 'update']);
        Route::delete('/{category}', [CategoryController::class, 'destroy']);
    });
n    // Delivery Settings (Admin/Merchant only for management, public for checking)
    Route::prefix('delivery-settings')->group(function () {
        // Public routes for checking delivery availability
        Route::get('/zones', [AppHttpControllersApiDeliverySettingsController::class, 'getZones']);
        Route::post('/check-availability', [AppHttpControllersApiDeliverySettingsController::class, 'checkAvailability']);
        Route::get('/today-hours', [AppHttpControllersApiDeliverySettingsController::class, 'getTodayHours']);
        Route::post('/calculate-fee', [AppHttpControllersApiDeliverySettingsController::class, 'calculateFee']);

        // Admin/Merchant only routes
        Route::middleware('role:ADMIN,MERCHANT')->group(function () {
            Route::get('/', [AppHttpControllersApiDeliverySettingsController::class, 'index']);
            Route::put('/', [AppHttpControllersApiDeliverySettingsController::class, 'update']);
        });
    });

    // Admin Dashboard
    Route::prefix('admin')->middleware('role:ADMIN,MERCHANT')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get('dashboard/recent-orders', [DashboardController::class, 'recentOrders']);
        Route::get('dashboard/sales-chart', [DashboardController::class, 'salesChart']);
        Route::get('dashboard/top-products', [DashboardController::class, 'topProducts']);

        // New Admin Statistics API
        Route::get('stats', [\App\Http\Controllers\Api\AdminStatsController::class, 'getStats']);
        Route::get('stats/orders', [\App\Http\Controllers\Api\AdminStatsController::class, 'getOrderStats']);
        
        // Reports and Export
        Route::get('orders/export', [\App\Http\Controllers\Api\ReportController::class, 'exportOrders']);
        Route::get('reports/sales', [\App\Http\Controllers\Api\ReportController::class, 'salesReport']);
        Route::get('reports/customers', [\App\Http\Controllers\Api\ReportController::class, 'customerReport']);
    });
n    // Delivery Settings (Admin/Merchant only for management, public for checking)
    Route::prefix('delivery-settings')->group(function () {
        // Public routes for checking delivery availability
        Route::get('/zones', [AppHttpControllersApiDeliverySettingsController::class, 'getZones']);
        Route::post('/check-availability', [AppHttpControllersApiDeliverySettingsController::class, 'checkAvailability']);
        Route::get('/today-hours', [AppHttpControllersApiDeliverySettingsController::class, 'getTodayHours']);
        Route::post('/calculate-fee', [AppHttpControllersApiDeliverySettingsController::class, 'calculateFee']);

        // Admin/Merchant only routes
        Route::middleware('role:ADMIN,MERCHANT')->group(function () {
            Route::get('/', [AppHttpControllersApiDeliverySettingsController::class, 'index']);
            Route::put('/', [AppHttpControllersApiDeliverySettingsController::class, 'update']);
        });
    });

    // Customer Management (Admin/Merchant only)
    Route::prefix('customers')->middleware('role:ADMIN,MERCHANT')->group(function () {
        Route::get('/', [AppHttpControllersApiCustomerController::class, 'index']);
        Route::get('/statistics', [AppHttpControllersApiCustomerController::class, 'statistics']);
        Route::get('/export', [AppHttpControllersApiCustomerController::class, 'export']);
        Route::get('/download/{filename}', [AppHttpControllersApiCustomerController::class, 'download']);
        Route::get('/{id}', [AppHttpControllersApiCustomerController::class, 'show']);
        Route::put('/{id}', [AppHttpControllersApiCustomerController::class, 'update']);
        Route::patch('/{id}/toggle-status', [AppHttpControllersApiCustomerController::class, 'toggleStatus']);
        Route::delete('/{id}', [AppHttpControllersApiCustomerController::class, 'destroy']);
    });
n    // Delivery Settings (Admin/Merchant only for management, public for checking)
    Route::prefix('delivery-settings')->group(function () {
        // Public routes for checking delivery availability
        Route::get('/zones', [AppHttpControllersApiDeliverySettingsController::class, 'getZones']);
        Route::post('/check-availability', [AppHttpControllersApiDeliverySettingsController::class, 'checkAvailability']);
        Route::get('/today-hours', [AppHttpControllersApiDeliverySettingsController::class, 'getTodayHours']);
        Route::post('/calculate-fee', [AppHttpControllersApiDeliverySettingsController::class, 'calculateFee']);

        // Admin/Merchant only routes
        Route::middleware('role:ADMIN,MERCHANT')->group(function () {
            Route::get('/', [AppHttpControllersApiDeliverySettingsController::class, 'index']);
            Route::put('/', [AppHttpControllersApiDeliverySettingsController::class, 'update']);
        });
    });

    // Test route
    Route::get('test', function () {
        return response()->json([
            'message' => 'BellGas API is working!',
            'user' => auth()->user(),
            'timestamp' => now()
        ]);
    });
n    // Delivery Settings (Admin/Merchant only for management, public for checking)
    Route::prefix('delivery-settings')->group(function () {
        // Public routes for checking delivery availability
        Route::get('/zones', [AppHttpControllersApiDeliverySettingsController::class, 'getZones']);
        Route::post('/check-availability', [AppHttpControllersApiDeliverySettingsController::class, 'checkAvailability']);
        Route::get('/today-hours', [AppHttpControllersApiDeliverySettingsController::class, 'getTodayHours']);
        Route::post('/calculate-fee', [AppHttpControllersApiDeliverySettingsController::class, 'calculateFee']);

        // Admin/Merchant only routes
        Route::middleware('role:ADMIN,MERCHANT')->group(function () {
            Route::get('/', [AppHttpControllersApiDeliverySettingsController::class, 'index']);
            Route::put('/', [AppHttpControllersApiDeliverySettingsController::class, 'update']);
        });
    });

    // Test Stripe connection - using custom API service
    Route::get('test-stripe', function () {
        try {
            $stripeApi = new StripeApiService();
            
            // Test connection first
            $connectionTest = $stripeApi->testConnection();
            
            if (!$connectionTest['success']) {
                return response()->json([
                    'message' => 'Stripe API connection failed',
                    'error' => $connectionTest['message']
                ], 500);
            }

            // Create a minimal payment intent
            $paymentIntent = $stripeApi->createPaymentIntent(1000, 'aud', [
                'test' => 'true',
                'source' => 'BellGas API test'
            ]);

            return response()->json([
                'message' => 'Stripe API connection and payment intent creation successful',
                'connection_test' => $connectionTest,
                'payment_intent' => [
                    'id' => $paymentIntent['id'],
                    'status' => $paymentIntent['status'],
                    'amount' => $paymentIntent['amount'],
                    'currency' => $paymentIntent['currency'],
                    'client_secret' => $paymentIntent['client_secret']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Stripe API test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    });
n    // Delivery Settings (Admin/Merchant only for management, public for checking)
    Route::prefix('delivery-settings')->group(function () {
        // Public routes for checking delivery availability
        Route::get('/zones', [AppHttpControllersApiDeliverySettingsController::class, 'getZones']);
        Route::post('/check-availability', [AppHttpControllersApiDeliverySettingsController::class, 'checkAvailability']);
        Route::get('/today-hours', [AppHttpControllersApiDeliverySettingsController::class, 'getTodayHours']);
        Route::post('/calculate-fee', [AppHttpControllersApiDeliverySettingsController::class, 'calculateFee']);

        // Admin/Merchant only routes
        Route::middleware('role:ADMIN,MERCHANT')->group(function () {
            Route::get('/', [AppHttpControllersApiDeliverySettingsController::class, 'index']);
            Route::put('/', [AppHttpControllersApiDeliverySettingsController::class, 'update']);
        });
    });

    // Test Stripe connection only (no payment creation)
    Route::get('test-stripe-connection', function () {
        try {
            $stripeApi = new StripeApiService();
            $result = $stripeApi->testConnection();

            return response()->json($result, $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    });
n    // Delivery Settings (Admin/Merchant only for management, public for checking)
    Route::prefix('delivery-settings')->group(function () {
        // Public routes for checking delivery availability
        Route::get('/zones', [AppHttpControllersApiDeliverySettingsController::class, 'getZones']);
        Route::post('/check-availability', [AppHttpControllersApiDeliverySettingsController::class, 'checkAvailability']);
        Route::get('/today-hours', [AppHttpControllersApiDeliverySettingsController::class, 'getTodayHours']);
        Route::post('/calculate-fee', [AppHttpControllersApiDeliverySettingsController::class, 'calculateFee']);

        // Admin/Merchant only routes
        Route::middleware('role:ADMIN,MERCHANT')->group(function () {
            Route::get('/', [AppHttpControllersApiDeliverySettingsController::class, 'index']);
            Route::put('/', [AppHttpControllersApiDeliverySettingsController::class, 'update']);
        });
    });

    // Stripe payment testing routes
    Route::prefix('stripe-test')->group(function () {
        Route::post('simulate-payment', [StripeTestController::class, 'simulatePaymentSuccess']);
        Route::get('cards', [StripeTestController::class, 'getTestCards']);
    });
n    // Delivery Settings (Admin/Merchant only for management, public for checking)
    Route::prefix('delivery-settings')->group(function () {
        // Public routes for checking delivery availability
        Route::get('/zones', [AppHttpControllersApiDeliverySettingsController::class, 'getZones']);
        Route::post('/check-availability', [AppHttpControllersApiDeliverySettingsController::class, 'checkAvailability']);
        Route::get('/today-hours', [AppHttpControllersApiDeliverySettingsController::class, 'getTodayHours']);
        Route::post('/calculate-fee', [AppHttpControllersApiDeliverySettingsController::class, 'calculateFee']);

        // Admin/Merchant only routes
        Route::middleware('role:ADMIN,MERCHANT')->group(function () {
            Route::get('/', [AppHttpControllersApiDeliverySettingsController::class, 'index']);
            Route::put('/', [AppHttpControllersApiDeliverySettingsController::class, 'update']);
        });
    });

    // Payment routes
    Route::prefix('payments')->group(function () {
        Route::post('orders/{order}/intent', [\App\Http\Controllers\Api\PaymentController::class, 'createPaymentIntent']);
        Route::post('orders/{order}/complete', [\App\Http\Controllers\Api\PaymentController::class, 'completePayment']);
        Route::post('orders/{order}/simulate', [\App\Http\Controllers\Api\PaymentController::class, 'simulateTestPayment']);
        Route::get('orders/{order}/status', [\App\Http\Controllers\Api\PaymentController::class, 'getPaymentStatus']);
    });
n    // Delivery Settings (Admin/Merchant only for management, public for checking)
    Route::prefix('delivery-settings')->group(function () {
        // Public routes for checking delivery availability
        Route::get('/zones', [AppHttpControllersApiDeliverySettingsController::class, 'getZones']);
        Route::post('/check-availability', [AppHttpControllersApiDeliverySettingsController::class, 'checkAvailability']);
        Route::get('/today-hours', [AppHttpControllersApiDeliverySettingsController::class, 'getTodayHours']);
        Route::post('/calculate-fee', [AppHttpControllersApiDeliverySettingsController::class, 'calculateFee']);

        // Admin/Merchant only routes
        Route::middleware('role:ADMIN,MERCHANT')->group(function () {
            Route::get('/', [AppHttpControllersApiDeliverySettingsController::class, 'index']);
            Route::put('/', [AppHttpControllersApiDeliverySettingsController::class, 'update']);
        });
    });

    // Receipt routes
    Route::prefix('receipts')->group(function () {
        Route::get('order/{order}', [ReceiptController::class, 'getOrderReceipt']);
        Route::get('stripe/{order}', [ReceiptController::class, 'getStripeReceiptUrl']);
        Route::post('email/{order}', [ReceiptController::class, 'emailReceipt']);
        Route::get('order/{order}/pdf', [ReceiptController::class, 'downloadPdf']);
        Route::get('order/{order}/pdf-url', [ReceiptController::class, 'getPdfUrl']);
    });
n    // Delivery Settings (Admin/Merchant only for management, public for checking)
    Route::prefix('delivery-settings')->group(function () {
        // Public routes for checking delivery availability
        Route::get('/zones', [AppHttpControllersApiDeliverySettingsController::class, 'getZones']);
        Route::post('/check-availability', [AppHttpControllersApiDeliverySettingsController::class, 'checkAvailability']);
        Route::get('/today-hours', [AppHttpControllersApiDeliverySettingsController::class, 'getTodayHours']);
        Route::post('/calculate-fee', [AppHttpControllersApiDeliverySettingsController::class, 'calculateFee']);

        // Admin/Merchant only routes
        Route::middleware('role:ADMIN,MERCHANT')->group(function () {
            Route::get('/', [AppHttpControllersApiDeliverySettingsController::class, 'index']);
            Route::put('/', [AppHttpControllersApiDeliverySettingsController::class, 'update']);
        });
    });

    // Stock management routes (for testing)
    Route::prefix('stock')->group(function () {
        Route::post('reduce', [\App\Http\Controllers\Api\StockController::class, 'reduceStock']);
        Route::post('increase', [\App\Http\Controllers\Api\StockController::class, 'increaseStock']);
        Route::get('{productVariantId}', [\App\Http\Controllers\Api\StockController::class, 'getStock']);
    });
n    // Delivery Settings (Admin/Merchant only for management, public for checking)
    Route::prefix('delivery-settings')->group(function () {
        // Public routes for checking delivery availability
        Route::get('/zones', [AppHttpControllersApiDeliverySettingsController::class, 'getZones']);
        Route::post('/check-availability', [AppHttpControllersApiDeliverySettingsController::class, 'checkAvailability']);
        Route::get('/today-hours', [AppHttpControllersApiDeliverySettingsController::class, 'getTodayHours']);
        Route::post('/calculate-fee', [AppHttpControllersApiDeliverySettingsController::class, 'calculateFee']);

        // Admin/Merchant only routes
        Route::middleware('role:ADMIN,MERCHANT')->group(function () {
            Route::get('/', [AppHttpControllersApiDeliverySettingsController::class, 'index']);
            Route::put('/', [AppHttpControllersApiDeliverySettingsController::class, 'update']);
        });
    });

    // Pickup verification routes
    Route::prefix('pickup')->group(function () {
        Route::post('generate/{order}', [PickupController::class, 'generatePickupToken']);
        Route::get('token/{order}', [PickupController::class, 'getPickupToken']);
        Route::post('verify', [PickupController::class, 'verifyPickupCode']);
        Route::get('pending', [PickupController::class, 'getPendingPickups']);
    });
n    // Delivery Settings (Admin/Merchant only for management, public for checking)
    Route::prefix('delivery-settings')->group(function () {
        // Public routes for checking delivery availability
        Route::get('/zones', [AppHttpControllersApiDeliverySettingsController::class, 'getZones']);
        Route::post('/check-availability', [AppHttpControllersApiDeliverySettingsController::class, 'checkAvailability']);
        Route::get('/today-hours', [AppHttpControllersApiDeliverySettingsController::class, 'getTodayHours']);
        Route::post('/calculate-fee', [AppHttpControllersApiDeliverySettingsController::class, 'calculateFee']);

        // Admin/Merchant only routes
        Route::middleware('role:ADMIN,MERCHANT')->group(function () {
            Route::get('/', [AppHttpControllersApiDeliverySettingsController::class, 'index']);
            Route::put('/', [AppHttpControllersApiDeliverySettingsController::class, 'update']);
        });
    });

    // Real-time API routes
    Route::prefix('realtime')->group(function () {
        Route::get('orders', [\App\Http\Controllers\Api\RealtimeController::class, 'getOrderUpdates']);
        Route::get('customer-orders', [\App\Http\Controllers\Api\RealtimeController::class, 'getCustomerOrderUpdates']);
        Route::get('admin-stats', [\App\Http\Controllers\Api\RealtimeController::class, 'getAdminStats']);
    });
n    // Delivery Settings (Admin/Merchant only for management, public for checking)
    Route::prefix('delivery-settings')->group(function () {
        // Public routes for checking delivery availability
        Route::get('/zones', [AppHttpControllersApiDeliverySettingsController::class, 'getZones']);
        Route::post('/check-availability', [AppHttpControllersApiDeliverySettingsController::class, 'checkAvailability']);
        Route::get('/today-hours', [AppHttpControllersApiDeliverySettingsController::class, 'getTodayHours']);
        Route::post('/calculate-fee', [AppHttpControllersApiDeliverySettingsController::class, 'calculateFee']);

        // Admin/Merchant only routes
        Route::middleware('role:ADMIN,MERCHANT')->group(function () {
            Route::get('/', [AppHttpControllersApiDeliverySettingsController::class, 'index']);
            Route::put('/', [AppHttpControllersApiDeliverySettingsController::class, 'update']);
        });
    });
});

// API Documentation
Route::get('docs', [DocsController::class, 'index']);

// Health check
Route::get('health', function () {
    return response()->json([
        'status' => 'OK',
        'service' => 'BellGas API',
        'timestamp' => now()
    ]);
});
