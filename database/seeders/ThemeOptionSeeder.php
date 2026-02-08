<?php

namespace Database\Seeders;

use Botble\Base\Supports\BaseSeeder;
use Botble\Page\Models\Page;
use Botble\Theme\Database\Traits\HasThemeOptionSeeder;
use Carbon\Carbon;

class ThemeOptionSeeder extends BaseSeeder
{
    use HasThemeOptionSeeder;

    public function run(): void
    {
        $this->uploadFiles('general');

        $this->createThemeOptions([
            'site_title' => 'Flex Home',
            'seo_description' => 'Find your favorite homes at Flex Home',
            'copyright' => sprintf('©%s Flex Home is Proudly Powered by Botble Team.', Carbon::now()->year),
            'favicon' => 'logo/favicon.png',
            'logo' => 'logo/logo.png',
            'cookie_consent_message' => 'Your experience on this site will be improved by allowing cookies ',
            'cookie_consent_learn_more_url' => '/cookie-policy',
            'cookie_consent_learn_more_text' => 'Cookie Policy',
            'homepage_id' => Page::query()->value('id'),
            'blog_page_id' => Page::query()->skip(1)->value('id'),
            'properties_list_page_id' => Page::query()->skip(6)->value('id'),
            'projects_list_page_id' => Page::query()->skip(7)->value('id'),
            'home_banner' => 'general/home-banner.jpg',
            'breadcrumb_background' => 'general/breadcrumb-background.jpg',
            'address' => 'North Link Building, 10 Admiralty Street, 757695 Singapore',
            'hotline' => '18006268',
            'email' => 'sale@botble.com',
            'social_links' => [
                [
                    [
                        'key' => 'social-name',
                        'value' => 'Facebook',
                    ],
                    [
                        'key' => 'social-icon',
                        'value' => 'fab fa-facebook',
                    ],
                    [
                        'key' => 'social-url',
                        'value' => 'https://facebook.com',
                    ],
                ],
                [
                    [
                        'key' => 'social-name',
                        'value' => 'Twitter',
                    ],
                    [
                        'key' => 'social-icon',
                        'value' => 'fab fa-twitter',
                    ],
                    [
                        'key' => 'social-url',
                        'value' => 'https://twitter.com',
                    ],
                ],
                [
                    [
                        'key' => 'social-name',
                        'value' => 'Youtube',
                    ],
                    [
                        'key' => 'social-icon',
                        'value' => 'fab fa-youtube',
                    ],
                    [
                        'key' => 'social-url',
                        'value' => 'https://youtube.com',
                    ],
                ],
            ],
        ]);
    }
}
