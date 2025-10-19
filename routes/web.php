<?php

use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', function () {
    return view('home');
});

Route::get('/home', function () {
    return view('home');
})->name('home');

// Test WebSocket Route
Route::get('/test-websocket', function () {
    return view('test-websocket');
})->name('test.websocket');

// Authentication Routes
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', [App\Http\Controllers\Web\AuthController::class, 'login']);
Route::post('/logout', [App\Http\Controllers\Web\AuthController::class, 'logout'])->name('logout');
Route::get('/logout', [App\Http\Controllers\Web\AuthController::class, 'logout'])->name('logout.get');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->name('password.request');

// Product Routes
Route::get('/products', function () {
    return view('products.beautiful');
})->name('products.index');

Route::get('/products/{slug}', function ($slug) {
    return view('products.show', compact('slug'));
})->name('products.show');

// Cart & Checkout (with optional auth for better UX)
Route::get('/checkout', function () {
    $user = auth()->check() ? auth()->user() : null;
    return view('checkout.index', compact('user'));
})->name('checkout');

// Secured checkout route (require auth)
Route::middleware(['auth'])->group(function () {
    Route::get('/secure-checkout', function () {
        $user = auth()->user();
        return view('checkout.index', compact('user'));
    })->name('secure-checkout');
});

Route::get('/cart', function () {
    return view('cart.index');
})->name('cart');

// Debug route
Route::get('/debug-token', function() {
    return response()->json([
        'session_jwt_token' => session('jwt_token'),
        'session_frontend_token' => session('frontend_token'),
        'session_authenticated' => session('authenticated'),
        'session_user_data' => session('user_data'),
        'auth_check' => auth()->check(),
        'auth_user' => auth()->user()
    ]);
});

// Test cart functionality
Route::get('/test-cart', function () {
    return view('test-cart');
})->name('test-cart');

// Test notifications
Route::get('/test-notifications', function () {
    return view('test-notifications');
})->name('test-notifications');

// Debug cart issues
Route::get('/debug-cart', function () {
    return view('debug-cart');
})->name('debug-cart');

// Simple products test
Route::get('/products-test', function () {
    return view('products.simple-test');
})->name('products-test');

// Add to cart test
Route::get('/test-add-to-cart', function () {
    return view('test-add-to-cart');
})->name('test-add-to-cart');

// Test routes for development only
Route::get('/test-create-order', function () {
    if (app()->environment('local')) {
        // Create a test order
        $user = \App\Models\User::first();
        if (!$user) {
            return response()->json(['error' => 'No user found'], 404);
        }

        $order = \App\Models\Order::create([
            'user_id' => $user->id,
            'order_number' => 'BG-TEST-' . time(),
            'status' => 'PENDING',
            'fulfillment_method' => 'DELIVERY',
            'total_aud' => 59.99,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '+61412345678',
            'customer_notes' => 'Test order for real-time notifications',
            'stripe_payment_intent_id' => 'pi_test_' . time(),
        ]);

        return response()->json(['message' => 'Test order created', 'order_id' => $order->id, 'order_number' => $order->order_number]);
    }
    return response()->json(['error' => 'Not available in production'], 403);
});

Route::get('/test-payment-webhook/{orderId}', function ($orderId) {
    if (app()->environment('local')) {
        $order = \App\Models\Order::with(['items.productVariant.product', 'address'])->find($orderId);
        if ($order) {
            $order->update(['status' => 'PAID']);
            broadcast(new \App\Events\NewPaidOrderEvent($order))->toOthers();
            return response()->json(['message' => 'Payment webhook simulated', 'order' => $order->order_number]);
        }
        return response()->json(['error' => 'Order not found'], 404);
    }
    return response()->json(['error' => 'Not available in production'], 403);
});

// Customer Dashboard (require auth)
Route::middleware(['web.auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard.index');
    })->name('dashboard');
});

