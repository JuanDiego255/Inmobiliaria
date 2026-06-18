@php
    $dealLabel = function ($property) {
        $type = (string) $property->type;
        if ($type === 'rent') {
            return __('Alquiler');
        }
        return __('Venta');
    };

    $dealClass = function ($property) {
        $type = (string) $property->type;
        if ($type === 'rent') {
            return 'alquiler';
        }
        $status = (string) $property->status;
        if (in_array($status, ['sold', 'rented'])) {
            return 'vendido';
        }
        return '';
    };

    $getLocation = function ($property) {
        return implode(', ', array_filter([optional($property->city)->name, optional($property->state)->name]));
    };

    $imgCount = function ($property) {
        return is_array($property->images) ? count($property->images) : 0;
    };
@endphp

<section class="ix-sec" id="ixRecent">
    <div class="ix-wrap">
        <div class="ix-sec-head ix-recent-rail-head ix-reveal-up">
            <div>
                <span class="ix-eyebrow">{{ __('Tu actividad') }}</span>
                <h2>{!! BaseHelper::clean($title ?: __('Propiedades vistas recientemente')) !!}</h2>
                @if ($description)
                    <p class="ix-lead">{!! BaseHelper::clean($description) !!}</p>
                @elseif ($subtitle)
                    <p class="ix-lead">{!! BaseHelper::clean($subtitle) !!}</p>
                @else
                    <p class="ix-lead">{{ __('Las propiedades que visitaste recientemente.') }}</p>
                @endif
            </div>
            <div class="ix-rail-nav">
                <button class="ix-rail-btn" id="ixRailPrev" type="button" aria-label="{{ __('Anterior') }}"><span
                        class="material-icons">chevron_left</span></button>
                <button class="ix-rail-btn" id="ixRailNext" type="button" aria-label="{{ __('Siguiente') }}"><span
                        class="material-icons">chevron_right</span></button>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="ix-recent-rail" id="ixRecentRail">
            @foreach ($properties as $property)
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
                    @if ($pImgCount)
                        <span class="ix-imgcount"><span
                                class="material-icons">photo_camera</span>{{ $pImgCount }}</span>
                    @endif
                    <button class="ix-fav" type="button" aria-label="{{ __('Favorito') }}"
                        data-property-id="{{ $property->id }}">
                        <span class="material-icons">favorite_border</span>
                    </button>
                    <div class="ix-prop-overlay">
                        <span class="ix-prop-type">{{ $categoryName }}</span>
                        <h3 class="ix-prop-title">{!! BaseHelper::clean($property->name) !!}</h3>
                        <div class="ix-prop-loc"><span
                                class="material-icons">place</span>{{ $pLocation ?: __('Sin ubicación') }}</div>
                        <div class="ix-prop-foot">
                            <div class="ix-prop-price">{{ $property->price_format }}</div>
                            <div class="ix-prop-specs">
                                @if ($property->number_bedroom)
                                    <span class="ix-spec"><span
                                            class="material-icons">king_bed</span>{{ $property->number_bedroom }}</span>
                                @endif
                                @if ($property->number_bathroom)
                                    <span class="ix-spec"><span
                                            class="material-icons">bathtub</span>{{ $property->number_bathroom }}</span>
                                @endif
                                @if ($property->square)
                                    <span class="ix-spec"><span
                                            class="material-icons">square_foot</span>{{ $property->square_text }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

</section>
