@php
    $categories = get_property_categories([
        'indent' => '↳',
        'conditions' => ['status' => \Botble\Base\Enums\BaseStatusEnum::PUBLISHED],
    ]);
@endphp

{!! Theme::partial('project-carousel', [
    'categories' => $categories,
    'projects' => $projects,
    'title' => $title ?? __('Descubre nuestros proyectos'),
    'description' => $description ?? theme_option('home_project_description'),
    'ajaxUrl' => $ajaxUrl ?? route('public.projects'),
    'actionUrl' => $actionUrl ?? RealEstateHelper::getProjectsListPageUrl(),
]) !!}
