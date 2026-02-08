<?php

use Botble\Widget\AbstractWidget;
use Illuminate\Support\Collection;

class CategoriesWidget extends AbstractWidget
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'Categories',
            'description' => __('Display list of categories'),
        ]);
    }

    public function data(): array|Collection
    {
        if (! is_plugin_active('blog')) {
            return [];
        }

        return [
            'categories' => get_categories(['select' => ['categories.id', 'categories.name']]),
        ];
    }
}
