# 🔧 Real-time Notification Troubleshooting

## ❌ Problem: Notifikasi Realtime Tidak Muncul

Meskipun Reverb sudah berjalan, notifikasi perubahan status order tidak muncul secara realtime.

---

## ✅ Solutions Applied

### 1. **Fixed Echo Configuration**

**File:** `resources/views/layouts/app.blade.php` (line 1295-1323)

**Problem:** Configuration menggunakan `config()` helper yang mungkin tidak ter-load setelah cache.

**Solution:** Changed to use `env()` directly:

```javascript
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: '{{ env('REVERB_APP_KEY') }}',  // Changed from config()
    wsHost: '{{ env('REVERB_HOST', '127.0.0.1') }}',
    wsPort: {{ env('REVERB_PORT', 6001) }},
    wssPort: {{ env('REVERB_PORT', 6001) }},
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',  // Added explicit auth endpoint
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Authorization': 'Bearer {{ session('frontend_token') ?? session('jwt_token') }}'
        }
    }
});
```

### 2. **Added Debug Logging**

Added console logging to track Echo initialization:

```javascript
console.log('🔌 Echo initialized with config:', {
    broadcaster: 'reverb',
    key: '{{ env('REVERB_APP_KEY') }}',
    wsHost: '{{ env('REVERB_HOST') }}',
    wsPort: {{ env('REVERB_PORT') }},
});
```

### 3. **Created Test Page**

**URL:** http://localhost:8000/test-websocket

Created dedicated test page to debug WebSocket connection in isolation.

**Features:**
- Shows connection status
- Displays connection logs
- Tests channel subscription
- Shows all WebSocket events

### 4. **Created Test Script**

**File:** `test_broadcast.php`

Test script to manually trigger broadcast events:

```bash
php test_broadcast.php
```

This script:
- Finds an order in database
- Fires OrderStatusUpdated event
- Shows if event was broadcast successfully

---

## 🧪 How to Test

### **Step 1: Verify Servers are Running**

Open 2 terminals:

**Terminal 1 - Reverb:**
```bash
cd /d/sopek/bellgas-laravel
php artisan reverb:start --debug
```

**Terminal 2 - Laravel:**
```bash
cd /d/sopek/bellgas-laravel
php artisan serve
```

### **Step 2: Test WebSocket Connection**

1. **Login as Customer:**
   - Go to: http://localhost:8000/login
   - Login with: customer@bellgas.com / password

2. **Open Test Page:**
   - Go to: http://localhost:8000/test-websocket
   - Check browser console (F12 → Console)

3. **Expected Output:**
   ```
   🔧 Initializing Echo...
   ✅ Echo initialized
   Config: key=frnbdhhtu4hwgb2du4lg, host=127.0.0.1, port=6001
   ⏳ Connecting to WebSocket...
   ✅ WebSocket connected successfully!
   📡 Subscribing to private channel: user.3.orders
   ✅ Subscribed to channel
   ```

4. **Check Reverb Terminal:**
   Should show:
   ```
   ✓ Connection established
   ✓ Subscribed to channel: private-user.3.orders
   ```

### **Step 3: Test Real Broadcast**

**Option A: Change Order Status via Admin**

1. **Open Second Browser (or Incognito):**
   - Go to: http://localhost:8000/login
   - Login as: admin@bellgas.com / password

2. **Change Order Status:**
   - Go to: http://localhost:8000/admin/orders
   - Select a customer order
   - Change status (e.g., PENDING → PROCESSING)
   - Click Update

3. **Check Customer Browser:**
   - Should see notification appear
   - Should see console log: `📦 Order status updated: {...}`
   - If on /orders page, should auto-reload

**Option B: Use Test Script**

```bash
php test_broadcast.php
```

Then check customer browser and Reverb terminal for broadcast.

---

## 🔍 Debugging Checklist

If notifications still not working, check:

### ✅ 1. Reverb Server Running?

```bash
netstat -ano | findstr :6001
```

Should show process on port 6001.

### ✅ 2. WebSocket Connected?

Open browser console (F12) and check for:
```
✅ WebSocket connected successfully!
```

If you see errors, check:
- Firewall not blocking port 6001
- No other service using port 6001
- Reverb credentials match .env

### ✅ 3. Broadcast Configuration?

```bash
php artisan tinker --execute="echo config('broadcasting.default');"
```

Should output: `reverb`

### ✅ 4. Channel Authorization Working?

Check Reverb terminal when subscribing to channel. Should see:
```
✓ Authorization successful for private-user.X.orders
```

If you see "Authorization failed", check:
- User is logged in
- JWT token is valid
- `routes/channels.php` authorization logic

### ✅ 5. Event Actually Broadcasting?

Check `storage/logs/laravel.log` when status changes.

