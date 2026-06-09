@php
    $categories = get_property_categories([
        'indent' => '↳',
        'conditions' => ['status' => \Botble\Base\Enums\BaseStatusEnum::PUBLISHED],
    ]);
@endphp

{!! Theme::partial('property-carousel', [
    'categories' => $categories,
    'ajaxUrl' => $ajaxUrl ?? route('public.properties'),
    'actionUrl' => $actionUrl ?? RealEstateHelper::getPropertiesListPageUrl(),
]) !!}
