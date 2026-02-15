(function () {
    'use strict';

    var config = window.__propertyPickerConfig || {};
    var modalEl = document.getElementById('propertyPickerModal');
    if (!modalEl) return;

    var selectedIds = new Set();
    var currentPage = 1;
    var lastPage = 1;
    var searchTimer = null;
    var useSmartFilter = true;

    var grid = document.getElementById('picker-grid');
    var loading = document.getElementById('picker-loading');
    var empty = document.getElementById('picker-empty');
    var loadMore = document.getElementById('picker-load-more');
    var countBadge = document.getElementById('picker-selected-count');
    var bulkBtn = document.getElementById('btn-bulk-add');
    var bulkBtnText = document.getElementById('btn-bulk-add-text');
    var smartBanner = document.getElementById('smart-filter-banner');
    var smartNotes = document.getElementById('smart-filter-notes');

    // Show smart filter banner if client has notes
    if (config.clientNotes) {
        smartBanner.classList.remove('d-none');
        var notesPreview = config.clientNotes.length > 100
            ? config.clientNotes.substring(0, 100) + '...'
            : config.clientNotes;
        smartNotes.textContent = config.translations.smartFilterNotes + ' "' + notesPreview + '"';
    }

    // Clear smart filter
    document.getElementById('btn-clear-smart-filter').addEventListener('click', function () {
        useSmartFilter = false;
        smartBanner.classList.add('d-none');
        loadProperties(true);
    });

    // Clear manual filters
    document.getElementById('btn-clear-filters').addEventListener('click', function () {
        document.getElementById('picker-search').value = '';
        document.getElementById('picker-type').value = '';
        document.getElementById('picker-bedrooms').value = '';
        document.getElementById('picker-bathrooms').value = '';
        document.getElementById('picker-price-min').value = '';
        document.getElementById('picker-price-max').value = '';
        loadProperties(true);
    });

    // Filter change handlers
    ['picker-type', 'picker-bedrooms', 'picker-bathrooms'].forEach(function (id) {
        document.getElementById(id).addEventListener('change', function () {
            loadProperties(true);
        });
    });

    ['picker-search', 'picker-price-min', 'picker-price-max'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () { loadProperties(true); }, 400);
        });
    });

    // Load more
    document.getElementById('btn-load-more').addEventListener('click', function () {
        if (currentPage < lastPage) {
            currentPage++;
            loadProperties(false);
        }
    });

    // Modal open handler
    modalEl.addEventListener('shown.bs.modal', function () {
        currentPage = 1;
        useSmartFilter = !!config.clientNotes;
        if (config.clientNotes) {
            smartBanner.classList.remove('d-none');
        }
        loadProperties(true);
    });

    function getFilters() {
        return {
            board_id: config.boardId,
            search: document.getElementById('picker-search').value,
            type: document.getElementById('picker-type').value,
            bedrooms: document.getElementById('picker-bedrooms').value,
            bathrooms: document.getElementById('picker-bathrooms').value,
            price_min: document.getElementById('picker-price-min').value,
            price_max: document.getElementById('picker-price-max').value,
            client_notes: useSmartFilter ? config.clientNotes : '',
            page: currentPage,
        };
    }

    function loadProperties(reset) {
        if (reset) {
            currentPage = 1;
            grid.innerHTML = '';
        }

        loading.classList.remove('d-none');
        empty.classList.add('d-none');
        loadMore.classList.add('d-none');

        var filters = getFilters();
        var params = new URLSearchParams(filters).toString();

        fetch(config.searchUrl + '?' + params, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        })
        .then(function (r) {
            if (!r.ok) {
                return r.json().then(function (errData) {
                    console.error('Property picker server error:', errData);
                    throw new Error('HTTP ' + r.status + ': ' + (errData.message || 'Unknown error'));
                }).catch(function (parseErr) {
                    if (parseErr.message && parseErr.message.startsWith('HTTP ')) throw parseErr;
                    throw new Error('HTTP ' + r.status);
                });
            }
            return r.json();
        })
        .then(function (response) {
            loading.classList.add('d-none');
            var items = response.data || [];
            lastPage = response.meta ? response.meta.last_page : 1;

            if (items.length === 0 && currentPage === 1) {
                empty.classList.remove('d-none');
                return;
            }

            items.forEach(function (item) {
                grid.appendChild(createCard(item));
            });

            if (currentPage < lastPage) {
                loadMore.classList.remove('d-none');
            }
        })
        .catch(function (err) {
            loading.classList.add('d-none');
            empty.classList.remove('d-none');
            console.error('Property picker error:', err);
        });
    }

    function createCard(item) {
        var col = document.createElement('div');
        col.className = 'col-6 col-md-4 col-lg-3 col-xl-2';

        var isSelected = selectedIds.has(item.id);
        var card = document.createElement('div');
        card.className = 'property-pick-card' + (isSelected ? ' selected' : '') + (item.already_added ? ' already-added' : '');
        card.dataset.id = item.id;

        var checkIcon = isSelected ? '<i class="fas fa-check" style="font-size:12px"></i>' : '';

        var metaParts = [];
        if (item.bedrooms) metaParts.push('<i class="fas fa-bed"></i> ' + item.bedrooms);
        if (item.bathrooms) metaParts.push('<i class="fas fa-bath"></i> ' + item.bathrooms);
        if (item.square) metaParts.push('<i class="fas fa-expand"></i> ' + item.square + ' m\u00B2');

        card.innerHTML =
            '<div class="pick-checkbox">' + checkIcon + '</div>' +
            (item.already_added ? '<div class="already-badge">' + config.translations.alreadyInBoard + '</div>' : '') +
            '<span class="pick-badge">' + escapeHtml(item.type) + '</span>' +
            '<img class="pick-image" src="' + escapeHtml(item.image) + '" alt="" loading="lazy">' +
            '<div class="pick-body">' +
                '<div class="pick-name" title="' + escapeHtml(item.name) + '">' + escapeHtml(item.name) + '</div>' +
                '<div class="pick-price">' + escapeHtml(item.price) + '</div>' +
                (item.location ? '<div class="pick-meta"><i class="fas fa-map-marker-alt"></i> ' + escapeHtml(item.location) + '</div>' : '') +
                (metaParts.length ? '<div class="pick-meta">' + metaParts.join(' ') + '</div>' : '') +
            '</div>';

        if (!item.already_added) {
            card.addEventListener('click', function () {
                toggleSelect(item.id, card);
            });
        }

        col.appendChild(card);
        return col;
    }

    function toggleSelect(id, card) {
        if (selectedIds.has(id)) {
            selectedIds.delete(id);
            card.classList.remove('selected');
            card.querySelector('.pick-checkbox').innerHTML = '';
        } else {
            selectedIds.add(id);
            card.classList.add('selected');
            card.querySelector('.pick-checkbox').innerHTML = '<i class="fas fa-check" style="font-size:12px"></i>';
        }
        updateSelectionUI();
    }

    function updateSelectionUI() {
        var count = selectedIds.size;
        if (count > 0) {
            countBadge.classList.remove('d-none');
            countBadge.textContent = count;
            bulkBtn.disabled = false;
            bulkBtnText.textContent = config.translations.addSelected + ' (' + count + ')';
        } else {
            countBadge.classList.add('d-none');
            bulkBtn.disabled = true;
            bulkBtnText.textContent = config.translations.addSelected;
        }
    }

    // Bulk add
    bulkBtn.addEventListener('click', function () {
        if (selectedIds.size === 0) return;

        bulkBtn.disabled = true;
        bulkBtnText.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>' + config.translations.adding;

        fetch(config.bulkAddUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken,
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                board_id: config.boardId,
                property_ids: Array.from(selectedIds),
            }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.error) {
                window.location.reload();
            } else {
                bulkBtnText.textContent = config.translations.addSelected;
                bulkBtn.disabled = false;
            }
        })
        .catch(function () {
            bulkBtnText.textContent = config.translations.addSelected;
            bulkBtn.disabled = false;
        });
    });

    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }
})();
