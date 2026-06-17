@php
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

    $statusMap = [
        'selling'       => 'En venta',
        'not_available' => 'No disponible',
        'pre_sale'      => 'Preventa',
        'sold'          => 'Vendido',
        'building'      => 'En construcción',
    ];
@endphp

<section class="ix-sec" id="ixFeatured">
    <div class="ix-wrap">
        <div class="ix-sec-head ix-reveal-up">
            <span class="ix-eyebrow">{{ __('Proyectos') }}</span>
            <h2>{!! BaseHelper::clean($title ?: __('Proyectos destacados')) !!}</h2>
            @if ($subtitle)
                <p class="ix-lead">{!! BaseHelper::clean($subtitle) !!}</p>
            @else
                <p class="ix-lead">{{ __('Los desarrollos más prestigiosos, seleccionados a mano. Visitá el detalle de cada uno para conocer más.') }}</p>
            @endif
        </div>

        <div class="ix-feat-list" id="ixFeatList">
            @foreach($projects as $project)
                @php
                    $pImage = $project->image
                        ? RvMedia::getImageUrl($project->image, null, false, RvMedia::getDefaultImage())
                        : RvMedia::getDefaultImage();
                    $pLocation = $getLocation($project);
                    $pPrice = $getPrice($project);
                    $statusKey = (string) $project->status;
                    $statusLabel = $statusMap[$statusKey] ?? __('En venta');
                    $pCategoryName = optional($project->categories->first())->name ?? __('Proyecto');
                @endphp
                <article class="ix-feat-card">
                    <div class="ix-feat-media">
                        <img src="{{ $pImage }}" alt="{{ $project->name }}" data-ix-parallax loading="lazy">
                        <span class="ix-feat-badge"><span class="material-icons">star</span> {{ __('Destacado') }}</span>
                    </div>
                    <div class="ix-feat-panel">
                        <span class="ix-feat-eyebrow">{{ $pCategoryName }} · {{ $statusLabel }}</span>
                        <h3 class="ix-feat-title">{!! BaseHelper::clean($project->name) !!}</h3>
                        <div class="ix-feat-loc"><span class="material-icons">place</span>{{ $pLocation ?: __('Sin ubicación') }}</div>
                        <p class="ix-feat-desc">{{ Str::limit(strip_tags($project->description), 180) }}</p>
                        <div class="ix-feat-chips">
                            @foreach($project->categories as $cat)
                                <span>{{ $cat->name }}</span>
                            @endforeach
                        </div>
                        @if($pPrice)
                            <div class="ix-feat-price">
                                <span class="lbl">{{ __('Desde') }}</span>
                                {{ $pPrice }}
                            </div>
                        @endif
                        <a class="ix-feat-cta" href="{{ $project->url }}">
                            {{ __('Conocer el proyecto') }} <span class="material-icons">north_east</span>
                        </a>
                        <span class="ix-feat-index">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
