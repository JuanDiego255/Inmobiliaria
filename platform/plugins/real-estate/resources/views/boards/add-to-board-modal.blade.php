<div class="modal fade" id="addToBoardModal" tabindex="-1" aria-labelledby="addToBoardModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addToBoardModalLabel">
                    <i class="fas fa-clipboard-list me-1"></i>
                    {{ trans('plugins/real-estate::board.add_property_to_board') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="board-list-loading" class="text-center py-3">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
                <div id="board-list-content" style="display: none;">
                    <div class="mb-3">
                        <label for="board-select" class="form-label">{{ trans('plugins/real-estate::board.select_board') }}</label>
                        <select class="form-control" id="board-select">
                            <option value="">-- {{ trans('plugins/real-estate::board.select_board') }} --</option>
                        </select>
                    </div>
                </div>
                <div id="board-add-result" class="mt-2" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ trans('core/base::forms.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="btn-add-to-board" disabled>
                    <i class="fas fa-plus"></i> {{ trans('plugins/real-estate::board.add_to_board') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('addToBoardModal');
        var boardSelect = document.getElementById('board-select');
        var addBtn = document.getElementById('btn-add-to-board');
        var resultDiv = document.getElementById('board-add-result');
        var propertyId = {{ $propertyId }};

        modal.addEventListener('show.bs.modal', function () {
            document.getElementById('board-list-loading').style.display = 'block';
            document.getElementById('board-list-content').style.display = 'none';
            resultDiv.style.display = 'none';
            resultDiv.innerHTML = '';

            fetch('{{ route("board.get-boards-for-property") }}', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            })
            .then(function (response) { return response.json(); })
            .then(function (response) {
                boardSelect.innerHTML = '<option value="">-- {{ trans("plugins/real-estate::board.select_board") }} --</option>';

                var boards = response.data || [];
                boards.forEach(function (board) {
                    var option = document.createElement('option');
                    option.value = board.id;
                    option.textContent = board.name + ' (' + board.client_name + ')';
                    boardSelect.appendChild(option);
                });

                document.getElementById('board-list-loading').style.display = 'none';
                document.getElementById('board-list-content').style.display = 'block';
            });
        });

        boardSelect.addEventListener('change', function () {
            addBtn.disabled = !this.value;
        });

        addBtn.addEventListener('click', function () {
            var boardId = boardSelect.value;
            if (!boardId) return;

            addBtn.disabled = true;
            addBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

            fetch('{{ route("board.add-property-to-board") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({
                    board_id: boardId,
                    property_id: propertyId,
                }),
            })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                resultDiv.style.display = 'block';
                if (data.error) {
                    resultDiv.innerHTML = '<div class="alert alert-warning mb-0">' + data.message + '</div>';
                } else {
                    resultDiv.innerHTML = '<div class="alert alert-success mb-0">' + data.message + '</div>';
                }
                addBtn.innerHTML = '<i class="fas fa-plus"></i> {{ trans("plugins/real-estate::board.add_to_board") }}';
                addBtn.disabled = false;
            })
            .catch(function () {
                resultDiv.style.display = 'block';
                resultDiv.innerHTML = '<div class="alert alert-danger mb-0">An error occurred.</div>';
                addBtn.innerHTML = '<i class="fas fa-plus"></i> {{ trans("plugins/real-estate::board.add_to_board") }}';
                addBtn.disabled = false;
            });
        });
    });
</script>
