@php
    $categories = get_property_categories([
        'indent' => '↳',
        'conditions' => ['status' => \Botble\Base\Enums\BaseStatusEnum::PUBLISHED],
    ]);
@endphp

{{-- Hero + Featured Properties Carousel --}}
{!! Theme::partial('property-carousel') !!}

{{-- Properties Listing --}}
<section class="main-homes pb-3">
    <div class="container-fluid w90 padtop30">
        <div class="projecthome">
            <form
                id="ajax-filters-form"
                data-ajax-url="{{ $ajaxUrl ?? route('public.properties') }}"
                action="{{ $actionUrl ?? RealEstateHelper::getPropertiesListPageUrl() }}"
                method="get"
            >
                {!! apply_filters(
                    'properties_projects_detail_search_box',
                    view(Theme::getThemeNamespace() . '::views.real-estate.includes.search-box', [
                        'type' => 'property',
                        'categories' => $categories,
                    ])->render(),
                    ['type' => 'property', 'categories' => $categories],
                ) !!}
                <div class="row rowm10">
                    <div class="col-lg-12 full-page-content" id="properties-list">
                        @include(Theme::getThemeNamespace() . '::views.real-estate.includes.filters', [
                            'isChangeView' => false,
                        ])
                        <div class="data-listing mt-2">
                            {!! Theme::partial('real-estate.properties.items', compact('properties')) !!}
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