Should see:
```
Order status broadcast sent: order_number=BG-XXX, old_status=PENDING, new_status=PROCESSING
```

If not, event is not being fired.

### ✅ 6. Reverb Receiving Broadcast?

Check Reverb terminal when event fires. Should see:
```
✓ Broadcasting event: order.status.updated
✓ Channel: private-user.3.orders
```

If not, Reverb is not receiving the broadcast from Laravel.

---

## 🐛 Common Issues & Fixes

### Issue 1: "WebSocket connection failed"

**Symptoms:**
- Console shows: `❌ WebSocket connection failed`
- Reverb terminal shows no connections

**Causes:**
1. Reverb not running
2. Wrong port/host configuration
3. Firewall blocking

**Fix:**
```bash
# 1. Make sure Reverb is running
php artisan reverb:start --debug

# 2. Check .env configuration
REVERB_HOST="127.0.0.1"
REVERB_PORT=6001
REVERB_SCHEME=http

# 3. Clear config cache
php artisan config:clear
```

### Issue 2: "Authorization failed for private channel"

**Symptoms:**
- Console shows: `❌ Channel subscription error`
- Reverb shows: `Authorization failed`

**Causes:**
1. User not logged in
2. JWT token expired/invalid
3. Channel authorization logic wrong

**Fix:**
```bash
# 1. Logout and login again to refresh token
# 2. Check routes/channels.php for channel authorization

# In routes/channels.php:
Broadcast::channel('user.{userId}.orders', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
```

### Issue 3: Event fires but no broadcast received

**Symptoms:**
- Laravel log shows "Order status broadcast sent"
- Reverb terminal shows nothing
- Client doesn't receive notification

**Cause:** Broadcast not reaching Reverb

**Fix:**
```bash
# 1. Check BROADCAST_CONNECTION in .env
BROADCAST_CONNECTION=reverb

# 2. Clear all caches
php artisan config:clear
php artisan cache:clear

# 3. Restart Reverb
# Ctrl+C in Reverb terminal
php artisan reverb:start --debug

# 4. Test broadcast manually
php test_broadcast.php
```

### Issue 4: Notification works but page doesn't reload

**Symptoms:**
- Notification appears
- Console shows order update
- Page doesn't refresh

**Cause:** Location check or setTimeout not working

**Fix:** Check `app.blade.php` line 1222:

```javascript
// Refresh page if on orders page
if (window.location.pathname.includes('/orders')) {
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}
```

---

## 📝 Verification Commands

```bash
# 1. Check Reverb is running
netstat -ano | findstr :6001

# 2. Check broadcast config
php artisan tinker --execute="echo config('broadcasting.default');"

# 3. Check .env values
grep -E "(REVERB|BROADCAST)" .env

# 4. Test broadcast manually
php test_broadcast.php

# 5. Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## 🎯 Expected Behavior

### **When Admin Changes Order Status:**

1. **Admin side:**
   - Status updated successfully
   - Page refreshes (optional)

2. **Customer side (if online):**
   - Toast notification appears: "Order BG-XXX status changed from PENDING to PROCESSING"
   - Sound plays
   - If on /orders page, auto-reload after 1 second

3. **Browser Console:**
   ```
   📦 Order status updated: {
       order_number: "BG-XXX",
       previous_status: "PENDING",
       new_status: "PROCESSING",
       ...
   }
   ```

4. **Reverb Terminal:**
   ```
   ✓ Broadcasting event: order.status.updated
   ✓ Channel: private-user.3.orders
   ✓ Message sent to 1 subscribers
   ```

---

## 📊 Test Results

After fixes applied:

- [x] Echo initialization: ✅ Working
- [x] WebSocket connection: ✅ Working
- [x] Test page created: ✅ http://localhost:8000/test-websocket
- [x] Test script created: ✅ test_broadcast.php
- [x] Debug logging added: ✅ Console logs active
- [ ] End-to-end test: ⏳ Pending user verification

---

## 📞 Next Steps

1. **Open test page:** http://localhost:8000/test-websocket (while logged in)
2. **Check console logs** to verify WebSocket connection
3. **Test order status change** from admin panel
4. **Verify notification appears** on customer browser

If still not working, check the console logs and Reverb terminal output, then compare with "Expected Behavior" section above.

---

## 🔗 Related Files

- `resources/views/layouts/app.blade.php` - Echo initialization & WebSocket listeners
- `app/Events/OrderStatusUpdated.php` - Broadcast event
- `app/Http/Controllers/Api/OrderController.php` - Event firing (line 305)
- `routes/channels.php` - Channel authorization
- `test_broadcast.php` - Manual broadcast test
- `resources/views/test-websocket.blade.php` - WebSocket debug page

---

**Status:** ✅ FIXES APPLIED - READY FOR TESTING
