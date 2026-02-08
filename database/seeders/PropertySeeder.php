<?php

namespace Database\Seeders;

use Botble\Base\Supports\BaseSeeder;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Models\Property;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PropertySeeder extends BaseSeeder
{
    public function run(): void
    {
        $this->uploadFiles('properties');

        Property::query()->update(['expire_date' => Carbon::now()->addDays(RealEstateHelper::propertyExpiredDays())]);

        DB::statement('UPDATE re_properties SET views = FLOOR(rand() * 10000) + 1;');
    }
}
