@php
    $categories =
        $categories ??
        get_property_categories([
            'indent' => '↳',
            'conditions' => ['status' => \Botble\Base\Enums\BaseStatusEnum::PUBLISHED],
        ]);

    $featuredProperties = \Botble\RealEstate\Models\Property::query()
        ->where('moderation_status', \Botble\RealEstate\Enums\ModerationStatusEnum::APPROVED)
        ->whereNotIn('status', [\Botble\RealEstate\Enums\PropertyStatusEnum::NOT_AVAILABLE])
        ->with(['categories', 'city', 'state', 'currency', 'features'])
        ->orderByDesc('is_featured')
        ->orderByDesc('created_at')
        ->limit(8)
        ->get();
@endphp

{{-- Hero section --}}
<header class="pc-hero">
    <div class="pc-wrap">
        <span class="pc-eyebrow">{{ __('Bienes raíces') }} · Costa Rica</span>
        <h1 class="pc-hero__title">Espacios pensados para <em>vivir mejor</em>.</h1>
        <p class="pc-hero__text">Una selección curada de propiedades en venta y alquiler. Seguí bajando para recorrer
            nuestras propiedades destacadas.</p>
        <div class="pc-scroll-hint">
            <span class="pc-scroll-hint__dot"><span class="material-icons">arrow_downward</span></span>
            {{ __('Desplazá para explorar') }}
        </div>
    </div>
</header>

{{-- Search filters --}}
<section class="pc-filters-section">
    <div class="container-fluid w90">
        <form id="pc-filter-form" action="{{ $actionUrl ?? RealEstateHelper::getPropertiesListPageUrl() }}"
            method="get">
            @include(Theme::getThemeNamespace() . '::views.real-estate.includes.search-box', [
                'type' => 'property',
                'categories' => $categories,
            ])
        </form>
    </div>
</section>

