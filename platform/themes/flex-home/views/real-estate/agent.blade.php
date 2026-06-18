@php
    if ($account->phone) {
        Theme::set('hotlineNumber', $account->phone);
    }

    $palette = ['#717fe0','#7cb342','#66a8a6','#e08a3e','#d4708a','#5b8def','#d6a23e','#4fa3a1'];
    $colorFor = function($name) use ($palette) {
        $h = 0;
        for ($i = 0; $i < mb_strlen($name); $i++) {
            $h = (($h * 31) + mb_ord(mb_substr($name, $i, 1))) & 0xFFFFFFFF;
        }
        return $palette[$h % count($palette)];
    };

    $c = $colorFor($account->name);
    $hasPhoto = $account->avatar && $account->avatar->url;
    $initial = mb_strtoupper(mb_substr($account->name, 0, 1));
    $firstName = explode(' ', trim($account->name))[0];

    $joinedLabel = $account->created_at
        ? $account->created_at->locale('es')->translatedFormat('F Y')
        : '';

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

    $descriptionHtml = BaseHelper::clean($account->description);
    $paragraphs = array_values(array_filter(
        preg_split('/<\/p>\s*/i', $descriptionHtml),
        fn($p) => trim(strip_tags($p)) !== ''
    ));
@endphp

{{-- Breadcrumb --}}
<div class="ag-wrap ag-crumb">
    <nav>
        <a href="{{ route('public.index') }}">{{ __('Inicio') }}</a>
        <span class="material-icons">chevron_right</span>
        <a href="{{ route('public.agents') }}">{{ __('Agentes') }}</a>
        <span class="material-icons">chevron_right</span>
        <span class="ag-here">{{ $account->name }}</span>
    </nav>
</div>

{{-- Hero: ID Card + Biography --}}
<section class="ag-hero">
    <div class="ag-wrap">
        <div class="ag-hero-grid">

            {{-- LEFT: Sticky identity card --}}
            <aside class="ag-id-card ag-reveal-up">
                <div class="ag-portrait" style="--c: {{ $c }}">
                    <div class="ag-portrait-cover" style="--c: {{ $c }}">
                        <span class="material-icons">apartment</span>
                    </div>
                    <div class="ag-portrait-avatar" style="--c: {{ $c }}">
                        @if($hasPhoto)
                            <img src="{{ RvMedia::getImageUrl($account->avatar->url, 'thumb') }}" alt="{{ $account->name }}">
                        @else
                            <span class="ag-ini">{{ $initial }}</span>
                        @endif
                    </div>
                    <div class="ag-id-meta">
                        <div class="ag-id-role">{{ __('Asesor inmobiliario') }}</div>
                        <h2 class="ag-id-name">{{ $account->name }}</h2>

                        <div class="ag-id-rows">
                            @if($account->email && !setting('real_estate_hide_agency_email', 0))
                                <div class="ag-id-row">
                                    <span class="ag-ico"><span class="material-icons">mail</span></span>
                                    <div>
                                        <div class="ag-k">{{ __('Correo electrónico') }}</div>
                                        <div class="ag-v"><a href="mailto:{{ $account->email }}">{{ $account->email }}</a></div>
                                    </div>
                                </div>
                            @endif
                            @if($account->phone && !setting('real_estate_hide_agency_phone', 0))
                                <div class="ag-id-row">
                                    <span class="ag-ico"><span class="material-icons">call</span></span>
                                    <div>
                                        <div class="ag-k">{{ __('Teléfono') }}</div>
                                        <div class="ag-v"><a href="tel:{{ preg_replace('/\s/', '', $account->phone) }}">{{ $account->phone }}</a></div>
                                    </div>
                                </div>
                            @endif
                            @if($joinedLabel)
                                <div class="ag-id-row">
                                    <span class="ag-ico"><span class="material-icons">event</span></span>
                                    <div>
                                        <div class="ag-k">{{ __('Se unió a') }}</div>
                                        <div class="ag-v">{{ ucfirst($joinedLabel) }}</div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="ag-id-actions">
                            @if($account->email && !setting('real_estate_hide_agency_email', 0))
                                <a class="ag-btn ag-btn-primary" href="mailto:{{ $account->email }}">
                                    <span class="material-icons">mail</span>{{ __('Enviar correo') }}
                                </a>
                            @endif
                            @if($account->phone && !setting('real_estate_hide_agency_phone', 0))
                                <a class="ag-btn ag-btn-ghost" href="tel:{{ preg_replace('/\s/', '', $account->phone) }}">
                                    <span class="material-icons">call</span>{{ __('Llamar ahora') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </aside>

            {{-- RIGHT: Editorial biography --}}
            <div class="ag-bio-col">
                <div class="ag-bio-head ag-reveal-up">
                    <span class="ag-eyebrow">{{ __('Información del agente') }}</span>
                    <h1>{{ $account->name }}</h1>
                </div>

                <div class="ag-stats ag-reveal-up">
                    <div class="ag-stat">
                        <div class="ag-stat-n">{{ $properties->total() }}</div>
                        <div class="ag-stat-l">{{ __('Propiedades activas') }}</div>
                    </div>
                </div>

                @if($descriptionHtml && trim(strip_tags($descriptionHtml)) !== '')
                    <div class="ag-bio-body ag-reveal-up">
                        <span class="ag-eyebrow ag-bio-eyebrow">{{ __('Sobre') }} {{ $firstName }}</span>
                        <div class="ag-bio-prose">
                            {!! $descriptionHtml !!}
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>
</section>

{{-- Properties of this agent --}}
<section class="ag-props-sec">
    <div class="ag-wrap">
        <div class="ag-props-head ag-reveal-up">
            <div>
                <span class="ag-eyebrow">{{ __('Portafolio') }}</span>
                <h2>{{ __('Propiedades de') }} {{ $firstName }}</h2>
            </div>
            <span class="ag-props-count"><b>{{ $properties->total() }}</b> {{ __('resultados') }}</span>
        </div>

        <div class="ag-props-grid" id="agPropsGrid">
            @forelse($properties as $property)
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
                    <button class="ix-fav" type="button" aria-label="{{ __('Favorito') }}" data-property-id="{{ $property->id }}">
                        <span class="material-icons">favorite_border</span>
                    </button>
                    <div class="ix-prop-overlay">
                        <span class="ix-prop-type">{{ $categoryName }}</span>
                        <h3 class="ix-prop-title">{!! BaseHelper::clean($property->name) !!}</h3>
                        <div class="ix-prop-loc"><span class="material-icons">place</span>{{ $pLocation ?: __('Sin ubicación') }}</div>
                        <div class="ix-prop-foot">
                            <div class="ix-prop-price">
                                {{ $property->price_format }}
                                @if((string) $property->type === 'rent')
                                    <span>/{{ __('mes') }}</span>
                                @endif
                            </div>
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
                    </div>
                </a>
            @empty
                <div class="ag-props-empty">
                    <span class="material-icons">home_work</span>
                    <h3>{{ __('Aún no hay propiedades publicadas') }}</h3>
                    <p>{{ __('Este agente todavía no tiene inmuebles activos. Volvé pronto o escribíle directamente.') }}</p>
                </div>
            @endforelse
        </div>

        @if($properties->hasPages())
            <div class="ag-pagination">
                <nav aria-label="Page navigation">
                    {!! $properties->withQueryString()->links() !!}
                </nav>
            </div>
        @endif
    </div>
</section>
