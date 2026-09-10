<?php

namespace Database\Seeders;

use App\Models\CompanyProfile;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolesAndUsersSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Super Admin
        User::create([
            'name' => 'TradeCore Admin',
            'email' => 'admin@tradecore.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // 2. Vendor 1: Apex Industrial Supplies
        $vendorUser1 = User::create([
            'name' => 'Tariq Mehmood',
            'email' => 'vendor1@tradecore.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        Vendor::create([
            'user_id' => $vendorUser1->id,
            'store_name' => 'Apex Industrial Tools',
            'slug' => 'apex-industrial-tools',
            'business_email' => 'sales@apexind.test',
            'business_phone' => '+923001234567',
            'commission_rate' => 5.00,
            'wallet_balance' => 0.00,
            'status' => 'approved',
        ]);

        // 3. Vendor 2: Nova Electronics Wholesale
        $vendorUser2 = User::create([
            'name' => 'Zubair Khan',
            'email' => 'vendor2@tradecore.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        Vendor::create([
            'user_id' => $vendorUser2->id,
            'store_name' => 'Nova Electronics Hub',
            'slug' => 'nova-electronics-hub',
            'business_email' => 'b2b@novaelectric.test',
            'business_phone' => '+923007654321',
            'commission_rate' => 4.50,
            'wallet_balance' => 0.00,
            'status' => 'approved',
        ]);

        // 4. B2B Corporate Buyer
        $buyer = User::create([
            'name' => 'Crescent Tech Solutions',
            'email' => 'buyer@tradecore.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        CompanyProfile::create([
            'user_id' => $buyer->id,
            'company_name' => 'Crescent Technologies Pvt Ltd',
            'tax_number' => 'NTN-8934211-7',
            'credit_limit' => 500000.00,
            'available_credit' => 500000.00,
            'billing_address' => 'Floor 4, Software Tech Park, F-3, Islamabad',
            'shipping_address' => 'Plot 12, Industrial Area, Sector I-9, Islamabad',
        ]);
    }
}