@php
    Theme::asset()->usePath()->add('client-board-css', 'css/client-board.css');
    Theme::asset()->container('footer')->usePath()->add('client-board-js', 'js/client-board.js');

    $clientName = $board->client ? $board->client->name : $board->name;
    $initials = collect(explode(' ', $clientName))->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('');
    $totalProperties = collect($columns)->sum(fn($col) => $col['properties']->count());
@endphp

<link rel="stylesheet" href="{{ Theme::asset()->url('css/client-board.css') }}">

{{-- ── DARK HERO BANNER ─────────────────────────────── --}}
<header class="cb-banner">
    <div class="cb-banner__bg">
        <img src="{{ theme_option('breadcrumb_background') ? RvMedia::url(theme_option('breadcrumb_background')) : Theme::asset()->url('images/banner-du-an.jpg') }}" alt="{{ $board->name }}" />
    </div>
    <div class="cb-banner__inner">
        <span class="cb-eyebrow">{{ __('Tablero de propiedades') }}</span>
        <div class="cb-banner__head cb-reveal-up">
            <span class="cb-client-ava--initials">{{ $initials }}</span>
            <div>
                <h1 class="cb-banner__title">{{ $board->name }}</h1>
                <p class="cb-banner__sub">
                    <span><b id="cb-totalCount">{{ $totalProperties }}</b> {{ __('propiedades en seguimiento') }}</span>
                    @if ($board->description)
                        <span class="cb-banner__dot"></span>
                        <span>{{ $board->description }}</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="cb-banner__share cb-reveal-up">
            <a href="{{ $board->whatsapp_share_url }}" target="_blank" class="cb-banner__share-btn">
                <span class="material-icons">chat</span> WhatsApp
            </a>
            <a href="{{ $board->email_share_url }}" class="cb-banner__share-btn">
                <span class="material-icons">mail</span> Email
            </a>
        </div>

        <div class="cb-banner__crumb cb-reveal-up">
            <a href="{{ route('public.index') }}">{{ __('Inicio') }}</a>
            <span class="material-icons">chevron_right</span>
            @if ($board->client)
                <span>{{ $board->client->name }}</span>
                <span class="material-icons">chevron_right</span>
            @endif
            <span class="cb-banner__crumb-cur">{{ __('Tablero') }}</span>
        </div>
    </div>
</header>

{{-- ── TABLERO KANBAN ───────────────────────────────── --}}
<section class="cb-board-sec">
    <div class="cb-wrap">
        <div class="cb-board-bar cb-reveal-up">
            <div>
                <span class="cb-eyebrow">{{ __('Clasificacion') }}</span>
                <h2>{{ __('Organiza tu busqueda') }}</h2>
            </div>
            <div class="cb-board-hint">
                <span class="material-icons">open_with</span>
                {{ __('Arrastra las tarjetas entre columnas para clasificar cada propiedad.') }}
            </div>
        </div>

        <div class="cb-board cb-reveal-up" id="cb-board">
            @foreach ($columns as $statusKey => $column)
                <section class="cb-col" data-status="{{ $statusKey }}">
                    <div class="cb-col-head">
                        <span class="cb-col-name">
                            <span class="cb-col-tag"></span>
                            {{ $column['label'] }}
                        </span>
                        <span class="cb-col-count" data-count>{{ $column['properties']->count() }}</span>
                    </div>
                    <div class="cb-col-body {{ $column['properties']->isEmpty() ? 'cb-is-empty' : '' }}" data-status="{{ $statusKey }}" id="column-{{ $statusKey }}">
                        @foreach ($column['properties'] as $property)
                            <article class="cb-pcard" draggable="true" data-property-id="{{ $property->id }}">
                                <div class="cb-pcard-media">
                                    @if (!empty($property->formatted_images) && count($property->formatted_images))
                                        <img src="{{ $property->formatted_images[0] }}" alt="{{ $property->name }}" loading="lazy" />
                                    @else
                                        <img src="{{ RvMedia::getDefaultImage() }}" alt="{{ $property->name }}" />
                                    @endif
                                    <span class="cb-pc-grab"><span class="material-icons">drag_indicator</span></span>
                                </div>
                                <div class="cb-pcard-body">
                                    <h4 class="cb-pcard-title">{{ $property->name }}</h4>
                                    <div class="cb-pcard-price">{{ $property->price_format }}</div>
                                    <div class="cb-pcard-specs">
                                        @if ($property->number_bedroom)
                                            <span class="cb-spec"><span class="material-icons">king_bed</span>{{ $property->number_bedroom }}</span>
                                        @endif
                                        @if ($property->number_bathroom)
                                            <span class="cb-spec"><span class="material-icons">bathtub</span>{{ $property->number_bathroom }}</span>
                                        @endif
                                        @if ($property->square)
                                            <span class="cb-spec"><span class="material-icons">square_foot</span>{{ $property->square_text }}</span>
                                        @endif
                                    </div>
                                </div>
                                @if ($property->url)
                                    <a class="cb-pcard-foot" href="{{ $property->url }}" target="_blank">
                                        <span class="material-icons">open_in_new</span> {{ trans('plugins/real-estate::board.view_property') }}
                                    </a>
                                @endif
                            </article>
                        @endforeach
                        <div class="cb-col-empty">
                            <span class="material-icons">open_with</span>
                            <span class="cb-col-empty__text">
                                @if ($statusKey !== 'properties')
                                    {{ trans('plugins/real-estate::board.kanban.drop_here') }}
                                @else
                                    {{ trans('plugins/real-estate::board.no_properties') }}
                                @endif
                            </span>
                        </div>
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</section>

