<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private channel untuk user-specific order updates
Broadcast::channel('user.{userId}.orders', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Private channel untuk admin order updates (hanya admin yang bisa akses)
Broadcast::channel('admin-orders', function ($user) {
    return in_array($user->role, ['ADMIN', 'MERCHANT']);
});

// Public channel untuk semua order notifications yang bisa diakses admin
Broadcast::channel('public-admin-orders', function () {
    return true; // Semua user bisa akses, tapi hanya admin yang akan subscribe
});

// Channel untuk real-time order tracking
Broadcast::channel('order.{orderNumber}', function ($user, $orderNumber) {
    // User bisa mengakses channel order mereka sendiri atau admin bisa akses semua
    $order = \App\Models\Order::where('order_number', $orderNumber)->first();

    if (!$order) {
        return false;
    }

    return $user->id === $order->user_id || in_array($user->role, ['ADMIN', 'MERCHANT']);
});
