<?php

namespace Database\Seeders;

use Botble\Base\Supports\BaseSeeder;
use Botble\RealEstate\Models\Category;
use Botble\RealEstate\Models\Project;
use Botble\RealEstate\Models\Property;
use Botble\Slug\Facades\SlugHelper;

class CategorySeeder extends BaseSeeder
{
    public function run(): void
    {
        Category::query()->truncate();

        $categories = [
            [
                'name' => 'Apartment',
                'is_default' => true,
                'order' => 0,
            ],
            [
                'name' => 'Villa',
                'is_default' => false,
                'order' => 1,
            ],
            [
                'name' => 'Condo',
                'is_default' => false,
                'order' => 2,
            ],
            [
                'name' => 'House',
                'is_default' => false,
                'order' => 3,
            ],
            [
                'name' => 'Land',
                'is_default' => false,
                'order' => 4,
            ],
            [
                'name' => 'Commercial property',
                'is_default' => false,
                'order' => 5,
            ],
        ];

        Category::query()->truncate();

        foreach ($categories as $item) {
            $category = Category::query()->create($item);

            SlugHelper::createSlug($category);
        }

        $properties = Property::query()->get();

        foreach ($properties as $property) {
            $property->categories()->sync([Category::query()->inRandomOrder()->value('id')]);
            $property->save();
        }

        $projects = Project::query()->get();

        foreach ($projects as $project) {
            $project->categories()->sync([Category::query()->inRandomOrder()->value('id')]);
            $project->save();
        }
    }
}
