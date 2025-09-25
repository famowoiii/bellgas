# Test logout functionality
Write-Host "Testing Logout Functionality" -ForegroundColor Cyan

# Step 1: Login to get token
Write-Host "`n1. Logging in..." -ForegroundColor Green
$loginData = @{
    email = "stripetester@bellgas.com"
    password = "password123"
} | ConvertTo-Json

try {
    $loginResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/auth/login" -Method POST -Body $loginData -ContentType "application/json"
    $token = $loginResponse.access_token
    Write-Host "✅ Login successful" -ForegroundColor Green
    
    $headers = @{
        'Authorization' = "Bearer $token"
        'Accept' = 'application/json'
        'Content-Type' = 'application/json'
    }

    # Step 2: Test logout
    Write-Host "`n2. Testing logout..." -ForegroundColor Green
    try {
        $logoutResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/auth/logout" -Method POST -Headers $headers
        Write-Host "✅ Logout successful: $($logoutResponse.message)" -ForegroundColor Green
        
        # Step 3: Test if token is invalidated
        Write-Host "`n3. Testing if token is invalidated..." -ForegroundColor Green
        try {
            $testResponse = Invoke-RestMethod -Uri "http://localhost:8000/api/auth/me" -Method GET -Headers $headers
            Write-Host "❌ Token still valid - logout may not have worked properly" -ForegroundColor Red
        } catch {
            Write-Host "✅ Token invalidated - logout worked correctly" -ForegroundColor Green
        }
        
    } catch {
        Write-Host "❌ Logout failed: $($_.Exception.Message)" -ForegroundColor Red
        Write-Host "Response: $($_.Exception.Response)" -ForegroundColor Yellow
    }
    
} catch {
    Write-Host "❌ Login failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n🎉 Logout test completed!" -ForegroundColor Cyan