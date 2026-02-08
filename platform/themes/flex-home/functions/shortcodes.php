<?php

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\Html;
use Botble\Base\Supports\RepositoryHelper;
use Botble\Blog\Repositories\Interfaces\PostInterface;
use Botble\Location\Models\City;
use Botble\Location\Models\State;
use Botble\RealEstate\Enums\PropertyTypeEnum;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Models\Account;
use Botble\RealEstate\Repositories\Interfaces\ProjectInterface;
use Botble\RealEstate\Repositories\Interfaces\PropertyInterface;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Shortcode\Compilers\Shortcode;
use Botble\Theme\Facades\Theme;
use Botble\Theme\Supports\ThemeSupport;
use Illuminate\Support\Facades\App;

app()->booted(function () {
    ThemeSupport::registerGoogleMapsShortcode();
    ThemeSupport::registerYoutubeShortcode();

    if (is_plugin_active('real-estate')) {
        add_shortcode('featured-projects', __('Featured projects'), __('Featured projects'), function (Shortcode $shortcode) {
            $projects = collect();

            if (is_plugin_active('real-estate')) {
                $projects = app(ProjectInterface::class)->advancedGet([
                    'condition' => [
                            're_projects.is_featured' => true,
                        ] + RealEstateHelper::getProjectDisplayQueryConditions(),
                    'take' => (int) $shortcode->limit ?: (int)theme_option('number_of_featured_projects', 4),
                    'with' => RealEstateHelper::getProjectRelationsQuery(),
                    'order_by' => ['re_projects.created_at' => 'DESC'],
                ]);
            }

            if ($projects->isEmpty()) {
                return null;
            }

            return Theme::partial('short-codes.featured-projects', [
                'title' => $shortcode->title,
                'subtitle' => $shortcode->subtitle,
                'projects' => $projects,
            ]);
        });

        shortcode()->setAdminConfig('featured-projects', function (array $attributes) {
            return Theme::partial('short-codes.featured-projects-admin-config', compact('attributes'));
        });

        add_shortcode('projects-by-locations', __('Projects by locations'), __('Projects by locations'), function (Shortcode $shortcode) {
            $cityIds = array_filter(explode(',', $shortcode->city));
            $stateIds = array_filter(explode(',', $shortcode->state));

            if (empty($cityIds) && empty($stateIds)) {
                return null;
            }

            $cities = collect();
            $states = collect();

            if (! empty($cityIds)) {
                $cities = City::query()
                    ->whereIn('id', $cityIds)
                    ->wherePublished()
                    ->select(['id', 'name', 'image', 'slug'])
                    ->orderBy('order')
                    ->orderBy('name')
                    ->get();

                $cities->transform(function (City $city) {
                    $city->setAttribute('url', route('public.projects-by-city', $city->slug));

                    return $city;
                });
            }

            if (! empty($stateIds)) {
                $states = State::query()
                    ->whereIn('id', $stateIds)
                    ->wherePublished()
                    ->select(['id', 'name', 'image', 'slug'])
                    ->orderBy('order')
                    ->orderBy('name')
                    ->get();

                $states->transform(function (State $state) {
                    $state->setAttribute('url', route('public.projects-by-state', $state->slug));

                    return $state;
                });
            }

            $locations = $cities->merge($states);

            if ($locations->isEmpty()) {
                return null;
            }

            return Theme::partial('short-codes.projects-by-locations', [
                'title' => $shortcode->title,
                'subtitle' => $shortcode->subtitle,
                'locations' => $locations,
            ]);
        });

        shortcode()->setAdminConfig('projects-by-locations', function (array $attributes) {
            $cities = City::query()
                ->wherePublished()
                ->pluck('name', 'id');

            $states = State::query()
                ->wherePublished()
                ->pluck('name', 'id');

            return Html::style('vendor/core/core/base/libraries/tagify/tagify.css') .
                Html::script('vendor/core/core/base/libraries/tagify/tagify.js') .
                Html::script('vendor/core/core/base/js/tags.js') .
                Theme::partial('short-codes.projects-by-locations-admin-config', compact('attributes', 'cities', 'states'));
        });

        add_shortcode(
            'properties-by-locations',
            __('Properties by locations'),
            __('Properties by locations'),
            function ($shortcode) {
                $cityIds = array_filter(explode(',', (string)$shortcode->city));
                $stateIds = array_filter(explode(',', (string)$shortcode->state));

                if (empty($cityIds) && empty($stateIds)) {
                    return null;
                }

                $cities = collect();
                $states = collect();

                if (! empty($cityIds)) {
                    $cities = City::query()
                        ->whereIn('id', $cityIds)
                        ->wherePublished()
                        ->select(['id', 'name', 'image', 'slug'])
                        ->orderBy('order')
                        ->orderBy('name')
                        ->get();

                    $cities->transform(function (City $city) {
                        $city->setAttribute('url', route('public.properties-by-city', $city->slug));

                        return $city;
                    });
                }

                if (! empty($stateIds)) {
                    $states = State::query()
                        ->whereIn('id', $stateIds)
                        ->wherePublished()
                        ->select(['id', 'name', 'image', 'slug'])
                        ->orderBy('order')
                        ->orderBy('name')
                        ->get();

                    $states->transform(function (State $state) {
                        $state->setAttribute('url', route('public.properties-by-state', $state->slug));

                        return $state;
                    });
                }

                $locations = $cities->merge($states);

                if ($locations->isEmpty()) {
                    return null;
                }

                return Theme::partial('short-codes.properties-by-locations', [
                    'title' => $shortcode->title,
                    'subtitle' => $shortcode->subtitle,
                    'locations' => $locations,
                ]);
            }
        );

        shortcode()->setAdminConfig('properties-by-locations', function (array $attributes) {
            $cities = City::query()
                ->wherePublished()
                ->pluck('name', 'id');

            $states = State::query()
                ->wherePublished()
                ->pluck('name', 'id');

            return Html::style('vendor/core/core/base/libraries/tagify/tagify.css') .
                Html::script('vendor/core/core/base/libraries/tagify/tagify.js') .
                Html::script('vendor/core/core/base/js/tags.js') .
                Theme::partial('short-codes.properties-by-locations-admin-config', compact('attributes', 'cities', 'states'));
        });

        add_shortcode('featured-properties', __('Featured properties'), __('Featured properties'), function (Shortcode $shortcode) {
            $properties = app(PropertyInterface::class)->getPropertiesByConditions(
                [
                    're_properties.is_featured' => true,
                ],
                (int)$shortcode->limit ?: 8,
                RealEstateHelper::getPropertyRelationsQuery()
            );

            if ($properties->isEmpty()) {
                return null;
            }

            return Theme::partial('short-codes.featured-properties', [
                'title' => $shortcode->title,
                'subtitle' => $shortcode->subtitle,
                'properties' => $properties,
            ]);
        });

        shortcode()->setAdminConfig('featured-properties', function (array $attributes) {
            return Theme::partial('short-codes.featured-properties-admin-config', compact('attributes'));
        });

        add_shortcode('properties-for-sale', __('Properties for sale'), __('Properties for sale'), function (Shortcode $shortcode) {
            $conditions = [
                're_properties.type' => PropertyTypeEnum::SALE,
            ];

            if (($shortcode->featured ?: 1) == 1) {
                $conditions['re_properties.is_featured'] = true;
            }

            $properties = app(PropertyInterface::class)->getPropertiesByConditions(
                $conditions,
                (int)$shortcode->limit ?: 8,
                RealEstateHelper::getPropertyRelationsQuery()
            );

            if ($properties->isEmpty()) {
                return null;
            }

            return Theme::partial('short-codes.properties-for-sale', [
                'title' => $shortcode->title,
                'subtitle' => $shortcode->subtitle,
                'properties' => $properties,
            ]);
        });

        shortcode()->setAdminConfig('properties-for-sale', function (array $attributes) {
            return Theme::partial('short-codes.properties-for-sale-admin-config', compact('attributes'));
        });

        add_shortcode('properties-for-rent', __('Properties for rent'), __('Properties for rent'), function (Shortcode $shortcode) {
            $conditions = [
                're_properties.type' => PropertyTypeEnum::RENT,
            ];

            if (($shortcode->featured ?: 1) == 1) {
                $conditions['re_properties.is_featured'] = true;
            }

            $properties = app(PropertyInterface::class)->getPropertiesByConditions(
                $conditions,
                (int)$shortcode->limit ?: 8,
                RealEstateHelper::getPropertyRelationsQuery()
            );

            if ($properties->isEmpty()) {
                return null;
            }

            return Theme::partial('short-codes.properties-for-rent', [
                'title' => $shortcode->title,
                'subtitle' => $shortcode->subtitle,
                'properties' => $properties,
            ]);
        });

        shortcode()->setAdminConfig('properties-for-rent', function (array $attributes) {
            return Theme::partial('short-codes.properties-for-rent-admin-config', compact('attributes'));
        });

        add_shortcode(
            'recently-viewed-properties',
            __('Recent Viewed Properties'),
            __('Recently Viewed Properties'),
            function ($shortcode) {
                $cookieName = App::getLocale() . '_recently_viewed_properties';
                $jsonRecentViewProduct = null;

                if (isset($_COOKIE[$cookieName])) {
                    $jsonRecentViewProduct = $_COOKIE[$cookieName];
                }

                if (empty($jsonRecentViewProduct)) {
                    return null;
                }

                $ids = collect(json_decode($jsonRecentViewProduct, true))->flatten()->all();

                $properties = app(PropertyInterface::class)->getPropertiesByConditions(
                    [
                        ['re_properties.id', 'IN', $ids],
                    ],
                    (int)$shortcode->limit ?: 8,
                    RealEstateHelper::getPropertyRelationsQuery()
                );

                if ($properties->isEmpty()) {
                    return null;
                }

                $reversed = array_reverse($ids);

                $properties = $properties->sortBy(function ($model) use ($reversed) {
                    return array_search($model->id, $reversed);
                });

                return Theme::partial('short-codes.recently-viewed-properties', [
                    'title' => $shortcode->title,
                    'description' => $shortcode->description,
                    'subtitle' => $shortcode->subtitle,
                    'properties' => $properties,
                ]);
            }
        );

        shortcode()->setAdminConfig('recently-viewed-properties', function (array $attributes) {
            return Theme::partial('short-codes.recently-viewed-properties-admin-config', compact('attributes'));
        });

        add_shortcode(
            'featured-agents',
            __('Featured Agents'),
            __('Featured Agents'),
            function (Shortcode $shortcode) {
                $agents = Account::query()
                    ->where('is_featured', true)
                    ->orderByDesc('id')
                    ->take((int)$shortcode->limit ?: 4)
                    ->withCount([
                        'properties' => function ($query) {
                            return RepositoryHelper::applyBeforeExecuteQuery($query, $query->getModel());
                        },
                    ])
                    ->with(['avatar'])
                    ->get();

                if ($agents->isEmpty()) {
                    return null;
                }

                return Theme::partial('short-codes.featured-agents', [
                    'title' => $shortcode->title,
                    'description' => $shortcode->description,
                    'subtitle' => $shortcode->subtitle,
                    'agents' => $agents,
                ]);
            }
        );

        shortcode()->setAdminConfig('featured-agents', function (array $attributes) {
            return Theme::partial('short-codes.featured-agents-admin-config', compact('attributes'));
        });

        add_shortcode(
            'search-box',
            __('Search box'),
            __('Search box'),
            function (Shortcode $shortcode) {
                return Theme::partial('short-codes.search-box', compact('shortcode'));
            }
        );

        shortcode()->setAdminConfig('search-box', function (array $attributes) {
            return Theme::partial('short-codes.search-box-admin-config', compact('attributes'));
        });

        add_shortcode('properties-list', __('Properties List'), __('Properties List'), function (Shortcode $shortcode) {
            $properties = RealEstateHelper::getPropertiesFilter((int)$shortcode->per_page ?: 12);

            $keyword = BaseHelper::stringify(request()->input('k'));

            if (! empty($keyword)) {
                SeoHelper::setTitle(__('Search results for ":keyword"', ['keyword' => $keyword]));
            }

            return Theme::partial('short-codes.properties-list', [
                'title' => $shortcode->title,
                'description' => $shortcode->description,
                'properties' => $properties,
            ]);
        });

        shortcode()->setAdminConfig('properties-list', function (array $attributes) {
            return Theme::partial('short-codes.properties-list-admin-config', compact('attributes'));
        });

        add_shortcode('projects-list', __('Projects List'), __('Projects List'), function (Shortcode $shortcode) {
            $projects = RealEstateHelper::getProjectsFilter((int)$shortcode->per_page ?: 12);

            $keyword = BaseHelper::stringify(request()->input('k'));

            if (! empty($keyword)) {
                SeoHelper::setTitle(__('Search results for ":keyword"', ['keyword' => $keyword]));
            }

            return Theme::partial('short-codes.projects-list', [
                'title' => $shortcode->title,
                'description' => $shortcode->description,
                'projects' => $projects,
            ]);
        });

        shortcode()->setAdminConfig('projects-list', function (array $attributes) {
            return Theme::partial('short-codes.properties-list-admin-config', compact('attributes'));
        });
    }

    if (is_plugin_active('blog')) {
        add_shortcode('latest-news', __('Latest news'), __('Latest news'), function (Shortcode $shortcode) {
            $posts = app(PostInterface::class)
                ->getFeatured((int)$shortcode->limit ?: 4, ['slugable', 'categories', 'categories.slugable']);

            if ($posts->isEmpty()) {
                return null;
            }

            return Theme::partial('short-codes.latest-news', [
                'title' => $shortcode->title,
                'subtitle' => $shortcode->subtitle,
                'posts' => $posts,
            ]);
        });

        shortcode()->setAdminConfig('latest-news', function (array $attributes) {
            return Theme::partial('short-codes.latest-news-admin-config', compact('attributes'));
        });
    }

    if (is_plugin_active('contact')) {
        add_filter(CONTACT_FORM_TEMPLATE_VIEW, function () {
            return Theme::getThemeNamespace() . '::partials.short-codes.contact-form';
        }, 120);
    }
});
