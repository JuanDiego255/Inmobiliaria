@php
    $categories = $categories ?? get_property_categories([
        'indent' => '↳',
        'conditions' => ['status' => \Botble\Base\Enums\BaseStatusEnum::PUBLISHED],
    ]);

    $allProjects = $projects ?? collect();

    $heroImage = theme_option('breadcrumb_background')
        ? RvMedia::url(theme_option('breadcrumb_background'))
        : null;

    $heroTitle = $title ?? __('Descubrí nuestros proyectos');
    $heroDescription = $description
        ?? theme_option('home_project_description')
        ?? __('Los desarrollos más prestigiosos, seleccionados a mano. Seguí bajando para recorrerlos uno a uno.');

    // Split featured vs rest
    $featured = $allProjects->firstWhere('is_featured', true) ?? $allProjects->first();
    $rest = $allProjects->reject(fn($p) => optional($featured)->id === $p->id);

    // Status → label + CSS class
    $statusMap = [
        'selling'       => ['En venta', 'venta'],
        'not_available' => ['No disponible', 'not_available'],
        'pre_sale'      => ['Preventa', 'pre_sale'],
        'sold'          => ['Vendido', 'vendido'],
        'building'      => ['En construcción', 'building'],
    ];

    $getStatus = function($project) use ($statusMap) {
        $key = (string) $project->status;
        return $statusMap[$key] ?? ['En venta', 'venta'];
    };

    $getLocation = function($project) {
        return implode(', ', array_filter([
            optional($project->city)->name,
            optional($project->state)->name,
        ]));
    };

    $getPrice = function($project) {
        if (!$project->price_from && !$project->price_to) return null;
        $parts = [];
        if ($project->price_from) {
            $parts[] = format_price($project->price_from, $project->currency);
        }
        if ($project->price_to) {
            $parts[] = format_price($project->price_to, $project->currency);
        }
        return implode(' – ', $parts);
    };
@endphp

{{-- Hero --}}
<header class="pj-hero {{ $heroImage ? '' : 'pj-hero--solid' }}">
    @if($heroImage)
        <div class="pj-hero-bg">
            <img src="{{ $heroImage }}" alt="{{ $heroTitle }}">
        </div>
    @endif
    <div class="pj-hero-inner">
        <div class="pj-wrap">
            <span class="pj-eyebrow">{{ __('Desarrollos inmobiliarios') }}</span>
            <h1 class="pj-hero__title">{!! $heroTitle !!}</h1>
            <p class="pj-hero__text">{{ $heroDescription }}</p>
            <div class="pj-hero-crumb">
                <a href="{{ route('public.index') }}">{{ __('Inicio') }}</a>
                <span class="material-icons">chevron_right</span>
                <span class="cur">{{ __('Proyectos') }}</span>
            </div>
        </div>
    </div>
</header>

{{-- Search filters --}}
<section class="pj-filters-section">
    <div class="container-fluid w90">
        <form
            id="pj-filter-form"
            data-ajax-url="{{ $ajaxUrl ?? route('public.projects') }}"
            action="{{ $actionUrl ?? RealEstateHelper::getProjectsListPageUrl() }}"
            method="get"
        >
            {!! apply_filters(
                'properties_projects_detail_search_box',
                view(Theme::getThemeNamespace() . '::views.real-estate.includes.search-box', [
                    'type' => 'project',
                    'categories' => $categories,
                ])->render(),
                ['type' => 'project', 'categories' => $categories],
            ) !!}
        </form>
    </div>
</section>

