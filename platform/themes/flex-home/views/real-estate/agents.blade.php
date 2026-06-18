@php
    $palette = ['#717fe0','#7cb342','#66a8a6','#e08a3e','#d4708a','#5b8def','#d6a23e','#4fa3a1'];
    $colorFor = function($name) use ($palette) {
        $h = 0;
        for ($i = 0; $i < mb_strlen($name); $i++) {
            $h = (($h * 31) + mb_ord(mb_substr($name, $i, 1))) & 0xFFFFFFFF;
        }
        return $palette[$h % count($palette)];
    };
@endphp

{{-- Dark hero banner --}}
<div class="ag-banner">
    <div class="ag-banner__inner">
        <span class="ag-banner__eyebrow">{{ __('Nuestro equipo') }}</span>
        <h1 class="ag-banner__title">{{ __('Conocé a nuestros') }} <em>{{ __('Agentes') }}</em></h1>
        <p class="ag-banner__text">{{ __('Profesionales con experiencia local listos para ayudarte a comprar, vender o invertir.') }}</p>
        <div class="ag-banner__scroll">
            <span class="ag-banner__dot"><span class="material-icons">arrow_downward</span></span>
            {{ __('Explorar agentes') }}
        </div>
    </div>
</div>

<section class="ag-sec" id="agAgentsList">
    <div class="ag-wrap">
        <div class="ag-sec-head ag-reveal-up">
            <span class="ag-eyebrow">{{ __('Agentes inmobiliarios') }}</span>
            <h2>{{ __('Encontrá al asesor ideal') }}</h2>
        </div>

        @if($accounts->isNotEmpty())
            <div class="ag-toolbar ag-reveal-up">
                <span class="ag-count-pill">
                    <span class="material-icons">groups</span>
                    <b id="agAgentCount">{{ $accounts->total() }}</b>&nbsp;{{ __('agentes disponibles') }}
                </span>
                <div class="ag-search">
                    <span class="material-icons">search</span>
                    <input type="text" id="agAgentSearch" placeholder="{{ __('Buscar por nombre…') }}" aria-label="{{ __('Buscar agente') }}">
                </div>
            </div>

            <div class="ag-agents-grid" id="agAgentGrid">
                @foreach($accounts as $account)
                    @php
                        $c = $colorFor($account->name);
                        $hasPhoto = $account->avatar && $account->avatar->url;
                        $initial = mb_strtoupper(mb_substr($account->name, 0, 1));
                    @endphp
                    <a class="ag-agent-card" href="{{ $account->url ?? '#' }}" style="--c: {{ $c }}" data-name="{{ mb_strtolower($account->name) }}">
                        <div class="ag-agent-cover"><span class="material-icons">apartment</span></div>
                        <div class="ag-agent-avatar" style="--c: {{ $c }}">
                            @if($hasPhoto)
                                <img src="{{ RvMedia::getImageUrl($account->avatar->url, 'thumb') }}" alt="{{ $account->name }}">
                            @else
                                <span class="ag-agent-initial">{{ $initial }}</span>
                            @endif
                        </div>
                        <div class="ag-agent-body">
                            <span class="ag-agent-role">{{ __('Asesor inmobiliario') }}</span>
                            <h3 class="ag-agent-name">{{ $account->name }}</h3>
                            <div class="ag-agent-contact">
                                @if($account->phone && !setting('real_estate_hide_agency_phone', 0))
                                    <div class="ag-ac-row">
                                        <span class="ag-ac-ico"><span class="material-icons">call</span></span>
                                        <div class="ag-ac-text">
                                            <div class="ag-ac-k">{{ __('Teléfono') }}</div>
                                            <div class="ag-ac-v">{{ $account->phone }}</div>
                                        </div>
                                    </div>
                                @endif
                                @if($account->email && !setting('real_estate_hide_agency_email', 0))
                                    <div class="ag-ac-row">
                                        <span class="ag-ac-ico"><span class="material-icons">mail</span></span>
                                        <div class="ag-ac-text">
                                            <div class="ag-ac-k">{{ __('Correo') }}</div>
                                            <div class="ag-ac-v">{{ $account->email }}</div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="ag-agent-foot">
                            <span class="ag-agent-props">
                                <span class="material-icons">home</span>
                                <b>{{ $account->properties_count }}</b>&nbsp;{{ $account->properties_count == 1 ? __('propiedad') : __('propiedades') }}
                            </span>
                            <span class="ag-agent-cta">{{ __('Ver perfil') }} <span class="material-icons">north_east</span></span>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="ag-empty" id="agAgentEmpty">
                <span class="material-icons">person_search</span>
                {{ __('No encontramos agentes con ese nombre.') }}
            </div>

            @if($accounts->hasPages())
                <div class="ag-pagination">
                    <nav aria-label="Page navigation">
                        {!! $accounts->withQueryString()->links() !!}
                    </nav>
                </div>
            @endif
        @else
            <div class="ag-empty" style="display: block;">
                <span class="material-icons">person_search</span>
                {{ __('No hay agentes disponibles en este momento.') }}
            </div>
        @endif
    </div>
</section>
