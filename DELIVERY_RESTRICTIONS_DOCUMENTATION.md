# BellGas Delivery Restrictions Documentation

## Overview
The BellGas system implements strict delivery restrictions based on product categories to ensure safe and compliant gas delivery operations.

## Business Rules

### Product Categories
The system supports two main product categories:

1. **FULL_TANK**: New gas cylinders/canisters that can be delivered
2. **REFILL**: Gas refill services that require pickup only

### Delivery Restrictions

#### FULL_TANK Products (Delivery Allowed)
- New LPG gas cylinders
- Portable LPG canisters
- Any new gas containers
- **Fulfillment Methods**: PICKUP or DELIVERY

#### REFILL Products (Pickup Only)
- LPG gas refills
- Cylinder exchange services
- Gas top-up services
- **Fulfillment Methods**: PICKUP only

## Implementation

### Database Schema

#### Products Table
```sql
products (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    slug VARCHAR(255),
    description TEXT,
    category ENUM('REFILL', 'FULL_TANK'),
    is_active BOOLEAN DEFAULT TRUE
)
```

### Model Methods

#### Product Model (`app/Models/Product.php`)
```php
/**
 * Check if product is a refill type
 */
public function isRefill(): bool
{
    return $this->category === 'REFILL';
}

/**
 * Check if product is a full tank type
 */
public function isFullTank(): bool
{
    return $this->category === 'FULL_TANK';
}

/**
 * Check if product can be delivered
 */
public function canBeDelivered(): bool
{
    return $this->category === 'FULL_TANK';
}
```

### Backend Validation

#### Request Validation (`app/Http/Requests/Checkout/CreatePaymentIntentRequest.php`)
```php
public function withValidator($validator): void
{
    $validator->after(function ($validator) {
        // Validate delivery restriction for refill products
        if ($this->fulfillment_method === 'DELIVERY' && $this->items) {
            $refillItems = [];
            foreach ($this->items as $index => $item) {
                $productVariant = \App\Models\ProductVariant::with('product')
                    ->find($item['product_variant_id'] ?? null);
                
                if ($productVariant && $productVariant->product->isRefill()) {
                    $refillItems[] = $productVariant->product->name . ' - ' . $productVariant->name;
                }
            }
            
            if (!empty($refillItems)) {
                $itemsList = implode(', ', $refillItems);
                $validator->errors()->add(
                    'fulfillment_method', 
                    "Refill products can only be picked up at the store. Please select 'PICKUP' for: {$itemsList}"
                );
            }
        }
    });
}
```

### Frontend Implementation

#### Checkout Page Logic (`resources/views/checkout/index.blade.php`)
```javascript
// Computed properties for delivery validation
get hasRefillItems() {
    return this.cart.some(item => 
        item.productVariant?.product?.category === 'REFILL'
    );
},

get canDeliverAllItems() {
    return !this.hasRefillItems;
},

get deliveryValidationMessage() {
    if (this.hasRefillItems) {
        const refillItems = this.cart
            .filter(item => item.productVariant?.product?.category === 'REFILL')
            .map(item => `${item.productVariant.product.name} - ${item.productVariant.name}`);
        
        return `These items can only be picked up: ${refillItems.join(', ')}`;
    }
    return '';
}
```

#### Auto-switching Logic
```javascript
// Watch for cart changes and auto-switch to pickup if needed
cartChanged() {
    if (this.hasRefillItems && this.fulfillmentMethod === 'DELIVERY') {
        this.fulfillmentMethod = 'PICKUP';
        this.showValidationMessage = true;
        
        setTimeout(() => {
            this.showValidationMessage = false;
        }, 5000);
    }
}
```

## User Experience

### Visual Indicators
- **Warning Message**: Displays when refill items are in cart and delivery is selected
- **Auto-switching**: Automatically changes to pickup when refill items are added
- **Disabled State**: Delivery option becomes disabled when refill items are present
- **Clear Messaging**: Explains which specific items require pickup