{{-- Pinned horizontal carousel --}}
@if($allProjects->count() >= 1)
<section class="pj-pin-wrap" id="pjPinWrap">
    <div class="pj-pin-sticky">
        <div class="pj-pin-inner">
            <div class="pj-sec-head">
                <div>
                    <span class="pj-eyebrow">{{ __('Proyectos destacados') }}</span>
                    <h2 class="pj-sec-head__title">{{ __('Recorré los desarrollos') }}</h2>
                </div>
                <p class="pj-sec-head__lead">{{ __('El primero es el proyecto destacado. El scroll mueve los demás de lado a lado.') }}</p>
            </div>

            <div class="pj-track" id="pjTrack">

                {{-- ═══ ITEM 1: PATTERN A — Featured split card ═══ --}}
                @if($featured)
                    @php
                        [$fStatusLabel, $fStatusCls] = $getStatus($featured);
                        $fImage = $featured->image
                            ? RvMedia::getImageUrl($featured->image, null, false, RvMedia::getDefaultImage())
                            : RvMedia::getDefaultImage();
                        $fLocation = $getLocation($featured);
                        $fPrice = $getPrice($featured);
                        $fCategoryName = optional($featured->categories->first())->name ?? __('Proyecto');
                    @endphp
                    <article class="proj-featured" id="pjFeatured">
                        <div class="pf-media">
                            <img src="{{ $fImage }}" alt="{{ $featured->name }}">
                            <span class="pf-badge"><span class="material-icons">star</span> {{ __('Destacado') }}</span>
                        </div>
                        <div class="pf-panel">
                            <span class="pf-eyebrow">{{ $fCategoryName }} · {{ $fStatusLabel }}</span>
                            <h3 class="pf-title">{{ $featured->name }}</h3>
                            <div class="pf-loc"><span class="material-icons">place</span>{{ $fLocation ?: __('Sin ubicación') }}</div>
                            <p class="pf-desc">{{ Str::limit(strip_tags($featured->description), 180) }}</p>
                            <div class="pf-chips">
                                @foreach($featured->categories as $cat)
                                    <span>{{ $cat->name }}</span>
                                @endforeach
                            </div>
                            @if($fPrice)
                                <div class="pf-price">
                                    <span class="lbl">{{ __('Desde') }}</span>
                                    {{ $fPrice }}
                                </div>
                            @endif
                            <a class="pf-cta" href="{{ $featured->url }}">
                                {{ __('Conocer el proyecto') }} <span class="material-icons">north_east</span>
                            </a>
                        </div>
                    </article>

                    {{-- ═══ CONNECTOR "Más proyectos" ═══ --}}
                    @if($rest->count())
                        <div class="pj-connector" aria-hidden="true">
                            <div class="cn-inner">
                                <span class="cn-label">{{ __('Más proyectos') }}</span>
                                <div class="cn-arrows">
                                    <span class="material-icons">chevron_right</span>
                                    <span class="material-icons">chevron_right</span>
                                    <span class="material-icons">chevron_right</span>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif

                {{-- ═══ ITEMS 2..N: PATTERN B — Regular cards (identical) ═══ --}}
                @foreach($rest as $project)
                    @php
                        [$sLabel, $sCls] = $getStatus($project);
                        $pImage = $project->image
                            ? RvMedia::getImageUrl($project->image, null, false, RvMedia::getDefaultImage())
                            : RvMedia::getDefaultImage();
                        $pLocation = $getLocation($project);
                        $pPrice = $getPrice($project);
                        $pCategoryName = optional($project->categories->first())->name ?? __('Proyecto');
                    @endphp
                    <a class="proj-card" href="{{ $project->url }}">
                        <img class="pc-img" src="{{ $pImage }}" alt="{{ $project->name }}" loading="lazy">
                        <div class="pc-overlay"></div>
                        <span class="pc-status {{ $sCls }}">{{ $sLabel }}</span>
                        <div class="pc-content">
                            <span class="pc-cat">{{ $pCategoryName }}</span>
                            <h3 class="pc-title">{!! BaseHelper::clean($project->name) !!}</h3>
                            <div class="pc-loc"><span class="material-icons">place</span>{{ $pLocation ?: __('Sin ubicación') }}</div>
                            @if($pPrice)
                                <div class="pc-price">
                                    <span class="lbl">{{ __('Desde') }}</span>{{ $pPrice }}
                                </div>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="pj-prog">
                <span class="pj-prog__num">
                    <span id="pjProgNum">01</span>
                    <span class="pj-prog__tot">/ <span id="pjProgTotal">{{ str_pad($allProjects->count(), 2, '0', STR_PAD_LEFT) }}</span></span>
                </span>
                <div class="pj-prog__line"><div class="pj-prog__fill" id="pjProgFill"></div></div>
            </div>

            <div class="pj-no-results" id="pjNoResults" style="display:none;">
                <div class="pj-no-results__inner">
                    <span class="material-icons">search_off</span>
                    <p>{{ __('No se encontraron proyectos con los filtros seleccionados.') }}</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endif

{{-- Closing CTA --}}
<section class="pj-closing">
    <div class="pj-wrap">
        <div class="pj-closing-inner">
            <div>
                <span class="pj-eyebrow">{{ __('¿Te interesa invertir?') }}</span>
                <h2 class="pj-closing__title">{{ __('Conocé los planes de pago de cada proyecto.') }}</h2>
                <p class="pj-closing__text">{{ __('Nuestro equipo te asesora sobre disponibilidad, financiamiento y fechas de cada desarrollo.') }}</p>
            </div>
            <a href="{{ url('/contact') }}" class="pj-closing__btn">
                {{ __('Agendar una visita') }} <span class="material-icons">north_east</span>
            </a>
        </div>
    </div>
</section>

{{-- Pagination --}}
@if($allProjects instanceof \Illuminate\Pagination\LengthAwarePaginator && $allProjects->hasPages())
<div class="pj-pagination">
    <nav aria-label="Page navigation">
        {!! $allProjects->withQueryString()->onEachSide(1)->links() !!}
    </nav>
</div>
@endif
