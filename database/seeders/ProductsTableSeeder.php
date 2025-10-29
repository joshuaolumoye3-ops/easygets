<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Provider;

class ProductsTableSeeder extends Seeder
{
    public function run(): void
    {
        $otpService = Provider::where('api_identifier', 'otp')->first();

        if ($otpService) {
            $products = [
                [
                    'product_code'  => 'Otp-others',
                    'product_sno'   => 'NULL',
                    'product_id'    => 'NULL',
                    'name'          => 'Otp-Price',
                    'api_price'     => 0.00,
                    'client_price'  => 160.00,
                    'is_active'     => true,
                ],
            ];

            foreach ($products as $product) {
                Product::updateOrCreate(
                    [
                        'provider_id'  => $otpService->id,
                        'product_code' => $product['product_code'],
                    ],
                    $product
                );
            }
        }
    }
}
