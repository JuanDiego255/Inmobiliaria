<div class="modal fade" id="propertyPickerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-full">
        <div class="modal-content">
            <div class="modal-header border-bottom" style="background: #f8f9fa;">
                <div class="d-flex align-items-center gap-3 w-100">
                    <h5 class="modal-title mb-0">
                        <i class="fas fa-plus-circle me-1"></i>
                        {{ trans('plugins/real-estate::board.add_properties') }}
                    </h5>
                    <span id="picker-selected-count" class="badge bg-primary d-none">0</span>
                    <div class="ms-auto d-flex gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            {{ trans('core/base::forms.cancel') }}
                        </button>
                        <button type="button" class="btn btn-primary" id="btn-bulk-add" disabled>
                            <i class="fas fa-plus me-1"></i>
                            <span id="btn-bulk-add-text">{{ trans('plugins/real-estate::board.add_selected') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-body p-0">
                {{-- Smart filter banner --}}
                <div id="smart-filter-banner" class="d-none px-4 py-2" style="background: #e8f4fd; border-bottom: 1px solid #bee3f8;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-magic text-primary"></i>
                        <span class="fw-medium">{{ trans('plugins/real-estate::board.smart_filter_active') }}</span>
                        <small id="smart-filter-notes" class="text-muted ms-1"></small>
                        <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" id="btn-clear-smart-filter">
                            <i class="fas fa-times me-1"></i>{{ trans('plugins/real-estate::board.clear_filters') }}
                        </button>
                    </div>
                </div>

                {{-- Filters bar --}}
                <div class="px-4 py-3 border-bottom" style="background: #fff;">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <input type="text" class="form-control" id="picker-search" placeholder="{{ trans('plugins/real-estate::board.search_properties') }}">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="picker-type">
                                <option value="">{{ trans('plugins/real-estate::board.all_types') }}</option>
                                @foreach(\Botble\RealEstate\Enums\PropertyTypeEnum::labels() as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1">
                            <select class="form-select" id="picker-bedrooms">
                                <option value="">{{ trans('plugins/real-estate::board.filter_bedrooms') }}</option>
                                @for($i = 1; $i <= 6; $i++)
                                    <option value="{{ $i }}">{{ $i }}+</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-1">
                            <select class="form-select" id="picker-bathrooms">
                                <option value="">{{ trans('plugins/real-estate::board.filter_bathrooms') }}</option>
                                @for($i = 1; $i <= 5; $i++)
                                    <option value="{{ $i }}">{{ $i }}+</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="number" class="form-control" id="picker-price-min" placeholder="{{ trans('plugins/real-estate::board.filter_price_min') }}">
                        </div>
                        <div class="col-md-2">
                            <input type="number" class="form-control" id="picker-price-max" placeholder="{{ trans('plugins/real-estate::board.filter_price_max') }}">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-secondary w-100" id="btn-clear-filters" title="{{ trans('plugins/real-estate::board.clear_filters') }}">
                                <i class="fas fa-undo"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Property grid --}}
                <div class="px-4 py-3" style="overflow-y: auto; height: calc(100vh - 220px); background: #f4f5f7;">
                    <div id="picker-loading" class="text-center py-5 d-none">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">{{ trans('plugins/real-estate::board.loading') }}</p>
                    </div>
                    <div id="picker-grid" class="row g-3"></div>
                    <div id="picker-empty" class="text-center py-5 d-none">
                        <i class="fas fa-search fa-2x text-muted mb-2"></i>
                        <p class="text-muted">{{ trans('plugins/real-estate::board.no_results') }}</p>
                    </div>
                    <div id="picker-load-more" class="text-center py-3 d-none">
                        <button type="button" class="btn btn-outline-primary" id="btn-load-more">
                            <i class="fas fa-chevron-down me-1"></i> Load more
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.property-pick-card {
    border: 2px solid transparent;
    border-radius: 10px;
    overflow: hidden;
    background: #fff;
    cursor: pointer;
    transition: border-color 0.15s, box-shadow 0.15s;
    position: relative;
}
.property-pick-card:hover {
    border-color: #93c5fd;
    box-shadow: 0 2px 8px rgba(59,130,246,0.12);
}
.property-pick-card.selected {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
}
.property-pick-card.already-added {
    opacity: 0.5;
    pointer-events: none;
}
.property-pick-card .pick-checkbox {
    position: absolute;
    top: 8px;
    left: 8px;
    width: 22px;
    height: 22px;
    background: rgba(255,255,255,0.9);
    border: 2px solid #d1d5db;
    border-radius: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
    transition: all 0.15s;
}
.property-pick-card.selected .pick-checkbox {
    background: #3b82f6;
    border-color: #3b82f6;
    color: #fff;
}
.property-pick-card .pick-image {
    width: 100%;
    aspect-ratio: 4 / 3;
    object-fit: cover;
}
.property-pick-card .pick-body {
    padding: 12px 14px;
}
.property-pick-card .pick-name {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.3;
}
.property-pick-card .pick-price {
    font-size: 14px;
    font-weight: 700;
    color: #3b82f6;
    margin-top: 2px;
}
.property-pick-card .pick-meta {
    font-size: 12px;
    color: #94a3b8;
    display: flex;
    gap: 10px;
    margin-top: 5px;
}
.property-pick-card .pick-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    background: rgba(0,0,0,0.6);
    color: #fff;
    font-size: 10px;
    padding: 2px 8px;
    border-radius: 4px;
    z-index: 2;
}
.property-pick-card .already-badge {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0,0,0,0.7);
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    padding: 6px 14px;
    border-radius: 6px;
    z-index: 3;
}
</style>

<script>
    window.__propertyPickerConfig = {
        searchUrl: '{{ route("board.search-properties") }}',
        bulkAddUrl: '{{ route("board.bulk-add-properties") }}',
        csrfToken: '{{ csrf_token() }}',
        boardId: {{ $board->id }},
        clientNotes: @json($board->client?->notes ?? ''),
        translations: {
            selectedCount: '{{ trans("plugins/real-estate::board.selected_count") }}',
            addSelected: '{{ trans("plugins/real-estate::board.add_selected") }}',
            adding: '{{ trans("plugins/real-estate::board.adding") }}',
            alreadyInBoard: '{{ trans("plugins/real-estate::board.already_in_board") }}',
            smartFilterNotes: '{{ trans("plugins/real-estate::board.smart_filter_notes") }}',
        }
    };
</script>
