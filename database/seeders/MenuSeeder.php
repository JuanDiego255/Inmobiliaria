<?php

namespace Database\Seeders;

use Botble\Base\Supports\BaseSeeder;
use Botble\Blog\Models\Category;
use Botble\Menu\Database\Traits\HasMenuSeeder;
use Botble\Page\Models\Page;

class MenuSeeder extends BaseSeeder
{
    use HasMenuSeeder;

    public function run(): void
    {
        $data = [
            [
                'name' => 'Main menu',
                'slug' => 'main-menu',
                'location' => 'main-menu',
                'items' => [
                    [
                        'title' => 'Projects',
                        'url' => '/projects',
                    ],
                    [
                        'title' => 'Properties',
                        'url' => '/properties',
                    ],
                    [
                        'title' => 'Agents',
                        'url' => '/agents',
                    ],
                    [
                        'title' => 'News',
                        'reference_id' => 2,
                        'reference_type' => Page::class,
                    ],
                    [
                        'title' => 'Careers',
                        'url' => '/careers',
                    ],
                    [
                        'title' => 'Contact',
                        'reference_id' => 4,
                        'reference_type' => Page::class,
                    ],
                ],
            ],
            [
                'name' => 'About',
                'slug' => 'about',
                'items' => [
                    [
                        'title' => 'About us',
                        'reference_id' => 3,
                        'reference_type' => Page::class,
                    ],
                    [
                        'title' => 'Contact us',
                        'reference_id' => 4,
                        'reference_type' => Page::class,
                    ],
                    [
                        'title' => 'Careers',
                        'url' => '/careers',
                    ],
                    [
                        'title' => 'Terms & Conditions',
                        'reference_id' => 5,
                        'reference_type' => Page::class,
                    ],
                ],
            ],
            [
                'name' => 'More information',
                'slug' => 'more-information',
                'items' => [
                    [
                        'title' => 'All projects',
                        'url' => '/projects',
                    ],
                    [
                        'title' => 'All properties',
                        'url' => '/properties',
                    ],
                    [
                        'title' => 'Houses for sale',
                        'url' => '/properties?type=sale',
                    ],
                    [
                        'title' => 'Houses for rent',
                        'url' => '/properties?type=rent',
                    ],
                ],
            ],
            [
                'name' => 'News',
                'slug' => 'news',
                'items' => [
                    [
                        'title' => 'Latest news',
                        'reference_id' => 2,
                        'reference_type' => Page::class,
                    ],
                    [
                        'title' => 'House architecture',
                        'reference_id' => 2,
                        'reference_type' => Category::class,
                    ],
                    [
                        'title' => 'House design',
                        'reference_id' => 4,
                        'reference_type' => Category::class,
                    ],
                    [
                        'title' => 'Building materials',
                        'reference_id' => 6,
                        'reference_type' => Category::class,
                    ],
                ],
            ],
        ];

        $this->createMenus($data);
    }
}
