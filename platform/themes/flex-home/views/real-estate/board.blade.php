@php
    Theme::asset()->add(
        'board-inline-css',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    );
@endphp

<style>
    :root {
        --col-properties: #f9f9f9;
        --col-interested: #f9f9f9;
        --col-visited: #f9f9f9;
        --col-discarded: #f9f9f9;
    }

    .board-header-section {
        background: #f9f9f9;
        color: #000;
        padding: 1.5rem 0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        margin-top: -1px;
    }

    .btn-blue {
        background: #007bff;
        color: #fff;
        border-radius: .5rem !important;
    }

    .board-header-section h1 {
        font-size: 1.5rem;
        margin: 0;
        font-weight: 700;
    }

    .board-header-section .board-meta {
        opacity: 0.8;
        font-size: 0.875rem;
    }

    .kanban-board {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        padding: 1rem 0;
        min-height: calc(100vh - 350px);
    }

    @media (max-width: 1200px) {
        .kanban-board {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .kanban-board {
            grid-template-columns: 1fr;
        }
    }

    .kanban-column {
        background: #e2e8f0;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        min-height: 300px;
    }

    .kanban-column-header {
        padding: 0.875rem 1rem;
        border-radius: 12px 12px 0 0;
        font-weight: 700;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: #000000;
    }

    .kanban-column[data-status="properties"] .kanban-column-header {
        background: var(--col-properties);
    }

    .kanban-column[data-status="interested"] .kanban-column-header {
        background: var(--col-interested);
    }

    .kanban-column[data-status="visited"] .kanban-column-header {
        background: var(--col-visited);
    }

    .kanban-column[data-status="discarded"] .kanban-column-header {
        background: var(--col-discarded);
    }

    .kanban-column-count {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        width: 26px;
        height: 26px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
    }

    .kanban-column-body {
        padding: 0.75rem;
        flex: 1;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 0.75rem;
        align-content: start;
        min-height: 120px;
        transition: background 0.2s;
    }

    .kanban-column-body.drag-over {
        background: rgba(0, 0, 0, 0.06);
        border-radius: 0 0 12px 12px;
    }

    .property-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 1px 6px rgba(0, 0, 0, 0.08);
        cursor: grab;
        transition: transform 0.15s, box-shadow 0.15s, opacity 0.15s;
        user-select: none;
    }

    .property-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
    }

    .property-card.dragging {
        opacity: 0.5;
        transform: rotate(2deg);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }

    .property-card-img {
        width: 100%;
        height: 120px;
        object-fit: cover;
    }

    .property-card-body {
        padding: 0.625rem 0.75rem;
    }

    .property-card-title {
        font-size: 0.8rem;
        font-weight: 600;
        margin: 0 0 0.25rem;
        color: #1e293b;
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .property-card-price {
        font-size: 0.85rem;
        font-weight: 700;
        color: #2563eb;
        margin: 0 0 0.25rem;
    }

    .property-card-meta {
        display: flex;
        gap: 0.5rem;
        font-size: 0.7rem;
        color: #94a3b8;
        flex-wrap: wrap;
    }

    .property-card-meta span {
        display: flex;
        align-items: center;
        gap: 2px;
    }

    .property-card-link {
        display: block;
        text-align: center;
        font-size: 0.7rem;
        padding: 0.35rem;
        background: #f8fafc;
        color: #64748b;
        text-decoration: none;
        border-top: 1px solid #f1f5f9;
        transition: background 0.15s;
    }

    .property-card-link:hover {
        background: #e2e8f0;
        color: #334155;
    }

    .toast-notification {
        position: fixed;
        bottom: 1.5rem;
        right: 1.5rem;
        background: #1e293b;
        color: white;
        padding: 0.75rem 1.25rem;
        border-radius: 8px;
        font-size: 0.875rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        transform: translateY(100px);
        opacity: 0;
        transition: all 0.3s;
        z-index: 9999;
    }

    .toast-notification.show {
        transform: translateY(0);
        opacity: 1;
    }

    .kanban-empty {
        grid-column: 1 / -1;
        text-align: center;
        padding: 2rem;
        color: #94a3b8;
        font-size: 0.85rem;
    }
</style>

<div class="board-header-section">
    <div class="container-fluid px-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h1>{{ $board->name }}</h1>
                @if ($board->description)
                    <p class="board-meta mb-0 mt-1">{{ $board->description }}</p>
                @endif
                @if ($board->client)
                    <p class="board-meta mb-0 mt-1">
                        {{ trans('plugins/real-estate::board.prepared_for') }}: {{ $board->client->name }}
                    </p>
                @endif
            </div>
            <div class="d-flex">
                <a href="{{ $board->whatsapp_share_url }}" target="_blank" class="btn btn-blue btn-sm mr-2">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
                <a href="{{ $board->email_share_url }}" class="btn btn-blue btn-sm">
                    <i class="fas fa-envelope"></i> Email
                </a>
            </div>

        </div>
    </div>
</div>

<div class="container-fluid px-4 py-3">
    <div class="kanban-board">
        @foreach ($columns as $statusKey => $column)
            <div class="kanban-column" data-status="{{ $statusKey }}">
                <div class="kanban-column-header">
                    <span>{{ $column['label'] }}</span>
                    <span class="kanban-column-count" data-count>{{ $column['properties']->count() }}</span>
                </div>
                <div class="kanban-column-body" data-status="{{ $statusKey }}" id="column-{{ $statusKey }}">
                    @forelse($column['properties'] as $property)
                        <div class="property-card" draggable="true" data-property-id="{{ $property->id }}">
                            @if (!empty($property->formatted_images) && count($property->formatted_images))
                                <img src="{{ $property->formatted_images[0] }}" class="property-card-img"
                                    alt="{{ $property->name }}" loading="lazy">
                            @else
                                <img src="{{ RvMedia::getDefaultImage() }}" class="property-card-img"
                                    alt="{{ $property->name }}">
                            @endif
                            <div class="property-card-body">
                                <h6 class="property-card-title">{{ $property->name }}</h6>
                                <div class="property-card-price">{{ $property->price_format }}</div>
                                <div class="property-card-meta">
                                    @if ($property->number_bedroom)
                                        <span><i class="fas fa-bed"></i> {{ $property->number_bedroom }}</span>
                                    @endif
                                    @if ($property->number_bathroom)
                                        <span><i class="fas fa-bath"></i> {{ $property->number_bathroom }}</span>
                                    @endif
                                    @if ($property->square)
                                        <span><i class="fas fa-ruler-combined"></i> {{ $property->square_text }}</span>
                                    @endif
                                </div>
                            </div>
                            @if ($property->url)
                                <a href="{{ $property->url }}" target="_blank" class="property-card-link">
                                    <i class="fas fa-external-link-alt me-1"></i>
                                    {{ trans('plugins/real-estate::board.view_property') }}
                                </a>
                            @endif
                        </div>
                    @empty
                        <div class="kanban-empty">
                            @if ($statusKey !== 'properties')
                                <i class="fas fa-arrows-alt mb-2 d-block"></i>
                                {{ trans('plugins/real-estate::board.kanban.drop_here') }}
                            @else
                                {{ trans('plugins/real-estate::board.no_properties') }}
                            @endif
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="toast-notification" id="toast"></div>

<script>
    (function() {
        var updateUrl = @json(route('public.board.update-status', $board->token));
        var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        var statusLabels = @json($statuses);
        var draggedCard = null;

        document.querySelectorAll('.property-card').forEach(function(card) {
            card.addEventListener('dragstart', function(e) {
                draggedCard = this;
                this.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', this.dataset.propertyId);
            });

            card.addEventListener('dragend', function() {
                this.classList.remove('dragging');
                document.querySelectorAll('.kanban-column-body').forEach(function(col) {
                    col.classList.remove('drag-over');
                });
                draggedCard = null;
            });

            card.querySelectorAll('.property-card-link').forEach(function(link) {
                link.addEventListener('dragstart', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                });
            });
        });

        document.querySelectorAll('.kanban-column-body').forEach(function(zone) {
            zone.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                this.classList.add('drag-over');
            });

            zone.addEventListener('dragleave', function(e) {
                if (!this.contains(e.relatedTarget)) {
                    this.classList.remove('drag-over');
                }
            });

            zone.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');

                if (!draggedCard) return;

                var dropZone = this;
                var propertyId = draggedCard.dataset.propertyId;
                var newStatus = dropZone.dataset.status;
                var oldColumn = draggedCard.closest('.kanban-column-body');
                var oldStatus = oldColumn.dataset.status;

                if (oldStatus === newStatus) return;

                var emptyEl = dropZone.querySelector('.kanban-empty');
                if (emptyEl) emptyEl.remove();

                dropZone.appendChild(draggedCard);

                if (oldColumn.querySelectorAll('.property-card').length === 0) {
                    var empty = document.createElement('div');
                    empty.className = 'kanban-empty';
                    if (oldStatus !== 'properties') {
                        empty.innerHTML =
                            '<i class="fas fa-arrows-alt mb-2 d-block"></i> Drag properties here';
                    } else {
                        empty.textContent = 'No properties';
                    }
                    oldColumn.appendChild(empty);
                }

                updateCounts();

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
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(data) {
                        if (data.error) {
                            showToast('Error: ' + data.message, true);
                            oldColumn.appendChild(draggedCard);
                            var emp = dropZone.querySelector('.kanban-empty');
                            if (!emp && dropZone.querySelectorAll('.property-card').length ===
                                0) {
                                var e2 = document.createElement('div');
                                e2.className = 'kanban-empty';
                                e2.innerHTML = '<i class="fas fa-arrows-alt mb-2 d-block"></i>';
                                dropZone.appendChild(e2);
                            }
                            updateCounts();
                        } else {
                            showToast('✓ ' + (statusLabels[newStatus] || newStatus));
                        }
                    })
                    .catch(function() {
                        showToast('Connection error', true);
                    });
            });
        });

        function updateCounts() {
            document.querySelectorAll('.kanban-column').forEach(function(col) {
                var count = col.querySelectorAll('.property-card').length;
                var badge = col.querySelector('[data-count]');
                if (badge) badge.textContent = count;
            });
        }

        function showToast(message, isError) {
            var toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.background = isError ? '#ef4444' : '#1e293b';
            toast.classList.add('show');
            setTimeout(function() {
                toast.classList.remove('show');
            }, 2500);
        }

        // Touch support for mobile
        var touchCard = null;
        var touchClone = null;
        var touchStartX, touchStartY;

        document.querySelectorAll('.property-card').forEach(function(card) {
            card.addEventListener('touchstart', function(e) {
                if (e.target.closest('.property-card-link')) return;
                touchCard = this;
                var touch = e.touches[0];
                touchStartX = touch.clientX;
                touchStartY = touch.clientY;
            }, {
                passive: true
            });
        });

        document.addEventListener('touchmove', function(e) {
            if (!touchCard) return;

            var touch = e.touches[0];
            var dx = Math.abs(touch.clientX - touchStartX);
            var dy = Math.abs(touch.clientY - touchStartY);

            if (dx < 10 && dy < 10) return;

            e.preventDefault();

            if (!touchClone) {
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

            document.querySelectorAll('.kanban-column-body').forEach(function(col) {
                col.classList.remove('drag-over');
            });
            var elBelow = document.elementFromPoint(touch.clientX, touch.clientY);
            if (elBelow) {
                var zone = elBelow.closest('.kanban-column-body');
                if (zone) zone.classList.add('drag-over');
            }
        }, {
            passive: false
        });

        document.addEventListener('touchend', function(e) {
            if (!touchCard) return;

            if (touchClone) {
                var touch = e.changedTouches[0];
                document.body.removeChild(touchClone);
                touchCard.style.opacity = '1';

                var elBelow = document.elementFromPoint(touch.clientX, touch.clientY);
                if (elBelow) {
                    var zone = elBelow.closest('.kanban-column-body');
                    if (zone) {
                        var newStatus = zone.dataset.status;
                        var oldColumn = touchCard.closest('.kanban-column-body');
                        var oldStatus = oldColumn.dataset.status;

                        if (oldStatus !== newStatus) {
                            var emptyEl = zone.querySelector('.kanban-empty');
                            if (emptyEl) emptyEl.remove();

                            zone.appendChild(touchCard);

                            if (oldColumn.querySelectorAll('.property-card').length === 0) {
                                var empty = document.createElement('div');
                                empty.className = 'kanban-empty';
                                empty.innerHTML = '<i class="fas fa-arrows-alt mb-2 d-block"></i>';
                                oldColumn.appendChild(empty);
                            }

                            updateCounts();

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
                                .then(function(r) {
                                    return r.json();
                                })
                                .then(function(data) {
                                    if (!data.error) {
                                        showToast('✓ ' + (statusLabels[newStatus] || newStatus));
                                    }
                                });
                        }
                    }
                }
            }

            document.querySelectorAll('.kanban-column-body').forEach(function(col) {
                col.classList.remove('drag-over');
            });

            touchClone = null;
            touchCard = null;
        });
    })();
</script>
