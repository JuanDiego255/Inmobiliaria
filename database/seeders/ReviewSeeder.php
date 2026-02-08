<?php

namespace Database\Seeders;

use Botble\Base\Supports\BaseSeeder;
use Botble\RealEstate\Models\Account;
use Botble\RealEstate\Models\Project;
use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Models\Review;
use Carbon\Carbon;

class ReviewSeeder extends BaseSeeder
{
    public function run(): void
    {
        Review::query()->truncate();

        $accountsCount = Account::query()->count();
        $projectsCount = Project::query()->count();
        $propertiesCount = Property::query()->count();

        $faker = $this->fake();

        $now = Carbon::now();

        for ($i = 1; $i <= 200; $i++) {
            $reviewable = $faker->randomElement([
                ['id' => rand(1, $projectsCount), 'type' => Project::class],
                ['id' => rand(1, $propertiesCount), 'type' => Property::class],
            ]);

            Review::query()->insertOrIgnore([
                'id' => (new Review())->newUniqueId(),
                'account_id' => rand(1, $accountsCount),
                'reviewable_type' => $reviewable['type'],
                'reviewable_id' => $reviewable['id'],
                'content' => $faker->realText(rand(30, 300)),
                'star' => rand(1, 5),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
