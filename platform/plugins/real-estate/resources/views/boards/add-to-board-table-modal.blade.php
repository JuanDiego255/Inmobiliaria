<div class="modal fade" id="addToBoardTableModal" tabindex="-1" aria-labelledby="addToBoardTableModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addToBoardTableModalLabel">
                    <i class="fas fa-clipboard-list me-1"></i>
                    {{ trans('plugins/real-estate::board.add_property_to_board') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="table-board-loading" class="text-center py-3">
                    <i class="fas fa-spinner fa-spin"></i> {{ trans('plugins/real-estate::board.loading') }}
                </div>
                <div id="table-board-content" style="display: none;">
                    <div class="mb-3">
                        <label for="table-board-select" class="form-label">{{ trans('plugins/real-estate::board.select_board') }}</label>
                        <select class="form-control" id="table-board-select">
                            <option value="">-- {{ trans('plugins/real-estate::board.select_board') }} --</option>
                        </select>
                    </div>
                </div>
                <div id="table-board-result" class="mt-2" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ trans('core/base::forms.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="btn-table-add-to-board" disabled>
                    <i class="fas fa-plus"></i> {{ trans('plugins/real-estate::board.add_to_board') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.__addToBoardConfig = {
        getBoardsUrl: '{{ route("board.get-boards-for-property") }}',
        addToBoardUrl: '{{ route("board.add-property-to-board") }}',
        csrfToken: '{{ csrf_token() }}',
        translations: {
            selectBoard: '{{ trans("plugins/real-estate::board.select_board") }}',
            addToBoard: '{{ trans("plugins/real-estate::board.add_to_board") }}',
            adding: '{{ trans("plugins/real-estate::board.adding") }}',
            loading: '{{ trans("plugins/real-estate::board.loading") }}'
        }
    };
</script>
