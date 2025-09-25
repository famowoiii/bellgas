# BellGas - Complete Application Documentation

## 📋 Table of Contents
1. [Overview](#overview)
2. [System Architecture](#system-architecture)
3. [Installation & Setup](#installation--setup)
4. [Database Schema](#database-schema)
5. [API Documentation](#api-documentation)
6. [Authentication & Authorization](#authentication--authorization)
7. [Frontend Architecture](#frontend-architecture)
8. [Payment Integration](#payment-integration)
9. [Admin Panel](#admin-panel)
10. [Testing Strategy](#testing-strategy)
11. [Deployment Guide](#deployment-guide)
12. [Troubleshooting](#troubleshooting)

---

## 📖 Overview

**BellGas** is a comprehensive e-commerce platform designed specifically for LPG (Liquefied Petroleum Gas) delivery services in Australia. The application provides a complete solution for customers to order gas cylinders online, make secure payments, and track deliveries or schedule pickups.

### 🎯 Key Features
- **Multi-role system**: Customer, Merchant, Admin roles with different access levels
- **Complete e-commerce flow**: Product browsing, cart management, checkout, payment
- **Dual fulfillment**: Both delivery and pickup options
- **Secure payment**: Stripe integration with Australian payment methods
- **Admin management**: Comprehensive admin panel for business operations
- **Mobile responsive**: Fully responsive design for all devices
- **Real-time updates**: JWT authentication with real-time cart and order updates

### 🛠 Technology Stack
- **Backend**: Laravel 12.x, PHP 8.2+
- **Database**: SQLite (dev) / MySQL (production)
- **Frontend**: Alpine.js, Tailwind CSS, Blade Templates
- **Authentication**: JWT (JSON Web Tokens)
- **Payments**: Stripe Payment Gateway
- **Testing**: PHPUnit, Playwright
- **Build Tools**: Vite, Laravel Mix

---

## 🏗 System Architecture

### MVC Architecture
The application follows Laravel's MVC pattern with additional service layer:

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│     Views       │    │   Controllers   │    │     Models      │
│  (Blade/Alpine) │◄──►│   (API/Web)     │◄──►│   (Eloquent)    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                               │
                               ▼
                    ┌─────────────────┐
                    │   Services      │
                    │  (Business Logic)│
                    └─────────────────┘
```

### Core Components

#### Controllers
- **API Controllers**: RESTful endpoints for frontend consumption
  - `AuthController`: User authentication and registration
  - `ProductController`: Product catalog management
  - `CartController`: Shopping cart operations
  - `OrderController`: Order processing and tracking
  - `PaymentController`: Stripe payment handling

- **Web Controllers**: Traditional Laravel controllers for web routes
  - `AuthController`: Web-based authentication
  - Session management and redirects

- **Admin Controllers**: Administrative functionality
  - `DashboardController`: Admin analytics and reporting

#### Models & Relationships
```
User ──┬── Address (1:N)
       ├── Order (1:N)
       └── Cart (1:N)

Product ──┬── ProductVariant (1:N)
          ├── ProductPhoto (1:N)
          └── Category (N:1)

Order ──┬── OrderItem (1:N)
        ├── OrderEvent (1:N)
        ├── Address (N:1)
        └── User (N:1)
```

#### Services
- **StripeApiService**: Payment processing logic
- **PickupTokenService**: Secure pickup verification
- **PdfReceiptService**: PDF receipt generation
- **ShippingCalculatorService**: Delivery cost calculation

---

## 🚀 Installation & Setup

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js & npm
- SQLite (for development)

### Quick Start

1. **Clone Repository**
   ```bash
   git clone <repository-url>
   cd bellgas-laravel
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   php artisan jwt:secret
   ```

4. **Database Setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Build Assets**
   ```bash
   npm run build
   ```

6. **Start Development Server**
   ```bash
   composer run dev
   # Or individually:
   # php artisan serve
   # npm run dev
   ```

### Environment Configuration

Key `.env` variables:
```env
# Application
APP_NAME="BellGas"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Stripe Configuration
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# JWT Configuration
JWT_SECRET=your-jwt-secret
JWT_TTL=60

# Mail Configuration (for receipts)
MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@bellgas.com.au"
MAIL_FROM_NAME="BellGas"
```

---

## 🗄 Database Schema

### Core Tables

#### Users
```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(255),
    role VARCHAR(255) DEFAULT 'CUSTOMER', -- CUSTOMER, MERCHANT, ADMIN
    is_active BOOLEAN DEFAULT 1,
    email_verified_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### Products & Variants
```sql
CREATE TABLE products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    category_id INTEGER,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE product_variants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    name VARCHAR(255) NOT NULL,
    weight_kg DECIMAL(8,2),
    price_aud DECIMAL(10,2) NOT NULL,
    stock_quantity INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id)
);
```

#### Orders
```sql
CREATE TABLE orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_number VARCHAR(255) UNIQUE NOT NULL,
    user_id INTEGER NOT NULL,
    address_id INTEGER,
    status VARCHAR(255) DEFAULT 'UNPAID', -- UNPAID, PAID, PROCESSING, READY_FOR_PICKUP, COMPLETED, CANCELLED
    fulfillment_method VARCHAR(255) NOT NULL, -- PICKUP, DELIVERY
    subtotal_aud DECIMAL(10,2) NOT NULL,
    shipping_cost_aud DECIMAL(10,2) DEFAULT 0,
    total_aud DECIMAL(10,2) NOT NULL,
    stripe_payment_intent_id VARCHAR(255),
    customer_notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (address_id) REFERENCES addresses(id)
);
```

#### Shopping Cart
```sql
CREATE TABLE carts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    session_id VARCHAR(255),
    product_variant_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    is_preorder BOOLEAN DEFAULT 0,
    reserved_until TIMESTAMP,
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id)
);
```

### Relationships Summary
- **Users** have many **Orders**, **Addresses**, **Cart Items**
- **Products** have many **ProductVariants**, **ProductPhotos**
- **Orders** have many **OrderItems**, **OrderEvents**
- **Categories** have many **Products**

---

## 🔌 API Documentation

### Base URL
```
Development: http://localhost:8000/api
Production: https://your-domain.com/api
```

### Authentication Header
```http
Authorization: Bearer <jwt_token>
```

### Response Format
All API responses follow this structure:
```json
{
    "success": true,
    "message": "Operation successful",
    "data": { ... },
    "errors": null
}
```

### Core Endpoints

#### 🔐 Authentication
```http
POST /auth/register
POST /auth/login
POST /auth/logout
GET  /auth/me
POST /auth/refresh
POST /auth/forgot-password
POST /auth/reset-password
```

**Example Login:**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "customer@example.com",
    "password": "password"
  }'
```

#### 📦 Products
```http
GET  /products              # List all products
GET  /products/{id}         # Get product details
GET  /products/categories   # Get product categories
POST /products              # Create product (Merchant only)
```

#### 🛒 Shopping Cart
```http
GET    /cart              # Get cart items
POST   /cart              # Add item to cart
PUT    /cart/{id}         # Update cart item quantity
DELETE /cart/{id}         # Remove cart item
DELETE /cart              # Clear entire cart
GET    /cart/count        # Get cart items count
```

**Example Add to Cart:**
```bash
curl -X POST http://localhost:8000/api/cart \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "product_variant_id": 1,
    "quantity": 2,
    "is_preorder": false,
    "notes": "Delivery instructions here"
  }'
```

#### 📋 Orders
```http
GET    /orders                    # Get user orders
POST   /orders                    # Create new order
GET    /orders/{id}               # Get order details
PUT    /orders/{id}               # Update order (Admin only)
PATCH  /orders/{id}/cancel        # Cancel order
POST   /orders/{id}/reorder       # Reorder items
```

**Example Create Order:**
```bash
curl -X POST http://localhost:8000/api/orders \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "address_id": 1,
    "payment_method": "CARD",
    "fulfillment_method": "DELIVERY",
    "notes": "Handle with care"
  }'
```

#### 💳 Payments
```http
POST /checkout/create-payment-intent
POST /payments/orders/{id}/intent
POST /payments/orders/{id}/complete
POST /payments/orders/{id}/simulate
```

#### 🏠 Addresses
```http
GET    /addresses          # Get user addresses
POST   /addresses          # Create address
GET    /addresses/{id}     # Get address details
PUT    /addresses/{id}     # Update address
DELETE /addresses/{id}     # Delete address
```

#### 👑 Admin Only
```http
GET /admin/dashboard                    # Dashboard stats
GET /admin/dashboard/recent-orders      # Recent orders
GET /admin/dashboard/sales-chart        # Sales analytics
GET /admin/dashboard/top-products       # Top selling products
GET /admin/dashboard/user-stats         # User statistics
GET /admin/dashboard/system-health      # System status
```

---

## 🔐 Authentication & Authorization

### JWT Authentication Flow

1. **User Login**
   ```javascript
   // Frontend login
   const response = await axios.post('/api/auth/login', {
     email: 'user@example.com',
     password: 'password'
   });
   
   // Store token
   localStorage.setItem('access_token', response.data.access_token);
   axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
   ```

2. **Token Usage**
   ```javascript
   // All subsequent API calls automatically include token
   const orders = await axios.get('/api/orders');
   ```

3. **Token Refresh**
   ```javascript
   // Refresh token when it expires
   const refreshResponse = await axios.post('/api/auth/refresh');
   localStorage.setItem('access_token', refreshResponse.data.access_token);
   ```

### Role-Based Access Control

#### User Roles
- **CUSTOMER**: Default role for end users
  - Can browse products, manage cart, place orders
  - Access to personal dashboard and order history

- **MERCHANT**: Business users who can manage products
  - All customer permissions
  - Can create and manage products
  - Access to merchant dashboard

- **ADMIN**: Full system access
  - All merchant permissions
  - User management, system configuration
  - Access to admin dashboard with analytics

#### Middleware Protection
```php
// API routes protected by role
Route::middleware(['jwt.auth', 'role:admin,merchant'])->group(function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'index']);
});

// Web routes protected by role
Route::middleware(['auth', 'role:admin,merchant'])->prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    });
});
```

### Security Features
- **Password Hashing**: bcrypt with salt
- **CSRF Protection**: Laravel built-in CSRF middleware
- **Rate Limiting**: API endpoint rate limiting
- **SQL Injection Protection**: Eloquent ORM prepared statements
- **XSS Protection**: Blade template escaping

---

## 🎨 Frontend Architecture

### Alpine.js Component Structure

The frontend uses Alpine.js for reactivity with a component-based approach:

#### Main App Component (`layouts/app.blade.php`)
```javascript
function app() {
    return {
        // State
        user: null,
        cartCount: 0,
        
        // Lifecycle
        async init() {
            await this.loadUser();
            await this.loadCart();
        },
        
        // Methods
        async loadUser() {
            const token = localStorage.getItem('access_token');
            if (token) {
                try {
                    const response = await axios.get('/api/auth/me');
                    this.user = response.data.user;
                } catch (error) {
                    this.logout();
                }
            }
        },
        
        async logout() {
            // Logout logic
        }
    }
}
```

#### Shopping Cart Component
```javascript
function cart() {
    return {
        items: [],
        loading: false,
        
        async loadCart() {
            const response = await axios.get('/api/cart');
            this.items = response.data.data.items;
        },
        
        async updateQuantity(item, newQuantity) {
            await axios.put(`/api/cart/${item.id}`, {
                quantity: newQuantity
            });
            await this.loadCart();
        }
    }
}
```

### Styling with Tailwind CSS

The application uses Tailwind CSS for styling with a consistent design system:

#### Color Palette
```css
/* Primary Colors */
--primary: #1e40af;      /* blue-800 */
--secondary: #3b82f6;    /* blue-600 */
--accent: #f59e0b;       /* amber-500 */

/* Status Colors */
--success: #10b981;      /* emerald-500 */
--warning: #f59e0b;      /* amber-500 */
--error: #ef4444;        /* red-500 */
--info: #3b82f6;         /* blue-500 */
```

#### Component Classes
```css
/* Buttons */
.btn-primary { @apply bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition; }
.btn-secondary { @apply bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition; }

/* Cards */
.card { @apply bg-white rounded-lg shadow-md p-6; }

/* Forms */
.form-input { @apply border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500; }
```

### Responsive Design

The application is fully responsive with mobile-first design:

```html
<!-- Mobile-first responsive grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <!-- Content -->
</div>

<!-- Mobile navigation -->
<div class="md:hidden" x-show="mobileMenuOpen">
    <!-- Mobile menu content -->
</div>
```

---

## 💳 Payment Integration

### Stripe Configuration

The application integrates with Stripe for secure payment processing:

#### Environment Setup
```env
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

#### Payment Flow

1. **Create Payment Intent**
   ```php
   // Backend - Create payment intent
   public function createPaymentIntent(Request $request)
   {
       $stripeService = new StripeApiService();
       $paymentIntent = $stripeService->createPaymentIntent(
           $order->total_aud * 100, // Amount in cents
           'aud'
       );
       
       return response()->json([
           'client_secret' => $paymentIntent->client_secret
       ]);
   }
   ```

2. **Frontend Payment Confirmation**
   ```javascript
   // Frontend - Confirm payment
   const {error} = await stripe.confirmCardPayment(clientSecret, {
       payment_method: {
           card: cardElement,
           billing_details: {
               name: 'Customer Name'
           }
       }
   });
   ```

3. **Webhook Processing**
   ```php
   // Handle Stripe webhooks
   public function handleStripeWebhook(Request $request)
   {
       $event = $request->all();
       
       switch ($event['type']) {
           case 'payment_intent.succeeded':
               // Update order status
               break;
           case 'payment_intent.payment_failed':
               // Handle failed payment
               break;
       }
   }
   ```

### Test Cards
For development, use Stripe's test cards:
- **Success**: 4242 4242 4242 4242
- **Declined**: 4000 0000 0000 0002
- **3D Secure**: 4000 0025 0000 3155

### Australian Payment Methods
- Credit/Debit Cards (Visa, Mastercard, Amex)
- Australian bank account verification
- BPAY integration (future feature)

---

## 👑 Admin Panel

### Dashboard Overview

The admin panel provides comprehensive business management tools:

#### Key Features
- **Sales Analytics**: Revenue tracking, order statistics
- **Order Management**: Process orders, update statuses
- **Product Management**: Add/edit products, manage inventory
- **Customer Management**: View customer data, manage accounts
- **System Settings**: Configure application settings

#### Admin Dashboard Components

1. **Metrics Cards**
   ```php
   $metrics = [
       'total_revenue' => Order::where('status', 'COMPLETED')->sum('total_aud'),
       'total_orders' => Order::count(),
       'active_customers' => User::where('is_active', true)->count(),
       'products_sold' => OrderItem::sum('quantity')
   ];
   ```

2. **Recent Orders Table**
   - Real-time order updates
   - Quick status changes
   - Order details modal

3. **Sales Chart**
   - Daily/weekly/monthly revenue
   - Order volume trends
   - Interactive charts

### Admin Navigation

The admin panel features a dedicated sidebar navigation:

```html
<nav class="admin-sidebar">
    <div class="admin-logo">
        <i class="fas fa-crown"></i>
        <span>Admin Panel</span>
    </div>
    
    <ul class="admin-menu">
        <li><a href="/admin/dashboard">Dashboard</a></li>
        <li><a href="/admin/orders">Order Management</a></li>
        <li><a href="/admin/products">Product Management</a></li>
        <li><a href="/admin/customers">Customer Management</a></li>
        <li><a href="/admin/settings">Settings</a></li>
    </ul>
</nav>
```

### Permission System

Admin access is controlled through role middleware:

```php
// Admin routes protected by role middleware
Route::middleware(['auth', 'role:admin,merchant'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/orders', [AdminController::class, 'orders']);
    // ... more admin routes
});
```

---

## 🧪 Testing Strategy

### Testing Pyramid

The application follows a comprehensive testing strategy:

```
        ┌─────────────────┐
        │   E2E Tests     │  ← Playwright (UI/Integration)
        │   (Playwright)  │
        └─────────────────┘
      ┌───────────────────────┐
      │   Integration Tests   │  ← Laravel Feature Tests
      │   (Laravel Feature)   │
      └───────────────────────┘
    ┌─────────────────────────────┐
    │      Unit Tests             │  ← PHPUnit Unit Tests
    │   (Services, Models)        │
    └─────────────────────────────┘
```

### Test Categories

#### 1. Unit Tests (PHPUnit)
```php
// tests/Unit/Services/StripeApiServiceTest.php
public function test_can_create_payment_intent()
{
    $service = new StripeApiService();
    $intent = $service->createPaymentIntent(1000, 'aud');
    
    $this->assertNotNull($intent);
    $this->assertEquals(1000, $intent['amount']);
    $this->assertEquals('aud', $intent['currency']);
}
```

#### 2. Feature Tests (Laravel)
```php
// tests/Feature/OrderTest.php
public function test_user_can_create_order()
{
    $user = User::factory()->create();
    $product = Product::factory()->create();
    
    $response = $this->actingAs($user, 'api')
        ->postJson('/api/orders', [
            'address_id' => 1,
            'payment_method' => 'CARD',
            'fulfillment_method' => 'DELIVERY'
        ]);
    
    $response->assertStatus(201);
    $this->assertDatabaseHas('orders', [
        'user_id' => $user->id
    ]);
}
```

#### 3. E2E Tests (Playwright)
```javascript
// tests/playwright/e2e/checkout.spec.js
test('complete checkout flow', async ({ page }) => {
    // Login
    await page.goto('/login');
    await page.fill('#email', 'test@example.com');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');
    
    // Add to cart
    await page.goto('/products');
    await page.click('.product-card:first-child .add-to-cart');
    
    // Checkout
    await page.goto('/checkout');
    await page.selectOption('#address_id', '1');
    await page.selectOption('#payment_method', 'CARD');
    await page.click('button[type="submit"]');
    
    // Verify success
    await expect(page.locator('.success-message')).toBeVisible();
});
```

### Running Tests

```bash
# PHP Unit Tests
php artisan test
php artisan test --filter OrderTest

# Playwright E2E Tests
npx playwright test
npx playwright test --headed
npx playwright test checkout.spec.js

# Coverage Reports
php artisan test --coverage
npx playwright test --reporter=html
```

### Test Data Management

#### Database Factories
```php
// database/factories/ProductFactory.php
class ProductFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->words(3, true),
            'slug' => $this->faker->slug(),
            'description' => $this->faker->paragraph(),
            'category_id' => Category::factory(),
            'is_active' => true
        ];
    }
}
```

#### Test Seeders
```php
// database/seeders/TestDataSeeder.php
public function run()
{
    User::factory()->create([
        'email' => 'admin@test.com',
        'role' => 'ADMIN'
    ]);
    
    Category::factory(3)->create();
    Product::factory(10)->create();
}
```

---

## 🚀 Deployment Guide

### Production Environment Setup

#### Server Requirements
- **PHP**: 8.2+ with extensions: BCMath, Ctype, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML
- **Web Server**: Nginx or Apache with URL rewriting
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Node.js**: 18+ for asset compilation
- **SSL Certificate**: Required for Stripe payments

#### Environment Configuration
```env
# Production .env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=bellgas_production
DB_USERNAME=db_user
DB_PASSWORD=secure_password

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Mail (Production)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your-smtp-user
MAIL_PASSWORD=your-smtp-password

# Stripe Production Keys
STRIPE_PUBLISHABLE_KEY=pk_live_...
STRIPE_SECRET_KEY=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

#### Deployment Steps

1. **Server Setup**
   ```bash
   # Update system
   sudo apt update && sudo apt upgrade -y
   
   # Install PHP and extensions
   sudo apt install php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl
   
   # Install Composer
   curl -sS https://getcomposer.org/installer | php
   sudo mv composer.phar /usr/local/bin/composer
   
   # Install Node.js
   curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
   sudo apt-get install -y nodejs
   ```

2. **Application Deployment**
   ```bash
   # Clone repository
   git clone https://github.com/your-repo/bellgas-laravel.git
   cd bellgas-laravel
   
   # Install dependencies
   composer install --optimize-autoloader --no-dev
   npm ci --only=production
   
   # Environment setup
   cp .env.production .env
   php artisan key:generate
   php artisan jwt:secret
   
   # Database migration
   php artisan migrate --force
   php artisan db:seed --class=ProductionSeeder
   
   # Build assets
   npm run build
   
   # Optimize for production
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   
   # Set permissions
   sudo chown -R www-data:www-data storage bootstrap/cache
   sudo chmod -R 775 storage bootstrap/cache
   ```

3. **Web Server Configuration**

   **Nginx Configuration:**
   ```nginx
   server {
       listen 80;
       server_name your-domain.com;
       return 301 https://$host$request_uri;
   }
   
   server {
       listen 443 ssl;
       server_name your-domain.com;
       
       ssl_certificate /path/to/cert.pem;
       ssl_certificate_key /path/to/key.pem;
       
       root /var/www/bellgas-laravel/public;
       index index.php index.html;
       
       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }
       
       location ~ \.php$ {
           fastcgi_pass unix:/run/php/php8.2-fpm.sock;
           fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
           include fastcgi_params;
       }
   }
   ```

4. **Process Management**
   ```bash
   # Install Supervisor for queue workers
   sudo apt install supervisor
   
   # Create supervisor config
   sudo nano /etc/supervisor/conf.d/bellgas-worker.conf
   ```
   
   ```ini
   [program:bellgas-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /var/www/bellgas-laravel/artisan queue:work --sleep=3 --tries=3 --timeout=90
   autostart=true
   autorestart=true
   user=www-data
   numprocs=4
   redirect_stderr=true
   stdout_logfile=/var/www/bellgas-laravel/storage/logs/worker.log
   ```

### CI/CD Pipeline

#### GitHub Actions Workflow
```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: php-actions/composer@v6
      - run: php artisan test
      
  deploy:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Deploy to server
        uses: appleboy/ssh-action@v0.1.5
        with:
          host: ${{ secrets.HOST }}
          username: ${{ secrets.USERNAME }}
          key: ${{ secrets.KEY }}
          script: |
            cd /var/www/bellgas-laravel
            git pull origin main
            composer install --no-dev --optimize-autoloader
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            sudo supervisorctl restart bellgas-worker:*
```

### Monitoring & Logging

#### Application Monitoring
```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'slack'],
    ],
    
    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'username' => 'BellGas Logger',
        'emoji' => ':boom:',
        'level' => 'critical',
    ],
],
```

#### Error Tracking
- **Laravel Telescope**: Development debugging
- **Sentry**: Production error tracking
- **New Relic**: Performance monitoring

---

## 🔧 Troubleshooting

### Common Issues & Solutions

#### 1. JWT Token Issues
**Problem**: Token authentication failing
```
Error: Token has expired
```

**Solution**:
```bash
# Regenerate JWT secret
php artisan jwt:secret

