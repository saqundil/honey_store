<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Seller;
use Illuminate\Database\Seeder;

class SellerSeeder extends Seeder
{
    public function run(): void
    {
        $seller = Seller::query()->updateOrCreate(
            ['email' => 'seller@honey-store.test'],
            [
                'name' => 'Default Honey Seller',
                'phone' => '+962775392581',
                'password' => 'password',
                'balance' => 0,
                'commission_rate' => 10,
            ],
        );

        Product::query()
            ->whereNull('seller_id')
            ->update(['seller_id' => $seller->id]);
    }
}