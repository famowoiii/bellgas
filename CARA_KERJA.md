# CARA KERJA APLIKASI BELLGAS E-COMMERCE

## OVERVIEW APLIKASI
BellGas adalah platform e-commerce yang dikembangkan untuk penjualan dan distribusi tabung gas LPG. Aplikasi ini menggunakan arsitektur Laravel dengan API-first approach, mengintegrasikan sistem pembayaran Stripe, dan mendukung real-time notifications menggunakan Laravel Reverb/Pusher.

## ARSITEKTUR SISTEM

### 1. TEKNOLOGI STACK
- **Backend**: Laravel 12.x (PHP 8.2+)
- **Database**: MySQL (production), SQLite (development)
- **Frontend**: Blade Templates + Alpine.js + Tailwind CSS
- **Authentication**: JWT (tymon/jwt-auth)
- **Payment Gateway**: Stripe API
- **Real-time**: Laravel Reverb (WebSocket)
- **Testing**: Playwright E2E Tests
- **Build System**: Vite

### 2. STRUKTUR DIREKTORI
```
bellgas-laravel/
├── app/
│   ├── Http/Controllers/          # API dan Web Controllers
│   │   ├── Api/                   # REST API Controllers
│   │   │   ├── Auth/              # Authentication
│   │   │   ├── Admin/             # Admin Management
│   │   │   └── ...
│   │   └── Web/                   # Web Controllers
│   ├── Models/                    # Eloquent Models
│   ├── Events/                    # Event Classes
│   ├── Middleware/                # Custom Middleware
│   └── Services/                  # Business Logic Services
├── database/
│   ├── migrations/                # Database Schema
│   └── seeders/                   # Data Seeders
├── resources/
│   ├── views/                     # Blade Templates
│   └── js/                        # Frontend Assets
├── routes/
│   ├── api.php                    # API Routes
│   └── web.php                    # Web Routes
└── tests/                         # Playwright Tests
```

## ALUR KERJA APLIKASI

### 1. SISTEM AUTENTIKASI

#### Registration & Login Process
```
User Registration/Login
↓
AuthController validates credentials
↓
JWT Token generated with role-based TTL:
- CUSTOMER: 2 hours
- ADMIN/MERCHANT: 8 hours
↓
Token stored in session dan localStorage
↓
User redirected based on role
```

#### Role-Based Access
- **CUSTOMER**: Akses ke shopping, orders, profile
- **MERCHANT**: Akses ke product management, orders
- **ADMIN**: Full system access

### 2. SISTEM PRODUK

#### Product Management Flow
```
Admin/Merchant creates product
↓
Product saved dengan:
- Basic info (name, description, category)
- Image upload handling
- Default variant creation
↓
ProductVariant contains:
- Price, stock, weight
- Active status
↓
Product photos managed separately
↓
Products displayed in catalog
```

#### Product Display Logic
- **Guest Users**: Hanya produk aktif
- **Admin/Merchant**: Semua produk
- **Categories**: Dynamic dari database
- **Search**: By name pattern matching

### 3. SISTEM SHOPPING CART

#### Cart Management Flow
```
User adds product to cart
↓
CartController validates:
- Stock availability
- Reservation conflicts
- User authentication
↓
Cart::addItemToCart() handles:
- Stock reservation (15 menit)
- Duplicate item merging
- Preorder logic
↓
Cart stored in database
↓
Real-time cart updates via Alpine.js
```

#### Stock Reservation System
```
Item added to cart
↓
Stock reserved for 15 minutes
↓
Periodic cleanup removes expired items
↓
Stock released back to inventory
```

#### Guest vs Authenticated Cart
- **Guest**: Session-based cart storage
- **Authenticated**: Database cart dengan user_id
- **Merge Logic**: Guest cart merged to user cart on login

### 4. SISTEM CHECKOUT & ORDERS

#### Order Creation Process
```
User initiates checkout
↓
CheckoutController validates:
- Cart tidak kosong
- Address valid
- Payment method
↓
Order created dengan status PENDING
↓
Order items created dari cart
↓
Cart cleared after successful order
↓
Stripe PaymentIntent created
```

#### Order Status Flow
```
PENDING → PAID → PROCESSING → READY → COMPLETED
     ↘        ↗
      CANCELLED
```

#### Payment Integration
```
Frontend creates Stripe PaymentIntent
↓
User completes payment via Stripe
↓
Webhook receives payment confirmation
↓
Order status updated to PAID
↓
Stock definitively reserved
↓
Order processing begins
```

### 5. FULFILLMENT SYSTEM

#### Delivery vs Pickup Logic
```
Order created dengan fulfillment_method:
├── DELIVERY
│   ├── Requires customer address
│   ├── Shipping cost calculated
│   └── Status: PAID → PROCESSING → DELIVERED → COMPLETED
└── PICKUP
    ├── Store address used
    ├── No shipping cost
    ├── Pickup token generated
    └── Status: PAID → PROCESSING → READY → PICKED_UP → COMPLETED
```

#### Pickup Token System
```
Order ready for pickup
↓
Admin generates pickup token
↓
6-digit code created
↓
Customer receives notification
↓
Customer presents code at store
↓
Staff verifies code
↓
Order marked as PICKED_UP
```

### 6. ADMIN MANAGEMENT SYSTEM

#### Admin Dashboard Features
```
Real-time Statistics:
- Total orders today/month
- Revenue tracking
- Top selling products
- Recent orders monitoring
```