# Clear config cache
php artisan config:clear

# Check token expiry in config/jwt.php
'ttl' => env('JWT_TTL', 60), // minutes
```

#### 2. Payment Processing Errors
**Problem**: Stripe payments failing
```
Error: No such payment_intent: pi_xxx
```

**Solution**:
```bash
# Verify Stripe keys in .env
STRIPE_PUBLISHABLE_KEY=pk_test_xxx
STRIPE_SECRET_KEY=sk_test_xxx

# Test Stripe connection
php artisan tinker
>>> App\Services\StripeApiService::testConnection()
```

#### 3. Database Migration Errors
**Problem**: Migration fails in production
```
Error: SQLSTATE[42S01]: Base table or view already exists
```

**Solution**:
```bash
# Check migration status
php artisan migrate:status

# Rollback and retry
php artisan migrate:rollback --step=1
php artisan migrate

# Fresh migration (development only)
php artisan migrate:fresh --seed
```

#### 4. File Permission Errors
**Problem**: Storage/cache directories not writable
```
Error: file_put_contents(): failed to open stream: Permission denied
```

**Solution**:
```bash
# Set correct permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# SELinux systems
sudo setsebool -P httpd_can_network_connect 1
sudo setsebool -P httpd_unified 1
```

#### 5. Cart/Session Issues
**Problem**: Cart items not persisting
```
Error: Cart items disappear after page refresh
```

**Solution**:
```javascript
// Check JWT token storage
const token = localStorage.getItem('access_token');
if (!token) {
    // Token missing - redirect to login
    window.location.href = '/login';
}

