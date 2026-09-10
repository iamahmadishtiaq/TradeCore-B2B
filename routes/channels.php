<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Buyer can listen to their specific order tracking channel
Broadcast::channel('orders.{orderId}', function (User $user, int $orderId) {
    return Order::where('id', $orderId)->where('buyer_id', $user->id)->exists();
});

// Vendor can only listen to their own assigned dashboard stream
Broadcast::channel('vendor.{vendorId}', function (User $user, int $vendorId) {
    return $user->vendor && (int) $user->vendor->id === (int) $vendorId;
});