<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING_PAYMENT = 'pending_payment';
    case PAYMENT_CONFIRMED = 'payment_confirmed';
    case PROCESSING = 'processing';
    case PARTIALLY_SHIPPED = 'partially_shipped';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::PENDING_PAYMENT => 'Pending Payment',
            self::PAYMENT_CONFIRMED => 'Payment Confirmed',
            self::PROCESSING => 'Processing Order',
            self::PARTIALLY_SHIPPED => 'Partially Shipped',
            self::COMPLETED => 'Delivered & Completed',
            self::CANCELLED => 'Cancelled',
            self::REFUNDED => 'Refunded',
        };
    }

    public function badgeColor(): string
    {
        return match($this) {
            self::PENDING_PAYMENT => 'amber',
            self::PAYMENT_CONFIRMED => 'sky',
            self::PROCESSING => 'indigo',
            self::PARTIALLY_SHIPPED => 'purple',
            self::COMPLETED => 'emerald',
            self::CANCELLED, self::REFUNDED => 'rose',
        };
    }

    public function canTransitionTo(OrderStatus $newStatus): bool
    {
        return match($this) {
            self::PENDING_PAYMENT => in_array($newStatus, [self::PAYMENT_CONFIRMED, self::CANCELLED]),
            self::PAYMENT_CONFIRMED => in_array($newStatus, [self::PROCESSING, self::CANCELLED]),
            self::PROCESSING => in_array($newStatus, [self::PARTIALLY_SHIPPED, self::COMPLETED, self::CANCELLED]),
            self::PARTIALLY_SHIPPED => in_array($newStatus, [self::COMPLETED]),
            self::COMPLETED => in_array($newStatus, [self::REFUNDED]),
            self::CANCELLED, self::REFUNDED => false,
        };
    }
}