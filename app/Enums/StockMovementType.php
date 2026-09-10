<?php

namespace App\Enums;

enum StockMovementType: string
{
    case RESTOCK = 'restock';
    case SALE = 'sale';
    case RESERVED = 'reserved';
    case DAMAGED = 'damaged';
    case RETURN = 'return';
}