### UI Components
```html
<!-- Delivery restriction warning -->
<div x-show="hasRefillItems && fulfillmentMethod === 'DELIVERY'" 
     class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400"><!-- Warning icon --></svg>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Delivery Not Available</h3>
            <p class="mt-1 text-sm text-red-700" x-text="deliveryValidationMessage"></p>
        </div>
    </div>
</div>

<!-- Fulfillment method selection -->
<div class="space-y-3">
    <label class="flex items-center">
        <input type="radio" x-model="fulfillmentMethod" value="PICKUP" 
               class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
        <span class="ml-2 text-sm font-medium text-gray-700">Pickup at Store</span>
    </label>
    
    <label class="flex items-center" 
           :class="{ 'opacity-50 cursor-not-allowed': !canDeliverAllItems }">
        <input type="radio" x-model="fulfillmentMethod" value="DELIVERY" 
               :disabled="!canDeliverAllItems"
               class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
        <span class="ml-2 text-sm font-medium text-gray-700">Delivery</span>
        <span x-show="!canDeliverAllItems" 
              class="ml-2 text-xs text-red-600">(Not available for refill items)</span>
    </label>
</div>
```

## Error Messages

### Backend Validation Errors
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "fulfillment_method": [
            "Refill products can only be picked up at the store. Please select 'PICKUP' for: LPG Gas Refill - 9kg, LPG Gas Refill - 19kg"
        ]
    }
}
```

### Frontend Messages
- **Auto-switch notification**: "Switched to pickup due to refill items in cart"
- **Validation warning**: "These items can only be picked up: [item names]"
- **Button state**: "Delivery (Not available for refill items)"

## Testing Scenarios

### Test Cases

#### 1. Full Tank Only - Should Allow Delivery
```javascript
// Cart with only FULL_TANK products
cart = [
    { 
        productVariant: { 
            product: { category: 'FULL_TANK', name: 'LPG Full Tank' } 
        } 
    }
];
// Expected: Both PICKUP and DELIVERY options available
```

#### 2. Refill Only - Should Force Pickup
```javascript
// Cart with only REFILL products
cart = [
    { 
        productVariant: { 
            product: { category: 'REFILL', name: 'LPG Gas Refill' } 
        } 
    }
];
// Expected: Only PICKUP option available, DELIVERY disabled
```

#### 3. Mixed Cart - Should Force Pickup
```javascript
// Cart with both FULL_TANK and REFILL products
cart = [
    { productVariant: { product: { category: 'FULL_TANK', name: 'LPG Full Tank' } } },
    { productVariant: { product: { category: 'REFILL', name: 'LPG Gas Refill' } } }
];
// Expected: Only PICKUP option available, warning message shown
```

### Manual Testing Steps

1. **Add Full Tank Product to Cart**
   - Navigate to products page
   - Add a FULL_TANK product to cart
   - Go to checkout
   - Verify both PICKUP and DELIVERY options are available

2. **Add Refill Product to Cart**
   - Add a REFILL product to cart
   - Verify automatic switch to PICKUP
   - Verify DELIVERY option is disabled
   - Verify warning message appears

3. **API Testing**
   - Send POST request to `/api/checkout/create-payment-intent`
   - With `fulfillment_method: "DELIVERY"` and refill products
   - Verify 422 validation error response

## Configuration

### Product Category Setup
```php
// Migration for products table
$table->enum('category', ['REFILL', 'FULL_TANK']);
```

### Seeder Data Example
```php
// ProductSeeder.php
Product::create([
    'name' => 'LPG Full Tank',
    'category' => 'FULL_TANK',
    'description' => 'New 9kg LPG gas cylinder'
]);

Product::create([
    'name' => 'LPG Gas Refill',
    'category' => 'REFILL', 
    'description' => '9kg LPG gas refill service'
]);
```

## Safety and Compliance

### Regulatory Compliance
- Refill services require proper equipment and trained personnel
- Delivery of gas refills may violate safety regulations
- Full tanks are pre-inspected and safe for delivery

### Safety Considerations
- Refill process requires inspection of customer cylinders
- On-site refilling ensures proper safety protocols
- Delivery drivers are not trained for gas handling procedures

## Maintenance

### Adding New Product Categories
1. Update database enum in migration
2. Add new methods to Product model
3. Update validation logic in request classes
4. Update frontend logic for new restrictions
5. Update documentation

### Monitoring
- Log delivery restriction violations
- Track auto-switching events
- Monitor user experience metrics for restriction clarity