{{-- Pinned horizontal carousel --}}
@if ($featuredProperties->count() >= 1)
    <section class="pc-pin-wrap" id="pcPinWrap">
        <div class="pc-pin-sticky">
            <div class="pc-pin-inner">
                <div class="pc-sec-head">
                    <div>
                        <span class="pc-eyebrow">{{ __('Propiedades destacadas') }}</span>
                        <h2 class="pc-sec-head__title">{{ __('Recorré nuestra cartera') }}</h2>
                    </div>
                    <div class="pc-sec-head__meta">
                        <p class="pc-sec-head__lead">
                            {{ __('El scroll mueve las propiedades de lado a lado. Pasá el cursor sobre una card para ver más detalles.') }}
                        </p>
                    </div>
                </div>

                <div class="pc-track" id="pcTrack">
                    @foreach ($featuredProperties as $property)
                        @php
                            $propImage = $property->image
                                ? RvMedia::getImageUrl($property->image)
                                : RvMedia::getDefaultImage();
                            $propType = $property->type;
                            $isRent = $propType == \Botble\RealEstate\Enums\PropertyTypeEnum::RENT;
                            $dealLabel = $isRent ? __('Alquiler') : __('Venta');
                            $categoryName = $property->category->name ?? __('Propiedad');
                            $cityName = $property->city->name ?? '';
                            $stateName = $property->state->name ?? '';
                            $locationText = implode(', ', array_filter([$cityName, $stateName]));
                            $priceText = $property->price_format;
                            $periodLabel = $isRent ? '/' . Str::lower($property->period->label()) : '';
                        @endphp
                        <article class="pc-card" data-category="{{ $property->categories->pluck('id')->implode(',') }}"
                            data-type="{{ (string) $propType }}" data-state="{{ $property->state_id }}"
                            data-city="{{ $property->city_id }}" data-bedrooms="{{ $property->number_bedroom }}"
                            data-bathrooms="{{ $property->number_bathroom }}" data-price="{{ $property->price }}">
                            <a href="{{ $property->url }}" class="pc-card__link">
                                <img class="pc-card__img" src="{{ $propImage }}" alt="{{ $property->name }}"
                                    loading="lazy">
                            </a>
                            <span class="pc-badge {{ $isRent ? 'pc-badge--rent' : '' }}">{{ $dealLabel }}</span>
                            @if(auth('account')->check())
                                <button class="pc-fav" data-id="{{ $property->id }}" type="button"
                                    aria-label="Favorito">
                                    <span class="material-icons">favorite_border</span>
                                </button>
                            @endif
                            <div class="pc-overlay">
                                <span class="pc-overlay__type">{{ $categoryName }}</span>
                                <h3 class="pc-overlay__title">{{ $property->name }}</h3>
                                <div class="pc-overlay__loc">
                                    <span class="material-icons">place</span>{{ $locationText ?: __('Sin ubicación') }}
                                </div>
                                <div class="pc-overlay__foot">
                                    <div class="pc-overlay__price">
                                        {{ $priceText }}
                                        @if ($periodLabel)
                                            <span>{{ $periodLabel }}</span>
                                        @endif
                                    </div>
                                    <div class="pc-overlay__specs">
                                        @if ($property->number_bedroom)
                                            <span class="pc-spec"><span
                                                    class="material-icons">king_bed</span>{{ $property->number_bedroom }}</span>
                                        @endif
                                        @if ($property->number_bathroom)
                                            <span class="pc-spec"><span
                                                    class="material-icons">bathtub</span>{{ $property->number_bathroom }}</span>
                                        @endif
                                        @if ($property->square)
                                            <span class="pc-spec"><span
                                                    class="material-icons">square_foot</span>{{ number_format($property->square) }}
                                                m²</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="pc-reveal">
                                    <div class="pc-reveal__inner">
                                        <div class="pc-reveal__grid">
                                            @if ($property->number_floor)
                                                <div class="pc-rv">
                                                    <span class="material-icons">layers</span>
                                                    <div>
                                                        <div class="pc-rv__k">{{ __('Pisos') }}</div>
                                                        <div class="pc-rv__v">{{ $property->number_floor }}</div>
                                                    </div>
                                                </div>
                                            @endif
                                            <div class="pc-rv">
                                                <span class="material-icons">event</span>
                                                <div>
                                                    <div class="pc-rv__k">{{ __('Publicado') }}</div>
                                                    <div class="pc-rv__v">{{ $property->created_at->format('Y') }}
                                                    </div>
                                                </div>
                                            </div>
                                            @if ($property->square)
                                                <div class="pc-rv">
                                                    <span class="material-icons">crop_free</span>
                                                    <div>
                                                        <div class="pc-rv__k">{{ __('Área') }}</div>
                                                        <div class="pc-rv__v">{{ number_format($property->square) }} m²
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                            <div class="pc-rv">
                                                <span class="material-icons">category</span>
                                                <div>
                                                    <div class="pc-rv__k">{{ __('Categoría') }}</div>
                                                    <div class="pc-rv__v">{{ $categoryName }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <a class="pc-cta" href="{{ $property->url }}">
                                            {{ __('Ver detalle') }} <span class="material-icons">north_east</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="pc-prog">
                    <span class="pc-prog__num"><span id="pcProgNum">01</span> <span class="pc-prog__tot">/ <span
                                id="pcProgTotal">{{ str_pad($featuredProperties->count(), 2, '0', STR_PAD_LEFT) }}</span></span></span>
                    <div class="pc-prog__line">
                        <div class="pc-prog__fill" id="pcProgFill"></div>
                    </div>
                </div>

                {{-- No results message --}}
                <div class="pc-no-results" id="pcNoResults" style="display:none;">
                    <div class="pc-no-results__inner">
                        <span class="material-icons">search_off</span>
                        <p>{{ __('No se encontraron propiedades con los filtros seleccionados.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif

<section class="pj-closing">
    <div class="pj-wrap">
        <div class="pj-closing-inner">
            <div>
                <span class="pj-eyebrow">{{ __('¿Buscás algo a la medida?') }}</span>
                <h2 class="pj-closing__title">{{ __('Contanos qué necesitás y te lo encontramos.') }}</h2>
                <p class="pj-closing__text">
                    {{ __('Más de 200 propiedades en todo el país. Nuestro equipo te acompaña en cada paso de la compra o el alquiler.') }}
                </p>
            </div>
            <a href="{{ url('/contact') }}" class="pj-closing__btn">
                {{ __('Hablar con un asesor') }} <span class="material-icons">north_east</span>
            </a>
        </div>
    </div>
</section>
