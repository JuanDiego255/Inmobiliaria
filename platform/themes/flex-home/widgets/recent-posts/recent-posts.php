<?php

use Botble\Widget\AbstractWidget;
use Illuminate\Support\Collection;

class RecentPostsWidget extends AbstractWidget
{
    public function __construct()
    {
        parent::__construct([
            'name' => __('Recent posts'),
            'description' => __('Recent posts widget.'),
            'number_display' => 5,
        ]);
    }

    public function data(): array|Collection
    {
        if (! is_plugin_active('blog')) {
            return [];
        }

        return [
            'posts' => get_recent_posts($this->getConfig('number_display')),
        ];
    }
}
