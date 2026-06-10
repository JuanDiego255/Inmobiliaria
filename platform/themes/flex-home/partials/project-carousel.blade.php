@php
    $categories = $categories ?? get_property_categories([
        'indent' => '↳',
        'conditions' => ['status' => \Botble\Base\Enums\BaseStatusEnum::PUBLISHED],
    ]);

    $allProjects = $projects ?? collect();

    $heroImage = theme_option('breadcrumb_background')
        ? RvMedia::url(theme_option('breadcrumb_background'))
        : null;

    $heroTitle = $title ?? __('Descubre nuestros proyectos');
    $heroDescription = $description ?? theme_option('home_project_description') ?? __('Una selección de proyectos inmobiliarios en desarrollo y venta. Explorá las mejores oportunidades de inversión.');

    $statusLabels = [
        'not_available' => __('No disponible'),
        'pre_sale'      => __('Preventa'),
        'selling'       => __('En venta'),
        'sold'          => __('Vendido'),
        'building'      => __('En construcción'),
    ];

    $statusBadgeClass = [
        'not_available' => '',
        'pre_sale'      => 'pj-badge--presale',
        'selling'       => '',
        'sold'          => 'pj-badge--sold',
        'building'      => 'pj-badge--building',
    ];

    $statusBarClass = [
        'not_available' => 'pj-status-bar__fill--unavailable',
        'pre_sale'      => 'pj-status-bar__fill--presale',
        'selling'       => 'pj-status-bar__fill--selling',
        'sold'          => 'pj-status-bar__fill--sold',
        'building'      => 'pj-status-bar__fill--building',
    ];
@endphp

