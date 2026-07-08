@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
<div class="cd-wrap">

    {{-- Page Header --}}
    <header class="cd-head">
        <nav class="cd-crumb">
            <a href="{{ route('dashboard.index') }}">Inicio</a>
            <span class="material-icons">chevron_right</span>
            <a href="{{ route('board.index') }}">Tableros</a>
            <span class="material-icons">chevron_right</span>
            <a href="{{ route('board.edit', $board->id) }}">{{ $board->name }}</a>
            <span class="material-icons">chevron_right</span>
            <span class="cd-cur">Calificación</span>
        </nav>
        <div class="cd-head-row">
            <div>
                <span class="cd-eyebrow">Tablero · Calificación de propiedades</span>
                <h1>{{ $board->name }}</h1>
                <p class="cd-head-sub">Vista de solo lectura — el cliente/lead clasifica las propiedades desde el tablero público.</p>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                <span class="cd-head-meta">
                    <span class="material-icons">person</span>
                    {{ $ownerName }}
                </span>
                <a class="cd-qbtn" href="{{ $board->public_url }}" target="_blank" style="width:auto;padding:9px 16px">
                    <span class="cd-qi"><span class="material-icons">open_in_new</span></span> Ver tablero público
                </a>
                <a class="cd-qbtn" href="{{ route('board.edit', $board->id) }}" style="width:auto;padding:9px 16px">
                    <span class="cd-qi"><span class="material-icons">edit</span></span> Editar tablero
                </a>
            </div>
        </div>
    </header>

    {{-- KPI Summary --}}
    @php
        $totalProps = $board->properties->count();
        $countInterested = $columns['interested']['properties']->count();
        $countVisited = $columns['visited']['properties']->count();
        $countDiscarded = $columns['discarded']['properties']->count();
        $countPending = $columns['properties']['properties']->count();
    @endphp

    <div class="cd-kpis" style="margin-bottom:var(--cd-gap)">
        <div class="cd-kpi" data-tone="accent">
            <div class="cd-kpi-ic"><span class="material-icons">home</span></div>
            <div><div class="cd-kpi-num cd-tnum">{{ $totalProps }}</div><div class="cd-kpi-lab">Total</div></div>
        </div>
        <div class="cd-kpi" data-tone="gold">
            <div class="cd-kpi-ic"><span class="material-icons">favorite</span></div>
            <div><div class="cd-kpi-num cd-tnum">{{ $countInterested }}</div><div class="cd-kpi-lab">Interesado</div></div>
        </div>
        <div class="cd-kpi" data-tone="green">
            <div class="cd-kpi-ic"><span class="material-icons">event_available</span></div>
            <div><div class="cd-kpi-num cd-tnum">{{ $countVisited }}</div><div class="cd-kpi-lab">Visitado</div></div>
        </div>
        <div class="cd-kpi" data-tone="red">
            <div class="cd-kpi-ic"><span class="material-icons">cancel</span></div>
            <div><div class="cd-kpi-num cd-tnum">{{ $countDiscarded }}</div><div class="cd-kpi-lab">Descartado</div></div>
        </div>
    </div>

    {{-- Kanban Board (Read-only) --}}
    @php
        $colColors = [
            'properties' => '--cd-s-nuevo',
            'interested' => '--cd-s-calificado',
            'visited' => '--cd-s-ganado',
            'discarded' => '--cd-s-perdido',
        ];
        $colIcons = [
            'properties' => 'home',
            'interested' => 'favorite',
            'visited' => 'event_available',
            'discarded' => 'cancel',
        ];
    @endphp

    <div class="cd-pipeline-board cd-rating-board">
        @foreach ($columns as $statusKey => $column)
            <div class="cd-pipeline-col">
                <div class="cd-pipeline-col-head">
                    <span class="cd-pipeline-col-dot" style="background:var({{ $colColors[$statusKey] }})"></span>
                    <span class="cd-pipeline-col-title">{{ $column['label'] }}</span>
                    <span class="cd-pipeline-col-count cd-tnum">{{ $column['properties']->count() }}</span>
                </div>
                <div class="cd-pipeline-col-body">
                    @forelse ($column['properties'] as $property)
                        <div class="cd-prop-card">
                            @if (!empty($property->formatted_images) && count($property->formatted_images) > 0)
                                <div class="cd-prop-card-img" style="background-image:url('{{ $property->formatted_images[0] }}')"></div>
                            @endif
                            <div class="cd-prop-card-body">
                                <div class="cd-prop-card-name">{{ $property->name }}</div>
                                @if ($property->price)
                                    <div class="cd-prop-card-price">
                                        @php
                                            $priceFormatted = '';
                                            try { $priceFormatted = $property->price_format; }
                                            catch (\Throwable $e) { $priceFormatted = number_format($property->price); }
                                        @endphp
                                        {{ $priceFormatted }}
                                    </div>
                                @endif
                                <div class="cd-prop-card-meta">
                                    @if ($property->location)
                                        <span><span class="material-icons">location_on</span> {{ \Illuminate\Support\Str::limit($property->location, 30) }}</span>
                                    @endif
                                </div>
                                <div class="cd-prop-card-meta">
                                    @if ($property->number_bedroom)
                                        <span><span class="material-icons">bed</span> {{ $property->number_bedroom }}</span>
                                    @endif
                                    @if ($property->number_bathroom)
                                        <span><span class="material-icons">bathtub</span> {{ $property->number_bathroom }}</span>
                                    @endif
                                    @if ($property->square)
                                        <span><span class="material-icons">square_foot</span> {{ $property->square }} m²</span>
                                    @endif
                                </div>
                            </div>
                            @if ($property->pivot && $property->pivot->notes)
                                <div class="cd-prop-card-notes">
                                    <span class="material-icons">notes</span>
                                    {{ $property->pivot->notes }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="cd-pipeline-empty" style="display:block">
                            <span class="material-icons">{{ $colIcons[$statusKey] }}</span>
                            Sin propiedades
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
