<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'Visa Assistance',
                'description' => 'Documentation and visa processing support.',
                'price' => 2500,
            ],
            [
                'name' => 'Travel Insurance',
                'description' => 'Domestic and international travel insurance plans.',
                'price' => 1800,
            ],
            [
                'name' => 'Airport Transfer',
                'description' => 'Pickup and drop transport arrangements.',
                'price' => 3200,
            ],
        ];

        $createdProducts = collect($products)->map(function (array $product) {
            return Product::updateOrCreate(
                ['name' => $product['name']],
                $product
            );
        })->values();

        $services = [
            [
                'product_index' => 0,
                'service_name' => 'Tourist Visa Filing',
                'description' => 'End-to-end tourist visa filing and appointment coordination.',
                'service_price' => 3500,
                'status' => 'active',
            ],
            [
                'product_index' => 0,
                'service_name' => 'Express Visa Review',
                'description' => 'Priority document review for urgent visa applications.',
                'service_price' => 5200,
                'status' => 'active',
            ],
            [
                'product_index' => 1,
                'service_name' => 'Standard Travel Cover',
                'description' => 'Medical and baggage coverage for standard trips.',
                'service_price' => 1400,
                'status' => 'active',
            ],
            [
                'product_index' => 2,
                'service_name' => 'Private Sedan Transfer',
                'description' => 'Private airport pickup and drop for up to three passengers.',
                'service_price' => 2800,
                'status' => 'inactive',
            ],
            [
                'product_index' => 2,
                'service_name' => 'Luxury SUV Transfer',
                'description' => 'Premium airport transfer with additional luggage capacity.',
                'service_price' => 4800,
                'status' => 'active',
            ],
        ];

        foreach ($services as $service) {
            $product = $createdProducts[$service['product_index']] ?? null;

            Service::updateOrCreate(
                ['service_name' => $service['service_name']],
                [
                    'product_id' => $product?->id,
                    'service_name' => $service['service_name'],
                    'description' => $service['description'],
                    'service_price' => $service['service_price'],
                    'status' => $service['status'],
                ]
            );
        }
    }
}
