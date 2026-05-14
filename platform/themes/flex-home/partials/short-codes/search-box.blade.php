@php
    $categories = get_property_categories(['indent' => '↳', 'conditions' => ['status' => \Botble\Base\Enums\BaseStatusEnum::PUBLISHED]]);
    $backgroundImage = $shortcode->background_image ?: theme_option('home_banner');
    $enableProjectsSearch = $shortcode->enable_search_projects_on_homepage_search ? $shortcode->enable_search_projects_on_homepage_search == 'yes' : theme_option('enable_search_projects_on_homepage_search', 'yes') == 'yes';
    $defaultSearchType = $shortcode->default_home_search_type ?: theme_option('default_home_search_type', 'sale');

    $tabs = [];
    $quantity = min((int) $shortcode->quantity, 20);
    if ($quantity) {
        for ($i = 1; $i <= $quantity; $i++) {
            $tabs[] = RvMedia::getImageUrl($shortcode->{'image_' . $i});
        }
    }
    $seconds = (int) $shortcode->seconds ?: 5;
    $enableChangeBackground = $shortcode->enable_change_image_background ?: 'no';

    $states = \Botble\Location\Models\State::query()
        ->wherePublished()
        ->orderBy('order')
        ->orderBy('name')
        ->select(['id', 'name'])
        ->get();
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
            action="{{ RealEstateHelper::getPropertiesListPageUrl() }}"
            data-ajax-url="{{ route('public.properties') }}"
            data-projects-url="{{ RealEstateHelper::getProjectsListPageUrl() }}"
            data-projects-ajax-url="{{ route('public.projects') }}"
            data-properties-url="{{ RealEstateHelper::getPropertiesListPageUrl() }}"
            data-properties-ajax-url="{{ route('public.properties') }}"
            method="GET"
        >
            <input id="txttypesearch" name="type" type="hidden" value="{{ $defaultSearchType }}">

            <div class="search-box-modern">
                <div class="search-box-modern__fields">
                    {{-- Category --}}
                    <div class="search-box-modern__field">
                        <div class="sbm-dropdown" data-name="category_id">
                            <button type="button" class="sbm-dropdown__trigger">
                                <i class="far fa-home"></i>
                                <span class="sbm-dropdown__label">{{ __('Tipos de propiedades') }}</span>
                                <i class="fas fa-chevron-down sbm-dropdown__arrow"></i>
                            </button>
                            <div class="sbm-dropdown__menu">
                                <div class="sbm-dropdown__search-wrap">
                                    <input type="text" class="sbm-dropdown__search" placeholder="{{ __('Search...') }}">
                                </div>
                                <ul class="sbm-dropdown__list">
                                    <li><a href="#" data-value="">{{ __('All') }}</a></li>
                                    @foreach($categories as $category)
                                        <li><a href="#" data-value="{{ $category->id }}">{!! BaseHelper::clean($category->indent_text) !!} {{ $category->name }}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                            <input type="hidden" name="category_id" value="{{ request()->input('category_id') }}">
                        </div>
                    </div>

                    <div class="search-box-modern__divider"></div>

                    {{-- Type (Venta / Alquiler) --}}
                    <div class="search-box-modern__field">
                        <div class="sbm-dropdown" data-name="type">
                            <button type="button" class="sbm-dropdown__trigger">
                                <i class="far fa-tag"></i>
                                <span class="sbm-dropdown__label">{{ $defaultSearchType == 'sale' ? __('Sale') : ($defaultSearchType == 'rent' ? __('Rent') : __('Sale')) }}</span>
                                <i class="fas fa-chevron-down sbm-dropdown__arrow"></i>
                            </button>
                            <div class="sbm-dropdown__menu">
                                <ul class="sbm-dropdown__list">
                                    <li><a href="#" data-value="">{{ __('All') }}</a></li>
                                    @foreach(\Botble\RealEstate\Enums\PropertyTypeEnum::labels() as $key => $label)
                                        <li><a href="#" data-value="{{ $key }}" @if(request()->input('type', $defaultSearchType) == $key) class="active" @endif>{{ $label }}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="search-box-modern__divider"></div>

                    {{-- Location (State → City cascade) --}}
                    <div class="search-box-modern__field search-box-modern__field--location">
                        <div class="sbm-dropdown sbm-dropdown--cascade" data-name="location">
                            <button type="button" class="sbm-dropdown__trigger">
                                <i class="far fa-map-marker-alt"></i>
                                <span class="sbm-dropdown__label">{{ __('Location') }}</span>
                                <i class="fas fa-chevron-down sbm-dropdown__arrow"></i>
                            </button>
                            <div class="sbm-dropdown__menu sbm-dropdown__menu--wide">
                                <div class="sbm-cascade">
                                    <div class="sbm-cascade__col">
                                        <div class="sbm-dropdown__search-wrap">
                                            <input type="text" class="sbm-dropdown__search" placeholder="{{ __('Search state...') }}">
                                        </div>
                                        <ul class="sbm-dropdown__list" id="sbm-states-list"
                                            data-url="{{ route('ajax.states-by-country') }}">
                                            <li><a href="#" data-value="" class="active">{{ __('All') }}</a></li>
                                            @foreach($states as $state)
                                                <li><a href="#" data-value="{{ $state->id }}">{{ $state->name }}</a></li>
                                            @endforeach
                                        </ul>
                                    </div>
                                    <div class="sbm-cascade__col" id="sbm-cities-col"
                                         data-url="{{ route('ajax.cities-by-state') }}">
                                        <div class="sbm-dropdown__search-wrap">
                                            <input type="text" class="sbm-dropdown__search" placeholder="{{ __('Search city...') }}">
                                        </div>
                                        <ul class="sbm-dropdown__list" id="sbm-cities-list">
                                            <li class="sbm-dropdown__placeholder">{{ __('Select a state first') }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="state_id" value="{{ request()->input('state_id') }}">
                            <input type="hidden" name="city_id" value="{{ request()->input('city_id') }}">
                        </div>
                    </div>

                    @if ($enableProjectsSearch)
                        <div class="search-box-modern__divider"></div>

                        {{-- Projects checkbox --}}
                        <div class="search-box-modern__field search-box-modern__field--check">
                            <label class="sbm-checkbox" for="search-in-projects">
                                <input type="checkbox" id="search-in-projects" value="1">
                                <span class="sbm-checkbox__box"></span>
                                <span class="sbm-checkbox__text">{{ __('Projects') }}</span>
                            </label>
                        </div>
                    @endif
                </div>

                {{-- Search button --}}
                <button type="submit" class="search-box-modern__btn">
                    <i class="far fa-search"></i>
                    <span>{{ __('Search') }}</span>
                </button>
            </div>

            <div class="listsuggest"></div>
        </form>
    </div>
</div>