// Cart & Checkout (require auth) - uncomment for production
// Route::middleware(['auth'])->group(function () {
//     Route::get('/checkout', function () {
//         return view('checkout.index');
//     })->name('checkout');
//     
//     Route::get('/cart', function () {
//         return view('cart.index');
//     })->name('cart');
// });

// Customer Dashboard routes (require authentication)
Route::middleware(['web.auth'])->group(function () {
    Route::get('/orders', function () {
        return view('orders.simple');
    })->name('orders.index');

    Route::get('/orders/{id}', function ($id) {
        return view('orders.show-clean', compact('id'));
    })->name('orders.show');

    Route::get('/profile', function () {
        return view('profile.index');
    })->name('profile');

    Route::get('/addresses', function () {
        return view('addresses.index');
    })->name('addresses');
});

// Receipt download routes (using session authentication)
Route::middleware(['web.auth'])->prefix('web')->group(function () {
    Route::get('/receipts/order/{order}/pdf', [\App\Http\Controllers\Api\ReceiptController::class, 'downloadPdf'])
        ->name('web.receipts.download');
    
    // Test authentication endpoint
    Route::get('/auth-test', function () {
        return response()->json([
            'authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'user' => auth()->user() ? auth()->user()->email : null,
            'guard' => auth()->getDefaultDriver(),
        ]);
    })->name('web.auth.test');

    // Dashboard data routes (using session authentication)
    Route::get('/admin/dashboard/stats', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'getStats'])
        ->name('web.admin.dashboard.stats');
    Route::get('/admin/dashboard/recent-orders', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'getRecentOrders'])
        ->name('web.admin.dashboard.recent-orders');
    Route::get('/admin/dashboard/top-products', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'getTopProducts'])
        ->name('web.admin.dashboard.top-products');

    // Customer dashboard routes
    Route::get('/customer/dashboard/stats', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'userStats'])
        ->name('web.customer.dashboard.stats');
    Route::get('/orders', [\App\Http\Controllers\Api\OrderController::class, 'index'])
        ->name('web.customer.orders');

    // Customer addresses (session-based)
    Route::get('/addresses', [\App\Http\Controllers\Api\AddressController::class, 'index'])
        ->name('web.customer.addresses');
    Route::post('/addresses', [\App\Http\Controllers\Api\AddressController::class, 'store'])
        ->name('web.customer.addresses.store');

    // Admin order management routes (session-based auth)
    Route::middleware(['admin.auth'])->prefix('admin')->group(function () {
        Route::get('/orders/stats', [\App\Http\Controllers\Api\OrderController::class, 'stats'])
            ->name('web.admin.orders.stats');
        Route::get('/orders/export', [\App\Http\Controllers\Api\OrderController::class, 'export'])
            ->name('web.admin.orders.export');
        Route::get('/realtime/orders', [\App\Http\Controllers\Api\OrderController::class, 'realtimeUpdates'])
            ->name('web.admin.realtime.orders');
        Route::put('/orders/{order}', [\App\Http\Controllers\Api\OrderController::class, 'update'])
            ->name('web.admin.orders.update');
    });
});

// Admin Routes (require admin authentication)
Route::middleware(['admin.auth'])->prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');

    Route::get('/orders', function () {
        return view('admin.orders');
    })->name('admin.orders');

    Route::get('/products', function () {
        return view('admin.products');
    })->name('admin.products');

    Route::get('/customers', function () {
        return view('admin.customers');
    })->name('admin.customers');

    Route::get('/settings', function () {
        return view('admin.settings');
    })->name('admin.settings');

    // Admin user management - only accessible by existing admins
    Route::get('/users', function () {
        return view('admin.users');
    })->name('admin.users');

    Route::get('/create-admin', function () {
        return view('admin.create-admin');
    })->name('admin.create-admin');
});

// Static Pages
Route::get('/about', function () {
    return view('pages.about');
})->name('about');

Route::get('/contact', function () {
    return view('pages.contact');
})->name('contact');

Route::get('/terms', function () {
    return view('pages.terms');
})->name('terms');

