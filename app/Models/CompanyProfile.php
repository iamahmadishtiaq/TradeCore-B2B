<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyProfile extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'tax_number',
        'credit_limit',
        'available_credit',
        'billing_address',
        'shipping_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}