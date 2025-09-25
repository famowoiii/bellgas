# Test all API endpoints
$loginData = @{
    email = "stripetester@bellgas.com"
    password = "password123"
} | ConvertTo-Json

# Get login token
Write-Host "1. Testing Login..." -ForegroundColor Green
$loginResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/auth/login" -Method POST -Body $loginData -ContentType "application/json"
$token = $loginResponse.access_token
Write-Host "✅ Login successful, token: $($token.Substring(0,20))..." -ForegroundColor Green

$headers = @{
    'Authorization' = "Bearer $token"
    'Accept' = 'application/json'
    'Content-Type' = 'application/json'
}

# Test /me endpoint
Write-Host "`n2. Testing /api/auth/me..." -ForegroundColor Green
try {
    $meResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/auth/me" -Method GET -Headers $headers
    Write-Host "✅ User info: $($meResponse.user.first_name) $($meResponse.user.last_name)" -ForegroundColor Green
} catch {
    Write-Host "❌ Failed to get user info: $($_.Exception.Message)" -ForegroundColor Red
}

# Test addresses
Write-Host "`n3. Testing /api/addresses..." -ForegroundColor Green
try {
    $addressResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/addresses" -Method GET -Headers $headers
    Write-Host "✅ Addresses: $($addressResponse.data.Count) addresses found" -ForegroundColor Green
} catch {
    Write-Host "❌ Failed to get addresses: $($_.Exception.Message)" -ForegroundColor Red
}

# Test add address
Write-Host "`n4. Testing add address..." -ForegroundColor Green
$addressData = @{
    type = "HOME"
    name = "Test Address"
    street_address = "123 Test Street"
    suburb = "Test Suburb"
    state = "NSW"
    postcode = "2000"
    country = "Australia"
    is_default = $true
} | ConvertTo-Json

try {
    $addAddressResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/addresses" -Method POST -Body $addressData -Headers $headers
    Write-Host "✅ Address added: $($addAddressResponse.data.full_address)" -ForegroundColor Green
} catch {
    Write-Host "❌ Failed to add address: $($_.Exception.Message)" -ForegroundColor Red
}

# Test cart
Write-Host "`n5. Testing /api/cart..." -ForegroundColor Green
try {
    $cartResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/cart" -Method GET -Headers $headers
    Write-Host "✅ Cart: $($cartResponse.data.count) items, total: $($cartResponse.data.total)" -ForegroundColor Green
} catch {
    Write-Host "❌ Failed to get cart: $($_.Exception.Message)" -ForegroundColor Red
}

# Test add to cart
Write-Host "`n6. Testing add to cart..." -ForegroundColor Green
$cartData = @{
    product_variant_id = 1
    quantity = 1
    is_preorder = $false
    notes = "PowerShell test"
} | ConvertTo-Json

try {
    $addCartResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/cart" -Method POST -Body $cartData -Headers $headers
    Write-Host "✅ Added to cart: $($addCartResponse.data.productVariant.product.name)" -ForegroundColor Green
} catch {
    Write-Host "❌ Failed to add to cart: $($_.Exception.Message)" -ForegroundColor Red
}

# Test orders
Write-Host "`n7. Testing /api/orders..." -ForegroundColor Green
try {
    $ordersResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/orders" -Method GET -Headers $headers
    Write-Host "✅ Orders: $($ordersResponse.data.data.Count) orders found" -ForegroundColor Green
} catch {
    Write-Host "❌ Failed to get orders: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n🎉 All API tests completed!" -ForegroundColor Cyan