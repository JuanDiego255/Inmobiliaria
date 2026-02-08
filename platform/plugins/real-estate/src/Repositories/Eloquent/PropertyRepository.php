<?php

namespace Botble\RealEstate\Repositories\Eloquent;

use Botble\Base\Models\BaseQueryBuilder;
use Botble\Language\Facades\Language;
use Botble\RealEstate\Enums\ModerationStatusEnum;
use Botble\RealEstate\Enums\PropertyTypeEnum;
use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Repositories\Interfaces\PropertyInterface;
use Botble\Support\Repositories\Eloquent\RepositoriesAbstract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class PropertyRepository extends RepositoriesAbstract implements PropertyInterface
{
    public function getRelatedProperties(int $propertyId, int $limit = 4, array $with = [], array $extra = []): Collection|LengthAwarePaginator
    {
        $limit = $limit > 1 ? $limit : 4;
        $currentProperty = $this->findById($propertyId, ['categories']);

        $this->model = $this->originalModel;

        // @phpstan-ignore-next-line
        $this->model = $this->model
            ->where('id', '<>', $propertyId)
            ->active();

        if ($currentProperty && $currentProperty->categories->count()) {
            $categoryIds = $currentProperty->categories->pluck('id')->toArray();

            $this->model
                ->whereHas('categories', function ($query) use ($categoryIds) {
                    $query->whereIn('category_id', $categoryIds);
                })
                ->where('type', $currentProperty->type);
        }

        $params = array_merge([
            'condition' => [],
            'order_by' => [
                'created_at' => 'DESC',
            ],
            'take' => $limit,
            'with' => $with,
        ], $extra);

        return $this->advancedGet($params);
    }

    public function getProperties(array $filters = [], array $params = []): Collection|LengthAwarePaginator
    {
        $filters = array_merge([
            'keyword' => null,
            'type' => null,
            'bedroom' => null,
            'bathroom' => null,
            'floor' => null,
            'min_square' => null,
            'max_square' => null,
            'min_price' => null,
            'max_price' => null,
            'project' => null,
            'project_id' => null,
            'category_id' => null,
            'city_id' => null,
            'city' => null,
            'state' => null,
            'state_id' => null,
            'location' => null,
            'sort_by' => null,
        ], $filters);

        $orderBy = match ($filters['sort_by']) {
            'date_asc' => [
                'created_at' => 'ASC',
            ],
            'price_asc' => [
                'price' => 'ASC',
            ],
            'price_desc' => [
                'price' => 'DESC',
            ],
            'name_asc' => [
                'name' => 'ASC',
            ],
            'name_desc' => [
                'name' => 'DESC',
            ],
            default => [
                'created_at' => 'DESC',
            ],
        };

        $params = array_merge([
            'condition' => [],
            'order_by' => [
                'created_at' => 'DESC',
            ],
            'take' => null,
            'paginate' => [
                'per_page' => 10,
                'current_paged' => 1,
            ],
            'select' => [
                '*',
            ],
            'with' => [],
        ], $params);

        $params['order_by'] = $orderBy;

        // @phpstan-ignore-next-line
        $this->model = $this->originalModel->active();

        if ($filters['keyword'] !== null) {
            $keyword = $filters['keyword'];

            if (is_plugin_active('language') && is_plugin_active('language-advanced') && Language::getCurrentLocale() != Language::getDefaultLocale()) {
                $this->model = $this->model
                    ->whereHas('translations', function (BaseQueryBuilder $query) use ($keyword) {
                        $query
                            ->addSearch('name', $keyword, false, false)
                            ->addSearch('location', $keyword, false)
                            ->addSearch('description', $keyword, false)
                            ->addSearch('unique_id', $keyword, false);
                    });
            } else {
                $this->model = $this->model
                    ->where(function (BaseQueryBuilder $query) use ($keyword) {
                        return $query
                            ->addSearch('name', $keyword, false, false)
                            ->addSearch('location', $keyword, false)
                            ->addSearch('description', $keyword, false)
                            ->addSearch('unique_id', $keyword, false);
                    });
            }
        }

        if ($filters['type'] !== null) {
            if ($filters['type'] == PropertyTypeEnum::SALE) {
                $this->model = $this->model->where('type', $filters['type']);
            } else {
                $this->model = $this->model->where('type', $filters['type']);
            }
        }

        if ($filters['bedroom']) {
            if ($filters['bedroom'] < 5) {
                $this->model = $this->model->where('number_bedroom', $filters['bedroom']);
            } else {
                $this->model = $this->model->where('number_bedroom', '>=', $filters['bedroom']);
            }
        }

        if ($filters['bathroom']) {
            if ($filters['bathroom'] < 5) {
                $this->model = $this->model->where('number_bathroom', $filters['bathroom']);
            } else {
                $this->model = $this->model->where('number_bathroom', '>=', $filters['bathroom']);
            }
        }

        if ($filters['floor']) {
            if ($filters['floor'] < 5) {
                $this->model = $this->model->where('number_floor', $filters['floor']);
            } else {
                $this->model = $this->model->where('number_floor', '>=', $filters['floor']);
            }
        }

        if ($filters['min_square'] !== null || $filters['max_square'] !== null) {
            $this->model = $this->model
                ->where(function (Builder $query) use ($filters) {
                    $minSquare = Arr::get($filters, 'min_square');
                    $maxSquare = Arr::get($filters, 'max_square');

                    if ($minSquare !== null) {
                        $query = $query->where('square', '>=', $minSquare);
                    }

                    if ($maxSquare !== null) {
                        $query = $query->where('square', '<=', $maxSquare);
                    }

                    return $query;
                });
        }

        if ($filters['min_price'] !== null || $filters['max_price'] !== null) {
            $this->model = $this->model
                ->where(function (Builder $query) use ($filters) {
                    $minPrice = Arr::get($filters, 'min_price');
                    $maxPrice = Arr::get($filters, 'max_price');

                    if ($minPrice !== null) {
                        $query = $query->where('price', '>=', $minPrice);
                    }

                    if ($maxPrice !== null) {
                        $query = $query->where('price', '<=', $maxPrice);
                    }

                    return $query;
                });
        }

        if ($filters['city'] !== null) {
            $this->model = $this->model->whereHas('city', function ($query) use ($filters) {
                $query->where('slug', $filters['city']);
            });
        }

        if ($filters['state'] !== null) {
            $this->model = $this->model->whereHas('state', function ($query) use ($filters) {
                $query->where('slug', $filters['state']);
            });
        }

        if ($filters['project'] !== null) {
            $this->model = $this->model->where(function (BaseQueryBuilder $query) use ($filters) {
                $query
                    ->where('project_id', $filters['project'])
                    ->orWhereHas('project', function (BaseQueryBuilder $query) use ($filters) {
                        $query->addSearch('re_projects.name', $filters['project'], false, false);
                    });
            });
        }

        if ($filters['project_id'] !== null) {
            $this->model = $this->model->where('project_id', $filters['project_id']);
        }

        if ($filters['category_id'] !== null) {
            $categoryIds = get_property_categories_related_ids($filters['category_id']);
            $this->model = $this->model
                ->whereHas('categories', function ($query) use ($categoryIds) {
                    $query->whereIn('category_id', $categoryIds);
                });
        }

        if ($filters['state_id']) {
            $this->model = $this->model->where('state_id', $filters['state_id']);
        }

        if ($filters['city_id']) {
            $this->model = $this->model->where('city_id', $filters['city_id']);
        } elseif ($filters['location']) {
            $locationData = explode(',', $filters['location']);

            if (count($locationData) > 1) {
                $locationSearch = trim($locationData[0]);
            } else {
                $locationSearch = trim($filters['location']);
            }

            if (is_plugin_active('language') && is_plugin_active('language-advanced') && Language::getCurrentLocale() != Language::getDefaultLocale()) {
                $this->model = $this->model
                    ->where(function (BaseQueryBuilder $query) use ($locationSearch) {
                        return $query
                            ->whereHas('translations', function (BaseQueryBuilder $query) use ($locationSearch) {
                                $query->addSearch('location', $locationSearch, false, false);
                            })
                            ->orWhereHas('city.translations', function (BaseQueryBuilder $query) use ($locationSearch) {
                                $query->addSearch('name', $locationSearch, false, false);
                            })
                            ->orWhereHas('state.translations', function (BaseQueryBuilder $query) use ($locationSearch) {
                                $query->addSearch('name', $locationSearch, false, false);
                            });
                    });
            } else {
                $this->model = $this->model
                    ->where(function ($query) use ($locationSearch) {
                        return $query
                            ->addSearch('location', $locationSearch, false, false)
                            ->orWhereHas('city', function (BaseQueryBuilder $query) use ($locationSearch) {
                                $query->addSearch('cities.name', $locationSearch, false, false);
                            })
                            ->orWhereHas('state', function (BaseQueryBuilder $query) use ($locationSearch) {
                                $query->addSearch('states.name', $locationSearch, false, false);
                            });
                    });
            }
        }

        if (count($filters['category_ids'] ?? [])) {
            $categoryIds = $filters['category_ids'];

            $this->model = $this->model
                ->whereHas('categories', function (Builder $query) use ($categoryIds) {
                    $query->whereIn('category_id', $categoryIds);
                });
        }

        if ($filters['locations'] ?? []) {
            $locationsSearch = $filters['locations'];

            if (is_plugin_active('language') && is_plugin_active('language-advanced') && Language::getCurrentLocale() != Language::getDefaultLocale()) {
                $this->model = $this->model
                    ->where(function (BaseQueryBuilder $query) use ($locationsSearch) {
                        return $query
                            ->whereHas('translations', function (BaseQueryBuilder $query) use ($locationsSearch) {
                                foreach ($locationsSearch as $location) {
                                    $query->addSearch('location', $location, false);
                                }
                            })
                            ->orWhereHas('city.translations', function (BaseQueryBuilder $query) use ($locationsSearch) {
                                foreach ($locationsSearch as $location) {
                                    $query->addSearch('name', $location, false);
                                }
                            })
                            ->orWhereHas('state.translations', function (BaseQueryBuilder $query) use ($locationsSearch) {
                                foreach ($locationsSearch as $location) {
                                    $query->addSearch('name', $location, false);
                                }
                            });
                    });
            } else {
                $this->model = $this->model
                    ->where(function (BaseQueryBuilder $query) use ($locationsSearch) {
                        return $query
                            ->where(function (BaseQueryBuilder $query) use ($locationsSearch) {
                                foreach ($locationsSearch as $location) {
                                    $query->addSearch('location', $location, false);
                                }
                            })
                            ->orWhereHas('city', function (BaseQueryBuilder $query) use ($locationsSearch) {
                                foreach ($locationsSearch as $location) {
                                    $query->addSearch('cities.name', $location, false);
                                }
                            })
                            ->orWhereHas('state', function (BaseQueryBuilder $query) use ($locationsSearch) {
                                foreach ($locationsSearch as $location) {
                                    $query->addSearch('states.name', $location, false);
                                }
                            });
                    });
            }
        }

        $this->model = apply_filters('properties_filter_query', $this->model, $filters, $params);

        return $this->advancedGet($params);
    }

    public function getProperty(int $propertyId, array $with = [], array $extra = []): ?Property
    {
        $params = array_merge([
            'condition' => [
                'id' => $propertyId,
                'moderation_status' => ModerationStatusEnum::APPROVED,
            ],
            'with' => $with,
            'take' => 1,
        ], $extra);

        // @phpstan-ignore-next-line
        $this->model = $this->originalModel->notExpired();

        return $this->advancedGet($params);
    }

    public function getPropertiesByConditions(array $condition, int $limit = 4, array $with = []): Collection|LengthAwarePaginator
    {
        $limit = $limit > 1 ? $limit : 4;

        // @phpstan-ignore-next-line
        $this->model = $this->originalModel->active();

        $params = [
            'condition' => $condition,
            'with' => $with,
            'take' => $limit,
            'order_by' => ['created_at' => 'DESC'],
        ];

        return $this->advancedGet($params);
    }
}
