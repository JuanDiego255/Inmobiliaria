(function () {
    'use strict';

    var config = window.__addToBoardConfig || {};
    var currentPropertyId = null;
    var modalEl = document.getElementById('addToBoardTableModal');

    if (!modalEl) return;

    var modal = new bootstrap.Modal(modalEl);
    var boardSelect = document.getElementById('table-board-select');
    var addBtn = document.getElementById('btn-table-add-to-board');
    var resultDiv = document.getElementById('table-board-result');
    var loadingDiv = document.getElementById('table-board-loading');
    var contentDiv = document.getElementById('table-board-content');

    // Listen for clicks on the "Add to Board" action buttons in the table
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-add-to-board-table');
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();

        currentPropertyId = btn.getAttribute('data-property-id');
        if (!currentPropertyId) return;

        // Reset modal state
        loadingDiv.style.display = 'block';
        contentDiv.style.display = 'none';
        resultDiv.style.display = 'none';
        resultDiv.innerHTML = '';
        addBtn.disabled = true;
        boardSelect.value = '';

        modal.show();

        // Fetch boards
        fetch(config.getBoardsUrl, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken,
            },
        })
        .then(function (response) { return response.json(); })
        .then(function (response) {
            var defaultText = config.translations.selectBoard || 'Select a Board';
            boardSelect.innerHTML = '<option value="">-- ' + defaultText + ' --</option>';

            var boards = response.data || [];
            boards.forEach(function (board) {
                var option = document.createElement('option');
                option.value = board.id;
                option.textContent = board.name + ' (' + board.client_name + ')';
                boardSelect.appendChild(option);
            });

            loadingDiv.style.display = 'none';
            contentDiv.style.display = 'block';
        })
        .catch(function () {
            loadingDiv.style.display = 'none';
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<div class="alert alert-danger mb-0">Error loading boards.</div>';
        });
    });

    // Enable/disable add button based on selection
    boardSelect.addEventListener('change', function () {
        addBtn.disabled = !this.value;
    });

    // Add property to selected board
    addBtn.addEventListener('click', function () {
        var boardId = boardSelect.value;
        if (!boardId || !currentPropertyId) return;

        var addText = config.translations.addToBoard || 'Add to Board';
        var addingText = config.translations.adding || 'Adding...';

        addBtn.disabled = true;
        addBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + addingText;

        fetch(config.addToBoardUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken,
            },
            body: JSON.stringify({
                board_id: boardId,
                property_id: currentPropertyId,
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
            addBtn.innerHTML = '<i class="fas fa-plus"></i> ' + addText;
            addBtn.disabled = false;
        })
        .catch(function () {
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<div class="alert alert-danger mb-0">An error occurred.</div>';
            addBtn.innerHTML = '<i class="fas fa-plus"></i> ' + addText;
            addBtn.disabled = false;
        });
    });
})();