#### Order Management
```
Admin views all orders
↓
Can update order status
↓
Status changes trigger:
- Database updates
- Email notifications
- WebSocket broadcasts
- Customer notifications
```

#### Product Management
```
CRUD Operations:
- Create products dengan variants
- Upload product images
- Manage stock levels
- Activate/deactivate products
```

### 7. REAL-TIME COMMUNICATION

#### WebSocket Integration
```
Laravel Reverb Server
↓
Pusher-compatible protocol
↓
Frontend connects via Echo.js
↓
Real-time channels:
- Private user channels
- Public order channels
- Admin notification channels
```

#### Event Broadcasting
```
Order Status Updated
↓
OrderStatusUpdated Event fired
↓
Broadcast to relevant channels:
- Customer private channel
- Admin bell notifications
↓
Frontend receives update
↓
UI automatically updated
```

### 8. NOTIFICATION SYSTEM

#### Multi-Channel Notifications
```
Event Triggered (OrderStatusUpdated)
↓
Notifications sent via:
├── WebSocket (real-time)
├── Email (asynchronous)
└── Browser notifications
```

#### Admin Bell Notifications
```
New order created
↓
NewOrderCreated event
↓
Broadcast to admin channel
↓
Bell icon updated dengan counter
↓
Admin dropdown shows notification
```

### 9. PAYMENT PROCESSING

#### Stripe Integration Flow
```
Checkout initiated
↓
PaymentIntent created via Stripe API
↓
Client secret returned to frontend
↓
Stripe Elements handles card processing
↓
Payment confirmation via webhook
↓
Order status automatically updated
```

#### Webhook Security
```
Stripe sends webhook
↓
Signature verification
↓
Event processing
↓
Order status update
↓
Customer notification
```

### 10. SEARCH & FILTERING

#### Product Search
```
Frontend search query
↓
API endpoint /api/products
↓
Database query dengan filters:
- Name pattern matching
- Category filtering
- Active status filtering
↓
Results returned dengan pagination
```

### 11. FILE UPLOAD SYSTEM

#### Product Image Handling
```
Admin uploads product image
↓
File validation (type, size)
↓
Image stored in public/uploads/products/
↓
Database record updated dengan path
↓
Product photos table managed
```

### 12. TESTING SYSTEM

#### End-to-End Testing
```
Playwright tests cover:
- Authentication flows
- Product browsing
- Cart operations
- Checkout process
- Admin functions
```

#### Test Categories
- **API Tests**: Backend functionality
- **E2E Tests**: Full user journeys
- **Component Tests**: Frontend components

### 13. SECURITY MEASURES

#### Authentication Security
```
JWT Tokens dengan:
- Role-based expiration
- Secure storage
- Automatic refresh
```

#### API Security
```
Middleware protection:
- JWT authentication
- Role-based authorization
- CSRF protection
- Input validation
```

#### Payment Security
```
Stripe integration provides:
- PCI compliance
- Secure card processing
- Webhook signature verification
```

### 14. PERFORMANCE OPTIMIZATIONS

#### Database Optimizations
```
Indexes pada:
- User lookups
- Product searches
- Order queries
- Cart operations
```

#### Frontend Optimizations
```
Alpine.js untuk:
- Reactive UI updates
- Minimal JavaScript overhead
- Component-based architecture
```

#### Caching Strategy
```
Configuration caching
Route caching
View caching
Database query optimization
```

### 15. ERROR HANDLING

#### API Error Responses
```
Consistent error format:
{
  "success": false,
  "message": "Error description",
  "errors": {...}
}
```

#### Frontend Error Handling
```
Axios interceptors
User-friendly notifications
Fallback UI states
Retry mechanisms
```

### 16. DEVELOPMENT WORKFLOW

#### Local Development
```
composer install
npm install
php artisan migrate
php artisan db:seed
npm run dev
php artisan serve
```

#### Testing Workflow
```
php artisan test (Laravel tests)
npm run test (Playwright E2E)
npm run test:api (API tests)
```

#### Deployment Process
```
composer install --optimize-autoloader --no-dev
npm run build
php artisan config:cache
php artisan route:cache
php artisan migrate --force
```

## FITUR UNGGULAN

### 1. Real-time Updates
- Order status changes langsung terupdate
- Admin dashboard real-time
- Cart synchronization across devices

### 2. Smart Stock Management
- Automatic stock reservation
- Expired reservation cleanup
- Stock availability validation

### 3. Flexible Fulfillment
- Delivery dengan address management
- Pickup dengan token system
- Dynamic shipping cost calculation

### 4. Role-based Access Control
- Customer, Merchant, Admin roles
- Feature access based on permissions
- Secure API endpoints

### 5. Modern Payment Integration
- Stripe secure payments
- Multiple payment methods
- Automatic order processing

### 6. Comprehensive Admin Tools
- Order management dashboard
- Product catalog management
- Customer management
- Real-time analytics

## MAINTENANCE & MONITORING

### Regular Tasks
- Database backup
- Log monitoring
- Performance monitoring
- Security updates

### Scaling Considerations
- Database optimization
- Redis untuk caching
- CDN untuk static assets
- Load balancer setup

## KESIMPULAN

Aplikasi BellGas menggunakan arsitektur modern dengan pemisahan yang jelas antara frontend dan backend, sistem real-time yang responsif, dan integrasi payment yang aman. Aplikasi ini dirancang untuk scalability dan maintainability dengan mengikuti best practices development Laravel dan modern web development.