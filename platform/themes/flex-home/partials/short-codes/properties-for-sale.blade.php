@php
    $dealLabel = function($property) {
        $type = (string) $property->type;
        if ($type === 'rent') return __('Alquiler');
        return __('Venta');
    };

    $dealClass = function($property) {
        $type = (string) $property->type;
        if ($type === 'rent') return 'alquiler';
        $status = (string) $property->status;
        if (in_array($status, ['sold', 'rented'])) return 'vendido';
        return '';
    };

    $getLocation = function($property) {
        return implode(', ', array_filter([
            optional($property->city)->name,
            optional($property->state)->name,
        ]));
    };

    $imgCount = function($property) {
        return is_array($property->images) ? count($property->images) : 0;
    };
@endphp

<section class="ix-sec ix-sec--soft" id="ixForSale">
    <div class="ix-wrap">
        <div class="ix-sec-head ix-reveal-up">
            <span class="ix-eyebrow">{{ __('En venta') }}</span>
            <h2>{!! BaseHelper::clean($title ?: __('Propiedades en venta')) !!}</h2>
            @if ($subtitle)
                <p class="ix-lead">{!! BaseHelper::clean($subtitle) !!}</p>
            @else
                <p class="ix-lead">{{ __('A continuación se muestra la lista de propiedades actualmente en venta.') }}</p>
            @endif
        </div>

        <div class="ix-forsale-grid" id="ixForSaleGrid">
            @foreach($properties as $property)
                @php
                    $pImage = RvMedia::getImageUrl($property->image, 'small', false, RvMedia::getDefaultImage());
                    $pLocation = $getLocation($property);
                    $pImgCount = $imgCount($property);
                    $pDealLabel = $dealLabel($property);
                    $pDealClass = $dealClass($property);
                    $categoryName = optional($property->category)->name ?? __('Propiedad');
                @endphp
                <a class="ix-prop-card" href="{{ $property->url }}">
                    <img class="ix-prop-img" src="{{ $pImage }}" alt="{{ $property->name }}" loading="lazy">
                    <span class="ix-badge {{ $pDealClass }}">{{ $pDealLabel }}</span>
                    @if($pImgCount)
                        <span class="ix-imgcount"><span class="material-icons">photo_camera</span>{{ $pImgCount }}</span>
                    @endif
                    @if(auth('account')->check())
                        <button class="ix-fav" type="button" aria-label="{{ __('Favorito') }}" data-property-id="{{ $property->id }}">
                            <span class="material-icons">favorite_border</span>
                        </button>
                    @endif
                    <div class="ix-prop-overlay">
                        <span class="ix-prop-type">{{ $categoryName }}</span>
                        <h3 class="ix-prop-title">{!! BaseHelper::clean($property->name) !!}</h3>
                        <div class="ix-prop-loc"><span class="material-icons">place</span>{{ $pLocation ?: __('Sin ubicación') }}</div>
                        <div class="ix-prop-foot">
                            <div class="ix-prop-price">{{ $property->price_format }}</div>
                            <div class="ix-prop-specs">
                                @if($property->number_bedroom)
                                    <span class="ix-spec"><span class="material-icons">king_bed</span>{{ $property->number_bedroom }}</span>
                                @endif
                                @if($property->number_bathroom)
                                    <span class="ix-spec"><span class="material-icons">bathtub</span>{{ $property->number_bathroom }}</span>
                                @endif
                                @if($property->square)
                                    <span class="ix-spec"><span class="material-icons">square_foot</span>{{ $property->square_text }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="ix-reveal">
                            <div class="ix-reveal-inner">
                                <div class="ix-reveal-grid">
                                    @if($property->number_floor)
                                        <div class="ix-rv">
                                            <span class="material-icons">layers</span>
                                            <div><div class="k">{{ __('Pisos') }}</div><div class="v">{{ $property->number_floor }}</div></div>
                                        </div>
                                    @endif
                                    @if($property->number_bedroom)
                                        <div class="ix-rv">
                                            <span class="material-icons">king_bed</span>
                                            <div><div class="k">{{ __('Habitaciones') }}</div><div class="v">{{ $property->number_bedroom }}</div></div>
                                        </div>
                                    @endif
                                    @if($property->number_bathroom)
                                        <div class="ix-rv">
                                            <span class="material-icons">bathtub</span>
                                            <div><div class="k">{{ __('Baños') }}</div><div class="v">{{ $property->number_bathroom }}</div></div>
                                        </div>
                                    @endif
                                    @if($property->square)
                                        <div class="ix-rv">
                                            <span class="material-icons">crop_free</span>
                                            <div><div class="k">{{ __('Área') }}</div><div class="v">{{ $property->square_text }}</div></div>
                                        </div>
                                    @endif
                                </div>
                                <span class="ix-cta">{{ __('Ver detalle') }} <span class="material-icons">north_east</span></span>
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
