# BellGas - Gas Cylinder E-commerce Platform

A modern Laravel-based e-commerce platform specifically designed for gas cylinder sales and distribution, featuring role-based access control, real-time inventory management, and comprehensive admin tools.

## 🚀 Features

### Customer Features
- **Product Catalog**: Browse gas cylinders by category with detailed specifications
- **Shopping Cart**: Add/remove items with real-time stock validation
- **Secure Checkout**: Stripe payment integration with order tracking
- **User Authentication**: JWT-based authentication with profile management
- **Address Management**: Multiple delivery addresses support
- **Order History**: Track order status and download invoices

### Admin/Merchant Features
- **Product Management**: Full CRUD operations for products and variants
- **Order Management**: Process orders, update status, manage fulfillment
- **Customer Management**: View customer profiles and order history
- **Inventory Control**: Real-time stock tracking with reservation system
- **Analytics Dashboard**: Sales reports and performance metrics
- **System Settings**: Configure site settings and payment methods

### Technical Features
- **Role-Based Access Control**: Customer, Merchant, Admin roles
- **Real-time Inventory**: Stock reservation system prevents overselling
- **Responsive Design**: Mobile-first design with Tailwind CSS
- **API-First Architecture**: RESTful APIs with comprehensive documentation
- **Security**: JWT authentication, CSRF protection, input validation

## 🛠️ Technology Stack

- **Backend**: Laravel 12.x (PHP 8.2+)
- **Database**: SQLite (development), MySQL/PostgreSQL (production)
- **Authentication**: JWT with tymon/jwt-auth
- **Frontend**: Blade templates with Alpine.js
- **Styling**: Tailwind CSS
- **Payment**: Stripe API
- **Testing**: Playwright E2E tests
- **Development**: Vite build system

## 📋 Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- SQLite (for development)

## 🔧 Quick Start

### 1. Clone and Install
```bash
git clone <repository-url>
cd bellgas-laravel
composer install
npm install
```

### 2. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

### 3. Configure Environment
Update your `.env` file:
```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

STRIPE_KEY=your_stripe_publishable_key
STRIPE_SECRET=your_stripe_secret_key
STRIPE_WEBHOOK_SECRET=your_stripe_webhook_secret

JWT_SECRET=your_jwt_secret
JWT_TTL=60
```

### 4. Database Setup
```bash
touch database/database.sqlite
php artisan migrate
php artisan db:seed
```

### 5. Build Assets and Start Server
```bash
npm run build
php artisan serve
```

Visit `http://localhost:8000` to access the application.

## 👤 Default Accounts

After seeding, you can use these accounts:

**Admin Account:**
- Email: admin@bellgas.com
- Password: password
- Role: ADMIN

**Customer Account:**
- Email: customer@bellgas.com
- Password: password
- Role: CUSTOMER

## 📚 Documentation

- **[Complete Documentation](DOCUMENTATION.md)** - Comprehensive guide covering architecture, deployment, and troubleshooting
- **[API Reference](API_REFERENCE.md)** - Detailed API documentation with examples
- **Admin Panel**: Access via `/admin/dashboard` (admin role required)
- **API Base URL**: `http://localhost:8000/api/v1/`

## 🧪 Testing

### Run E2E Tests
```bash
# Install Playwright
npx playwright install

# Run all tests
npx playwright test

# Run specific test
npx playwright test comprehensive-fix-test.spec.js
```

### Test Coverage
- Authentication flows
- Product catalog browsing
- Cart operations
- Checkout process
- Admin dashboard functionality

## 🏗️ Project Structure

```
bellgas-laravel/
├── app/
│   ├── Http/Controllers/          # API and Web controllers
│   ├── Models/                    # Eloquent models
│   ├── Middleware/                # Custom middleware
│   └── Services/                  # Business logic services
├── database/
│   ├── migrations/                # Database migrations
│   └── seeders/                   # Database seeders
├── resources/
│   ├── views/                     # Blade templates
│   └── js/                        # Frontend assets
├── routes/
│   ├── web.php                    # Web routes
│   └── api.php                    # API routes
└── tests/                         # Playwright tests
```

## 🔐 Authentication & Roles

### Role Hierarchy
- **ADMIN**: Full system access
- **MERCHANT**: Product and order management
- **CUSTOMER**: Shopping and account management

### API Authentication
```bash
# Login to get JWT token
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@bellgas.com","password":"password"}'

# Use token in subsequent requests
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  http://localhost:8000/api/v1/products
```

## 🚀 Deployment

### Production Checklist
- [ ] Configure production database
- [ ] Set up Stripe production keys
- [ ] Configure mail service
- [ ] Set `APP_ENV=production`
- [ ] Enable HTTPS
- [ ] Set up queue workers
- [ ] Configure file storage

### Quick Deploy Commands
```bash
composer install --optimize-autoloader --no-dev
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
```

## 🐛 Troubleshooting

### Common Issues

**JWT Token Issues**
```bash
php artisan jwt:secret
php artisan config:clear
```

**Permission Errors**
```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

**Database Connection**
- Ensure database file exists for SQLite
- Check database credentials in `.env`
- Run `php artisan migrate:fresh --seed`

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License.

## 🆘 Support

For support and questions:
- Check the [Documentation](DOCUMENTATION.md)
- Review [API Reference](API_REFERENCE.md)
- Create an issue in the repository

---

Built with ❤️ using Laravel and modern web technologies.
