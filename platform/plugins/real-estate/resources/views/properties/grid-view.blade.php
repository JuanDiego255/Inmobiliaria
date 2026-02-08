<div class="property-grid-wrapper">
    <div class="property-grid-toolbar mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="property-grid-count text-muted"></span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="input-group input-group-sm" style="width: 250px;">
                <input type="text" class="form-control" id="grid-search" placeholder="{{ trans('plugins/real-estate::property.grid.search_placeholder') }}">
                <button class="btn btn-outline-secondary" type="button" id="grid-search-btn">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            <select class="form-select form-select-sm" id="grid-status-filter" style="width: 150px;">
                <option value="">{{ trans('plugins/real-estate::property.grid.all_statuses') }}</option>
                <option value="selling">{{ trans('plugins/real-estate::property.grid.selling') }}</option>
                <option value="sold">{{ trans('plugins/real-estate::property.grid.sold') }}</option>
                <option value="renting">{{ trans('plugins/real-estate::property.grid.renting') }}</option>
                <option value="rented">{{ trans('plugins/real-estate::property.grid.rented') }}</option>
            </select>
            <select class="form-select form-select-sm" id="grid-sort" style="width: 150px;">
                <option value="created_at-desc">{{ trans('plugins/real-estate::property.grid.newest') }}</option>
                <option value="created_at-asc">{{ trans('plugins/real-estate::property.grid.oldest') }}</option>
                <option value="name-asc">{{ trans('plugins/real-estate::property.grid.name_asc') }}</option>
                <option value="name-desc">{{ trans('plugins/real-estate::property.grid.name_desc') }}</option>
                <option value="price-asc">{{ trans('plugins/real-estate::property.grid.price_asc') }}</option>
                <option value="price-desc">{{ trans('plugins/real-estate::property.grid.price_desc') }}</option>
            </select>
        </div>
    </div>

    <div class="property-grid" id="property-grid">
        <div class="property-grid-loading text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
        </div>
    </div>

    <div class="property-grid-pagination d-flex justify-content-center mt-4" id="grid-pagination"></div>
</div>

{{-- Add to Board modal for grid view --}}
<div class="modal fade" id="gridAddToBoardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-clipboard-list me-1"></i>
                    {{ trans('plugins/real-estate::board.add_property_to_board') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="grid-board-loading" class="text-center py-3">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
                <div id="grid-board-content" style="display: none;">
                    <div class="mb-3">
                        <label class="form-label">{{ trans('plugins/real-estate::board.select_board') }}</label>
                        <select class="form-control" id="grid-board-select">
                            <option value="">-- {{ trans('plugins/real-estate::board.select_board') }} --</option>
                        </select>
                    </div>
                </div>
                <div id="grid-board-result" class="mt-2" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ trans('core/base::forms.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="grid-btn-add-to-board" disabled>
                    <i class="fas fa-plus"></i> {{ trans('plugins/real-estate::board.add_to_board') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.propertyGridConfig = {
        ajaxUrl: '{{ route("property.grid-data") }}',
        boardsUrl: '{{ route("board.get-boards-for-property") }}',
        addToBoardUrl: '{{ route("board.add-property-to-board") }}',
        editUrlTemplate: '{{ route("property.edit", ":id") }}',
        deleteUrlTemplate: '{{ route("property.destroy", ":id") }}',
        csrfToken: '{{ csrf_token() }}',
        perPage: 18,
        translations: {
            confirmDelete: '{{ trans("core/base::tables.confirm_delete") }}',
            noProperties: '{{ trans("plugins/real-estate::property.grid.no_properties") }}',
            showing: '{{ trans("plugins/real-estate::property.grid.showing") }}',
            of: '{{ trans("plugins/real-estate::property.grid.of") }}',
            properties: '{{ trans("plugins/real-estate::property.grid.properties_label") }}',
        }
    };
</script>
