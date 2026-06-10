{!! Theme::partial('short-codes.projects-list', [
    'title' => __('Descubre nuestros proyectos'),
    'description' => theme_option('home_project_description'),
    'projects' => $projects,
    'ajaxUrl' => $ajaxUrl ?? null,
    'actionUrl' => $actionUrl ?? null,
]) !!}