Route::get('/privacy', function () {
    return view('pages.privacy');
})->name('privacy');

// Test Routes (for development)
Route::get('/test-stripe', function () {
    return view('test.stripe');
})->name('test.stripe');

Route::get('/test-email', function () {
    return view('test.email');
})->name('test.email');

// Clear expired sessions route
Route::get('/clear-session', function () {
    session()->flush();
    session()->regenerate();
    return redirect('/login')->with('success', 'Session cleared successfully');
});

// CSS test route for Firefox debugging
Route::get('/test-css', function () {
    return view('test.css-test');
});

// Loading animation test
Route::get('/test-loading', function () {
    return view('test-loading');
});

// Debug route - remove in production
Route::get('/debug-auth', function () {
    $user = \App\Models\User::where('email', 'kimpet@gmail.com')->first();
    if ($user) {
        \Illuminate\Support\Facades\Auth::login($user);
        session(['authenticated' => true]);
        session(['user_data' => [
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'role' => $user->role
        ]]);
        
        return redirect('/admin/dashboard');
    }
    return 'User tidak ditemukan';
});

// Debug route for customer login
Route::get('/debug-customer-login', function () {
    $user = \App\Models\User::where('email', 'john@example.com')->first();
    if ($user) {
        \Illuminate\Support\Facades\Auth::login($user);
        session(['authenticated' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Logged in successfully',
            'user' => $user->email,
            'redirect' => '/secure-checkout'
        ]);
    }
    return response()->json(['error' => 'User tidak ditemukan']);
});

// Quick login routes for testing
Route::get('/quick-login/admin', function () {
    // Try multiple admin emails for compatibility across environments
    $user = \App\Models\User::where('email', 'adminbellgas@gmail.com')->first()
        ?? \App\Models\User::where('email', 'admin@bellgas.com')->first()
        ?? \App\Models\User::where('email', 'admin@bellgas.com.au')->first();
    if ($user) {
        \Illuminate\Support\Facades\Auth::login($user);

        // Generate JWT token like AuthController does
        try {
            $ttlMinutes = 480; // 8 hours for admin
            config(['jwt.ttl' => $ttlMinutes]);
            $token = auth('api')->login($user);

            session(['jwt_token' => $token]);
            session(['authenticated' => true]);
            session(['user_data' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone_number' => $user->phone_number,
                'role' => $user->role,
                'is_active' => $user->is_active
            ]]);
            session(['frontend_token' => $token]);

        } catch (\Exception $e) {
            \Log::warning('Failed to generate JWT token for quick login: ' . $e->getMessage());
            session(['authenticated' => true]);
            session(['user_data' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'role' => $user->role
            ]]);
        }

        return redirect('/admin/dashboard')->with('success', 'Logged in as admin');
    }
    return redirect('/login')->with('error', 'Admin user not found');
});

Route::get('/quick-login/customer', function () {
    $user = \App\Models\User::where('email', 'kimpet@gmail.com')->first();
    if ($user) {
        \Illuminate\Support\Facades\Auth::login($user);

        // Generate JWT token like AuthController does
        try {
            $ttlMinutes = 120; // 2 hours for customer
            config(['jwt.ttl' => $ttlMinutes]);
            $token = auth('api')->login($user);

            session(['jwt_token' => $token]);
            session(['authenticated' => true]);
            session(['user_data' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone_number' => $user->phone_number,
                'role' => $user->role,
                'is_active' => $user->is_active
            ]]);
            session(['frontend_token' => $token]);

        } catch (\Exception $e) {
            \Log::warning('Failed to generate JWT token for quick login: ' . $e->getMessage());
            session(['authenticated' => true]);
            session(['user_data' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'role' => $user->role
            ]]);
        }

        return redirect('/orders')->with('success', 'Logged in as customer');
    }
    return redirect('/login')->with('error', 'Customer user not found');
});