{{-- Hero section --}}
<header class="pj-hero {{ $heroImage ? '' : 'pj-hero--solid' }}"
    @if($heroImage) style="background-image: url('{{ $heroImage }}')" @endif>
    <div class="pj-wrap">
        <span class="pj-eyebrow">{{ __('Proyectos') }} · Costa Rica</span>
        <h1 class="pj-hero__title">{!! $heroTitle !!}</h1>
        <p class="pj-hero__text">{{ $heroDescription }}</p>
        <div class="pj-scroll-hint">
            <span class="pj-scroll-hint__dot"><span class="material-icons">arrow_downward</span></span>
            {{ __('Desplazá para explorar') }}
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
                    <span class="pj-eyebrow">{{ __('Proyectos disponibles') }}</span>
                    <h2 class="pj-sec-head__title">{{ __('Recorré nuestros proyectos') }}</h2>
                </div>
                <div class="pj-sec-head__meta">
                    <p class="pj-sec-head__lead">{{ __('El scroll mueve los proyectos de lado a lado. Pasá el cursor sobre una tarjeta para ver más detalles.') }}</p>
                </div>
            </div>

            {{-- Sort bar --}}
            <div class="pj-sort-bar">
                <div class="pj-sort-bar__group">
                    <span class="pj-sort-bar__label">{{ __('Showing') }}</span>
                    <select name="per_page" id="pj-per-page">
                        <option value="">{{ $allProjects->count() }} {{ __('proyectos') }}</option>
                    </select>
                </div>
                <div class="pj-sort-bar__group">
                    <span class="pj-sort-bar__label">{{ __('Sort by') }}</span>
                    <select name="sort_by" id="pj-sort-by">
                        <option value="">{{ __('Default') }}</option>
                        <option value="date_desc" @if(request()->input('sort_by') == 'date_desc') selected @endif>{{ __('Newest') }}</option>
                        <option value="date_asc" @if(request()->input('sort_by') == 'date_asc') selected @endif>{{ __('Oldest') }}</option>
                        <option value="price_asc" @if(request()->input('sort_by') == 'price_asc') selected @endif>{{ __('Price') . ': ' . __('low to high') }}</option>
                        <option value="price_desc" @if(request()->input('sort_by') == 'price_desc') selected @endif>{{ __('Price') . ': ' . __('high to low') }}</option>
                        <option value="name_asc" @if(request()->input('sort_by') == 'name_asc') selected @endif>{{ __('Name') . ': A-Z' }}</option>
                        <option value="name_desc" @if(request()->input('sort_by') == 'name_desc') selected @endif>{{ __('Name') . ': Z-A' }}</option>
                    </select>
                </div>
            </div>

            <div class="pj-track" id="pjTrack">
                @foreach($allProjects as $project)
                    @php
                        $projImage = $project->image
                            ? RvMedia::getImageUrl($project->image, 'medium', false, RvMedia::getDefaultImage())
                            : RvMedia::getDefaultImage();
                        $statusKey = (string) $project->status;
                        $statusLabel = $statusLabels[$statusKey] ?? ucfirst(str_replace('_', ' ', $statusKey));
                        $badgeClass = $statusBadgeClass[$statusKey] ?? '';
                        $barClass = $statusBarClass[$statusKey] ?? 'pj-status-bar__fill--selling';
                        $categoryName = optional($project->category)->name ?? __('Proyecto');
                        $cityName = optional($project->city)->name ?? '';
                        $stateName = optional($project->state)->name ?? '';
                        $locationText = implode(', ', array_filter([$cityName, $stateName]));

                        $priceText = '';
                        if ($project->price_from || $project->price_to) {
                            if ($project->price_from) {
                                $priceText .= __('Desde') . ' ' . format_price($project->price_from, $project->currency);
                            }
                            if ($project->price_to) {
                                $priceText .= ($priceText ? ' - ' : '') . format_price($project->price_to, $project->currency);
                            }
                        }
                    @endphp
                    <article class="pj-card {{ $project->is_featured ? 'pj-card--featured' : '' }}"
                             data-category="{{ optional($project->category)->id }}"
                             data-state="{{ $project->state_id }}"
                             data-city="{{ $project->city_id }}"
                             data-price="{{ $project->price_from ?? 0 }}"
                             data-name="{{ $project->name }}"
                             data-date="{{ $project->created_at->format('Y-m-d') }}">
                        <a href="{{ $project->url }}" class="pj-card__link">
                            <img class="pj-card__img" src="{{ $projImage }}" alt="{{ $project->name }}" loading="lazy">
                        </a>
                        @if($project->is_featured)
                            <span class="pj-badge pj-badge--featured">{{ __('Destacado') }}</span>
                        @elseif($badgeClass)
                            <span class="pj-badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                        @else
                            <span class="pj-badge">{{ $statusLabel }}</span>
                        @endif
                        <div class="pj-overlay">
                            <span class="pj-overlay__cat">{{ $categoryName }}</span>
                            <h3 class="pj-overlay__title">{!! BaseHelper::clean($project->name) !!}</h3>
                            <div class="pj-overlay__loc">
                                <span class="material-icons">place</span>{{ $locationText ?: __('Sin ubicación') }}
                            </div>
                            <div class="pj-overlay__foot">
                                @if($priceText)
                                    <div class="pj-overlay__price">{{ $priceText }}</div>
                                @endif
                                <div class="pj-overlay__specs">
                                    @if($project->number_block)
                                        <span class="pj-spec"><span class="material-icons">domain</span>{{ $project->number_block }}</span>
                                    @endif
                                    @if($project->number_floor)
                                        <span class="pj-spec"><span class="material-icons">layers</span>{{ $project->number_floor }}</span>
                                    @endif
                                    @if($project->number_flat)
                                        <span class="pj-spec"><span class="material-icons">apartment</span>{{ $project->number_flat }}</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Status progress bar --}}
                            <div class="pj-status-bar">
                                <div class="pj-status-bar__track">
                                    <div class="pj-status-bar__fill {{ $barClass }}"></div>
                                </div>
                                <span class="pj-status-bar__label">{{ $statusLabel }}</span>
                            </div>

                            <div class="pj-reveal">
                                <div class="pj-reveal__inner">
                                    <div class="pj-reveal__grid">
                                        @if($project->number_flat)
                                            <div class="pj-rv">
                                                <span class="material-icons">apartment</span>
                                                <div>
                                                    <div class="pj-rv__k">{{ __('Unidades') }}</div>
                                                    <div class="pj-rv__v">{{ $project->number_flat }}</div>
                                                </div>
                                            </div>
                                        @endif
                                        @if($project->number_floor)
                                            <div class="pj-rv">
                                                <span class="material-icons">layers</span>
                                                <div>
                                                    <div class="pj-rv__k">{{ __('Pisos') }}</div>
                                                    <div class="pj-rv__v">{{ $project->number_floor }}</div>
                                                </div>
                                            </div>
                                        @endif
                                        @if($project->number_block)
                                            <div class="pj-rv">
                                                <span class="material-icons">domain</span>
                                                <div>
                                                    <div class="pj-rv__k">{{ __('Torres') }}</div>
                                                    <div class="pj-rv__v">{{ $project->number_block }}</div>
                                                </div>
                                            </div>
                                        @endif
                                        @if($project->date_finish)
                                            <div class="pj-rv">
                                                <span class="material-icons">event</span>
                                                <div>
                                                    <div class="pj-rv__k">{{ __('Entrega') }}</div>
                                                    <div class="pj-rv__v">{{ \Carbon\Carbon::parse($project->date_finish)->format('M Y') }}</div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <a class="pj-cta" href="{{ $project->url }}">
                                        {{ __('Ver proyecto') }} <span class="material-icons">north_east</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="pj-prog">
                <span class="pj-prog__num"><span id="pjProgNum">01</span> <span class="pj-prog__tot">/ <span id="pjProgTotal">{{ str_pad($allProjects->count(), 2, '0', STR_PAD_LEFT) }}</span></span></span>
                <div class="pj-prog__line"><div class="pj-prog__fill" id="pjProgFill"></div></div>
            </div>

            {{-- No results message --}}
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
    <div class="pj-wrap" style="text-align: center;">
        <h2 class="pj-closing__title">{{ __('¿Listo para') }} <em>{{ __('invertir') }}</em>?</h2>
        <p class="pj-closing__text">{{ __('Contactanos y te ayudamos a encontrar el proyecto ideal para vos. Asesoría personalizada sin compromiso.') }}</p>
        <a href="{{ url('/contact') }}" class="pj-closing__btn">
            {{ __('Contactar un asesor') }} <span class="material-icons">arrow_forward</span>
        </a>
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
