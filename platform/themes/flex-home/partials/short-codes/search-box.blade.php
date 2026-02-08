@php
    $categories = get_property_categories(['indent' => '↳', 'conditions' => ['status' => \Botble\Base\Enums\BaseStatusEnum::PUBLISHED]]);
    $backgroundImage = $shortcode->background_image ?: theme_option('home_banner');
    $enableProjectsSearch = $shortcode->enable_search_projects_on_homepage_search ? $shortcode->enable_search_projects_on_homepage_search == 'yes' : theme_option('enable_search_projects_on_homepage_search', 'yes') == 'yes';
    $defaultSearchType = $shortcode->default_home_search_type ?: theme_option('default_home_search_type', 'project');

    $tabs = [];
    $quantity = min((int) $shortcode->quantity, 20);
    if ($quantity) {
        for ($i = 1; $i <= $quantity; $i++) {
            $tabs[] = RvMedia::getImageUrl($shortcode->{'image_' . $i});
        }
    }
    $seconds = (int) $shortcode->seconds ?: 5;
    $enableChangeBackground = $shortcode->enable_change_image_background ?: 'no';
@endphp
<div
    class="home_banner"
    id="bannerSlider"
    data-images="{{ json_encode($tabs) }}"
    data-seconds="{{ $seconds }}"
    data-slide="{{ $enableChangeBackground }}"
    style="background-image: url({{ $backgroundImage ? RvMedia::getImageUrl($backgroundImage) : Theme::asset()->url('images/banner.jpg') }}); transition: background 3s"
>

    <div class="topsearch">
        @if (theme_option('home_banner_description'))
            <h1 class="text-center text-white mb-4 banner-text-description">
                {{ $shortcode->title ?: theme_option('home_banner_description') }}</h1>
        @endif
        <form
            id="frmhomesearch"
            @if ($enableProjectsSearch && $defaultSearchType == 'project') action="{{ RealEstateHelper::getProjectsListPageUrl() }}" data-ajax-url="{{ route('public.projects') }}" @else action="{{ RealEstateHelper::getPropertiesListPageUrl() }}" data-ajax-url="{{ route('public.properties') }}" @endif
            method="GET"
        >
            <div
                class="typesearch"
                id="hometypesearch"
            >
                @if ($enableProjectsSearch)
                    <a
                        data-url="{{ RealEstateHelper::getProjectsListPageUrl() }}"
                        data-ajax-url="{{ route('public.projects') }}"
                        href="javascript:void(0)"
                        @if ($defaultSearchType == 'project') class="active" @endif
                        rel="project"
                    >{{ __('Projects') }}</a>
                @endif
                <a
                    data-url="{{ RealEstateHelper::getPropertiesListPageUrl() }}"
                    data-ajax-url="{{ route('public.properties') }}"
                    href="javascript:void(0)"
                    rel="sale"
                    @if ($defaultSearchType == 'sale') class="active" @endif
                >{{ __('Sale') }}</a>
                <a
                    data-url="{{ RealEstateHelper::getPropertiesListPageUrl() }}"
                    data-ajax-url="{{ route('public.properties') }}"
                    href="javascript:void(0)"
                    rel="rent"
                    @if ($defaultSearchType == 'rent') class="active" @endif
                >{{ __('Rent') }}</a>
            </div>
            <div class="input-group input-group-lg">

                <input
                    id="txttypesearch"
                    name="type"
                    type="hidden"
                    @if ($enableProjectsSearch && $defaultSearchType == 'project') value="project" @else value="{{ $defaultSearchType }}" @endif
                >

                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="far fa-search"></i></span>
                </div>
                <div class="keyword-input">
                    <input
                        class="form-control"
                        id="txtkey"
                        name="k"
                        type="text"
                        placeholder="{{ __('Enter keyword...') }}"
                        autocomplete="off"
                    >
                    <div class="spinner-icon">
                        <i class="fas fa-spin fa-spinner"></i>
                    </div>
                </div>
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="far fa-location"></i></span>
                </div>
                <div
                    class="location-input"
                    data-url="{{ route('public.ajax.cities') }}"
                >
                    <input
                        name="city_id"
                        type="hidden"
                    >
                    <input
                        class="select-city-state form-control"
                        name="location"
                        value="{{ BaseHelper::stringify(request()->input('location')) }}"
                        placeholder="{{ __('City, State') }}"
                        autocomplete="off"
                    >
                    <div class="spinner-icon">
                        <i class="fas fa-spin fa-spinner"></i>
                    </div>
                    <div class="suggestion">

                    </div>
                </div>
                <div class="input-group-append search-button-wrapper">
                    <button
                        class="btn btn-orange"
                        type="submit"
                    >{{ __('Search') }}</button>
                </div>

                {!! apply_filters(
                    'main_search_box_filters',
                    Theme::partial('real-estate.filters.search-box-filters', compact('categories', 'enableProjectsSearch')),
                    compact('categories', 'enableProjectsSearch'),
                ) !!}
            </div>
            <div class="listsuggest">

            </div>
        </form>
    </div>
</div>