// Verify API authentication
axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
```

#### 6. Admin Access Denied
**Problem**: Admin users cannot access admin panel
```
Error: Access denied. Insufficient permissions.
```

**Solution**:
```bash
# Check user roles in database
php artisan tinker
>>> User::where('email', 'admin@example.com')->first()->role
>>> User::where('email', 'admin@example.com')->update(['role' => 'ADMIN'])

# Verify middleware configuration
# Check app/Http/Middleware/RoleMiddleware.php
# Check bootstrap/app.php middleware aliases
```

### Debug Mode

#### Enable Debug Mode
```env
APP_DEBUG=true
APP_LOG_LEVEL=debug
```

#### Useful Artisan Commands
```bash
# Clear all caches
php artisan optimize:clear

# View routes
php artisan route:list

# View configuration
php artisan config:show

# Database queries
php artisan db:show

# Queue status
php artisan queue:work --verbose

# Test API endpoints
php artisan tinker
>>> $response = Http::get('http://localhost:8000/api/products');
>>> $response->json();
```

### Performance Optimization

#### Caching Strategy
```bash
# Production optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Clear caches
php artisan optimize:clear
```

#### Database Optimization
```php
// Add database indexes
Schema::table('orders', function (Blueprint $table) {
    $table->index(['user_id', 'status']);
    $table->index(['created_at']);
});

// Query optimization
Order::with(['user', 'items.productVariant'])
    ->where('status', 'PENDING')
    ->orderBy('created_at', 'desc')
    ->paginate(20);
```

---

## 📚 Additional Resources

### Documentation Links
- [Laravel Documentation](https://laravel.com/docs)
- [Alpine.js Documentation](https://alpinejs.dev/start-here)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Stripe API Documentation](https://stripe.com/docs/api)
- [Playwright Documentation](https://playwright.dev/docs/intro)

### Development Tools
- **Laravel Telescope**: Application debugging
- **Laravel Debugbar**: Query debugging
- **Postman**: API testing
- **TablePlus**: Database management

### Code Quality
```bash
# PHP Code Style
composer require --dev friendsofphp/php-cs-fixer
./vendor/bin/php-cs-fixer fix

# Static Analysis
composer require --dev phpstan/phpstan
./vendor/bin/phpstan analyse

# Security Audit
composer audit
npm audit
```

### Support & Maintenance
- Regular Laravel updates
- Security patches
- Database backups
- Performance monitoring
- User feedback integration

---

**BellGas Application Documentation v1.0**  
*Generated: December 2024*  
*Author: Development Team*  
*Contact: dev@bellgas.com.au*