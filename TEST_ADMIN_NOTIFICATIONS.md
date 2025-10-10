# 🧪 Test Admin Notifications - Quick Guide

## 🎯 What to Test
Admin akan menerima notifikasi dalam **10 detik** ketika customer membayar order.

## ✅ Step-by-Step Test

### 1. Open Admin Browser
```
http://localhost:8000/quick-login/admin
```

**Expected Console Output** (tekan F12 untuk buka console):
```
🔄 Starting admin polling fallback (checks every 10 seconds)...
✅ Admin polling started at: 2025-01-09T12:34:56.789Z
```

Jika muncul pesan ini, polling **SUDAH JALAN** ✅

---

### 2. Open Customer Browser (Incognito/Private)
```
http://localhost:8000/quick-login/customer
```

---

### 3. Make Order as Customer
1. Browse products: `http://localhost:8000/products`
2. Add product to cart (misal: Gas Cylinder 9kg)
3. Go to checkout: `http://localhost:8000/checkout`
4. Click "Proceed to Payment"
5. Use Stripe test card:
   ```
   Card Number: 4242 4242 4242 4242
   Expiry: Any future date (e.g., 12/25)
   CVC: Any 3 digits (e.g., 123)
   ```
6. Complete payment

---

### 4. Watch Admin Browser 👀

**Within 10 seconds**, you will see in console:
```
🔔 POLLING: Found 1 new paid orders!
💰 New PAID order via polling: ORD-20250109-XXXX
```

**AND you will see:**
- ✅ **Toast notification** (bottom-right) with green background
- ✅ **Bell icon** shows red badge with number
- ✅ **Sound plays** (beep notification)
- ✅ **Page auto-refreshes** after 3 seconds (if on admin orders page)

---

## 🔍 Troubleshooting

### If notification doesn't appear:

#### Check 1: Is polling running?
Open browser console and type:
```javascript
Alpine.store('app').pollingInterval
```

**Expected**: Should return a number (interval ID)
**If null**: Polling not started - check user role

#### Check 2: Check user role
```javascript
Alpine.store('app').user
```

**Expected**: Should show `role: "ADMIN"` or `role: "MERCHANT"`

#### Check 3: Manual polling trigger
```javascript
Alpine.store('app').pollForNewOrders()
```

Watch console for any errors.

#### Check 4: Check last polling time
```javascript
Alpine.store('app').lastPollingCheck
```

**Expected**: Should show recent timestamp

#### Check 5: Test backend API directly
Get token from localStorage:
```javascript
console.log(localStorage.getItem('access_token'))
```

Then test with curl:
```bash
curl -X GET "http://localhost:8000/api/admin/notifications/new-paid-orders?last_check=2025-01-09T00:00:00Z" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Expected**: JSON response with orders

---

## 📊 Expected Timeline

```
T+0s    Admin opens admin page
T+2s    First polling check (immediate)
T+12s   Second polling check
T+22s   Third polling check
...     Continues every 10 seconds

When order paid:
T+Xs    Customer completes payment → Order status = PAID
T+Xs+10 Admin polling detects new order → Shows notification
```

**Maximum delay**: 10 seconds from payment to notification

---

## 🎉 Success Indicators

You'll know it's working when you see:

### In Browser Console:
```
🔔 POLLING: Found 1 new paid orders!
💰 New PAID order via polling: ORD-20250109-XXXX
```

### On Screen:
1. **Green toast notification** slides in from right with message:
   ```
   Order ORD-20250109-XXXX has been PAID! Total: $150.00
   ```

2. **Bell icon** (top-right) shows red badge with number

3. **Notification sound** plays (beep)

4. If on admin orders/dashboard page, **page refreshes** after 3 seconds

### In Bell Dropdown:
Click bell icon → Shows notification list with:
- Order number
- Customer name
- Amount
- Timestamp

---

## 🔧 Configuration

### Change Polling Interval
**File**: `resources/views/layouts/app.blade.php` (line 1294)

```javascript
// Change 10000 (10 seconds) to your desired interval
this.pollingInterval = setInterval(() => {
    this.pollForNewOrders();
}, 10000); // milliseconds
```

**Examples**:
- `5000` = 5 seconds (faster, more server load)
- `10000` = 10 seconds (recommended)
- `30000` = 30 seconds (slower, less server load)

---

## 🚀 Advanced Testing

### Test with Multiple Orders
1. Make 3 orders as customer quickly
2. Watch admin browser
3. Should see 3 notifications appear

### Test Bell Icon
1. Receive notification
2. Click bell icon (top-right)
3. Should see notification in dropdown list
4. Click "Clear All" → Badge should disappear

### Test Page Refresh
1. Go to `http://localhost:8000/admin/orders`
2. Make order as customer
3. Watch admin browser auto-refresh after notification

### Test Persistence
1. Receive notification
2. Refresh admin page
3. Bell icon should still show badge
4. Notification history preserved in localStorage

---

## 📝 Manual Testing Checklist

- [ ] Polling starts when admin logs in
- [ ] Console shows polling messages every 10 seconds
- [ ] Customer can complete payment successfully
- [ ] Admin receives notification within 10 seconds
- [ ] Toast notification appears (bottom-right)
- [ ] Bell icon shows badge
- [ ] Notification sound plays
- [ ] Bell dropdown shows notification details
- [ ] Page auto-refreshes (if on admin page)
- [ ] Notifications persist after page refresh
- [ ] "Clear All" removes notifications
- [ ] Multiple orders show multiple notifications

---

## 🎯 Final Check

Run this in admin browser console:
```javascript
// Complete system check
console.log('✅ Polling Running:', Alpine.store('app').pollingInterval !== null);
console.log('✅ User Role:', Alpine.store('app').user?.role);
console.log('✅ Last Check:', Alpine.store('app').lastPollingCheck);
console.log('✅ Bell Count:', Alpine.store('app').bellCount);
console.log('✅ Bell Notifications:', Alpine.store('app').bellNotifications);

// Force a polling check now
Alpine.store('app').pollForNewOrders();
```

---

## 💡 Tips

1. **Keep admin browser console open** during testing to see all logs
2. **Use incognito/private window** for customer to avoid session conflicts
3. **Wait at least 10 seconds** after payment for notification
4. **Check bell icon** if you miss the toast notification
5. **Refresh page** if polling seems stuck

---

## 🆘 Need Help?

See detailed documentation:
- `POLLING_SOLUTION_COMPLETE.md` - Complete technical documentation
- `SESSION_SUMMARY.md` - Full session summary
- `QUICK_FIX_POLLING.md` - Quick overview

---

**READY TO TEST!** 🚀

Open admin browser, make a test order, and watch the magic happen! ✨