<div class="cb-toast" id="cb-toast"></div>

<script>
(function() {
    var updateUrl = @json(route('public.board.update-status', $board->token));
    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var statusLabels = @json($statuses);
    var draggedCard = null;

    function refreshCounts() {
        var total = 0;
        document.querySelectorAll('.cb-col').forEach(function(col) {
            var body = col.querySelector('.cb-col-body');
            var n = body.querySelectorAll('.cb-pcard').length;
            col.querySelector('.cb-col-count').textContent = n;
            body.classList.toggle('cb-is-empty', n === 0);
            total += n;
        });
        var tc = document.getElementById('cb-totalCount');
        if (tc) tc.textContent = total;
    }

    function afterElement(body, y) {
        var cards = Array.from(body.querySelectorAll('.cb-pcard:not(.cb-dragging)'));
        var closest = { dist: -Infinity, el: null };
        for (var i = 0; i < cards.length; i++) {
            var box = cards[i].getBoundingClientRect();
            var offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.dist) {
                closest = { dist: offset, el: cards[i] };
            }
        }
        return closest.el;
    }

    function showToast(message, isError) {
        var toast = document.getElementById('cb-toast');
        toast.textContent = message;
        toast.style.background = isError ? '#ef4444' : '#16181d';
        toast.classList.add('cb-toast--show');
        setTimeout(function() { toast.classList.remove('cb-toast--show'); }, 2500);
    }

    // Desktop drag & drop
    document.querySelectorAll('.cb-pcard').forEach(function(card) {
        card.addEventListener('dragstart', function(e) {
            draggedCard = this;
            this.classList.add('cb-dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', this.dataset.propertyId);
        });

        card.addEventListener('dragend', function() {
            this.classList.remove('cb-dragging');
            document.querySelectorAll('.cb-col.cb-drop-active').forEach(function(c) {
                c.classList.remove('cb-drop-active');
            });
            draggedCard = null;
            refreshCounts();
        });

        card.querySelectorAll('.cb-pcard-foot').forEach(function(link) {
            link.addEventListener('dragstart', function(e) {
                e.preventDefault();
                e.stopPropagation();
            });
        });
    });

    document.querySelectorAll('.cb-col').forEach(function(col) {
        var body = col.querySelector('.cb-col-body');

        col.addEventListener('dragover', function(e) {
            if (!draggedCard) return;
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            col.classList.add('cb-drop-active');
            var after = afterElement(body, e.clientY);
            var emptyEl = body.querySelector('.cb-col-empty');
            if (after == null) body.insertBefore(draggedCard, emptyEl);
            else body.insertBefore(draggedCard, after);
        });

        col.addEventListener('dragleave', function(e) {
            if (!col.contains(e.relatedTarget)) col.classList.remove('cb-drop-active');
        });

        col.addEventListener('drop', function(e) {
            e.preventDefault();
            col.classList.remove('cb-drop-active');

            if (!draggedCard) return;

            var propertyId = draggedCard.dataset.propertyId;
            var newStatus = body.dataset.status;
            var oldBody = draggedCard._oldBody || body;
            var oldStatus = oldBody.dataset.status;

            if (oldStatus === newStatus) {
                refreshCounts();
                return;
            }

            refreshCounts();

            fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    property_id: parseInt(propertyId),
                    status: newStatus,
                }),
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.error) {
                    showToast('Error: ' + data.message, true);
                } else {
                    showToast(statusLabels[newStatus] || newStatus);
                }
            })
            .catch(function() {
                showToast('Error de conexion', true);
            });
        });
    });

    // Track original column on dragstart for proper status detection
    document.querySelectorAll('.cb-pcard').forEach(function(card) {
        card.addEventListener('dragstart', function() {
            this._oldBody = this.closest('.cb-col-body');
        });
    });

    // Touch support for mobile
    var touchCard = null;
    var touchClone = null;
    var touchStartX, touchStartY;

    document.querySelectorAll('.cb-pcard').forEach(function(card) {
        card.addEventListener('touchstart', function(e) {
            if (e.target.closest('.cb-pcard-foot')) return;
            touchCard = this;
            var touch = e.touches[0];
            touchStartX = touch.clientX;
            touchStartY = touch.clientY;
        }, { passive: true });
    });

    document.addEventListener('touchmove', function(e) {
        if (!touchCard) return;

        var touch = e.touches[0];
        var dx = Math.abs(touch.clientX - touchStartX);
        var dy = Math.abs(touch.clientY - touchStartY);

        if (dx < 10 && dy < 10) return;

        e.preventDefault();

        if (!touchClone) {
            touchCard._oldBody = touchCard.closest('.cb-col-body');
            touchClone = touchCard.cloneNode(true);
            touchClone.style.position = 'fixed';
            touchClone.style.zIndex = '10000';
            touchClone.style.width = touchCard.offsetWidth + 'px';
            touchClone.style.opacity = '0.85';
            touchClone.style.pointerEvents = 'none';
            touchClone.style.transform = 'rotate(3deg)';
            touchClone.style.boxShadow = '0 8px 25px rgba(0,0,0,0.3)';
            document.body.appendChild(touchClone);
            touchCard.style.opacity = '0.3';
        }

        touchClone.style.left = (touch.clientX - touchCard.offsetWidth / 2) + 'px';
        touchClone.style.top = (touch.clientY - 30) + 'px';

        document.querySelectorAll('.cb-col').forEach(function(c) {
            c.classList.remove('cb-drop-active');
        });
        var elBelow = document.elementFromPoint(touch.clientX, touch.clientY);
        if (elBelow) {
            var zone = elBelow.closest('.cb-col');
            if (zone) zone.classList.add('cb-drop-active');
        }
    }, { passive: false });

    document.addEventListener('touchend', function(e) {
        if (!touchCard) return;

        if (touchClone) {
            var touch = e.changedTouches[0];
            document.body.removeChild(touchClone);
            touchCard.style.opacity = '1';

            var elBelow = document.elementFromPoint(touch.clientX, touch.clientY);
            if (elBelow) {
                var zone = elBelow.closest('.cb-col');
                if (zone) {
                    var dropBody = zone.querySelector('.cb-col-body');
                    var newStatus = dropBody.dataset.status;
                    var oldBody = touchCard._oldBody || touchCard.closest('.cb-col-body');
                    var oldStatus = oldBody.dataset.status;

                    if (oldStatus !== newStatus) {
                        dropBody.insertBefore(touchCard, dropBody.querySelector('.cb-col-empty'));
                        refreshCounts();

                        fetch(updateUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({
                                property_id: parseInt(touchCard.dataset.propertyId),
                                status: newStatus,
                            }),
                        })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            if (!data.error) {
                                showToast(statusLabels[newStatus] || newStatus);
                            }
                        });
                    }
                }
            }
        }

        document.querySelectorAll('.cb-col').forEach(function(c) {
            c.classList.remove('cb-drop-active');
        });

        touchClone = null;
        touchCard = null;
    });

    refreshCounts();
})();
</script>
