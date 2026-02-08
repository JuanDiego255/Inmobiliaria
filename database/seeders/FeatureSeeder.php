<?php

namespace Database\Seeders;

use Botble\Base\Supports\BaseSeeder;
use Botble\RealEstate\Models\Feature;
use Botble\RealEstate\Models\Property;
use Illuminate\Support\Facades\DB;

class FeatureSeeder extends BaseSeeder
{
    public function run(): void
    {
        Feature::query()->truncate();
        DB::table('re_project_features')->truncate();
        DB::table('re_property_features')->truncate();

        $features = [
            [
                'name' => 'Wifi',
            ],
            [
                'name' => 'Parking',
            ],
            [
                'name' => 'Swimming pool',
            ],
            [
                'name' => 'Balcony',
            ],
            [
                'name' => 'Garden',
            ],
            [
                'name' => 'Security',
            ],
            [
                'name' => 'Fitness center',
            ],
            [
                'name' => 'Air Conditioning',
            ],
            [
                'name' => 'Central Heating  ',
            ],
            [
                'name' => 'Laundry Room',
            ],
            [
                'name' => 'Pets Allow',
            ],
            [
                'name' => 'Spa & Massage',
            ],
        ];

        foreach ($features as $facility) {
            Feature::query()->create($facility);
        }

        foreach (Property::query()->get() as $property) {
            $property->features()->detach();
            $property->features()->attach([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]);
        }
    }
}