// Debug session route
Route::get('/debug-session', function () {
    return response()->json([
        'jwt_token' => session('jwt_token'),
        'frontend_token' => session('frontend_token'),
        'authenticated' => session('authenticated'),
        'user_data' => session('user_data'),
        'all_session' => session()->all()
    ]);
});

// Test tombol admin orders debug
Route::get('/test-buttons', function () {
    return view('test-buttons');
});

// Debug admin orders dengan data real
Route::middleware(['web.auth', 'admin.auth'])->get('/debug-admin-orders', function () {
    // Get real orders data like admin orders page
    $orders = \App\Models\Order::with(['user', 'orderItems.product'])
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();

    return view('debug-admin-orders', compact('orders'));
});

// Test route to manually trigger order status update broadcast
Route::get('/test-broadcast/{userId}', function ($userId) {
    try {
        $testData = [
            'order_id' => 999,
            'order_number' => 'TEST-ORDER',
            'customer_id' => (int)$userId,
            'previous_status' => 'PENDING',
            'new_status' => 'PROCESSING',
            'total_amount' => '25.00',
            'customer_name' => 'Test User',
            'updated_at' => now()->toISOString(),
            'message' => 'Test broadcast message',
        ];

        // Use the proper broadcasting method
        broadcast(new \App\Events\OrderStatusUpdated(
            (object) [
                'id' => 999,
                'order_number' => 'TEST-ORDER',
                'user_id' => (int)$userId,
                'total_aud' => '25.00',
                'updated_at' => now(),
                'user' => (object) ['first_name' => 'Test', 'last_name' => 'User']
            ],
            'PENDING',
            'PROCESSING'
        ))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Test broadcast sent to user ' . $userId,
            'event' => 'OrderStatusUpdated',
            'channel' => 'private-user.' . $userId . '.orders'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});

// Auto-login route for testing
Route::get('/login-and-checkout', function () {
    $user = \App\Models\User::where('email', 'john@example.com')->first();
    if ($user) {
        \Illuminate\Support\Facades\Auth::login($user);
        session(['authenticated' => true]);
        
        return redirect('/secure-checkout')->with('success', 'Auto-logged in as ' . $user->email);
    }
    return redirect('/login')->with('error', 'User tidak ditemukan');
});

// Receipt routes for web session authentication
Route::middleware(['web.auth'])->prefix('web/receipts')->group(function () {
    Route::get('order/{order}/pdf', [\App\Http\Controllers\Web\ReceiptController::class, 'downloadPdf'])
        ->name('web.receipts.download');
    Route::get('order/{order}/preview', [\App\Http\Controllers\Web\ReceiptController::class, 'preview'])
        ->name('web.receipts.preview');
});

// Admin receipt routes
Route::middleware(['web.auth', 'admin.auth'])->prefix('admin/receipts')->group(function () {
    Route::get('order/{order}/pdf', [\App\Http\Controllers\Web\ReceiptController::class, 'adminDownload'])
        ->name('admin.receipts.download');
});

// Customer orders page
Route::middleware(['web.auth'])->group(function () {
    Route::get('/my-orders', function () {
        return view('customer.orders');
    })->name('customer.orders');
});

// Web-based real-time routes for admin dashboard (session authentication)
Route::middleware(['web.auth', 'admin.auth'])->prefix('web/realtime')->group(function () {
    Route::get('orders', [\App\Http\Controllers\Api\RealtimeController::class, 'getOrderUpdates'])
        ->name('web.realtime.orders');
    Route::get('admin-stats', [\App\Http\Controllers\Api\RealtimeController::class, 'getAdminStats'])
        ->name('web.realtime.admin-stats');
});

// Web-based real-time routes for customer (session authentication)
Route::middleware(['web.auth'])->prefix('web/realtime')->group(function () {
    Route::get('customer-orders', [\App\Http\Controllers\Api\RealtimeController::class, 'getCustomerOrderUpdates'])
        ->name('web.realtime.customer-orders');
});
// Broadcasting routes untuk Reverb/Pusher WebSocket
Broadcast::routes(['middleware' => ['web', 'web.auth']]);
