(function () {
    'use strict';

    var config = window.propertyGridConfig || {};
    var currentPage = 1;
    var isLoading = false;
    var gridInitialized = false;

    // Initialize when grid tab is shown
    document.addEventListener('DOMContentLoaded', function () {
        var gridTab = document.getElementById('grid-view-tab');
        if (!gridTab) return;

        gridTab.addEventListener('shown.bs.tab', function () {
            if (!gridInitialized) {
                gridInitialized = true;
                loadGrid();
                bindEvents();
            }
        });

        // Remember tab preference
        var savedTab = localStorage.getItem('property_view_tab');
        if (savedTab === 'grid') {
            var tab = new bootstrap.Tab(gridTab);
            tab.show();
        }

        document.querySelectorAll('#propertyViewTabs .nav-link').forEach(function (btn) {
            btn.addEventListener('shown.bs.tab', function (e) {
                localStorage.setItem('property_view_tab', e.target.id === 'grid-view-tab' ? 'grid' : 'table');
            });
        });
    });

    function bindEvents() {
        // Search
        var searchInput = document.getElementById('grid-search');
        var searchBtn = document.getElementById('grid-search-btn');
        var debounceTimer;

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    currentPage = 1;
                    loadGrid();
                }, 400);
            });

            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    currentPage = 1;
                    loadGrid();
                }
            });
        }

        if (searchBtn) {
            searchBtn.addEventListener('click', function () {
                currentPage = 1;
                loadGrid();
            });
        }

        // Status filter
        var statusFilter = document.getElementById('grid-status-filter');
        if (statusFilter) {
            statusFilter.addEventListener('change', function () {
                currentPage = 1;
                loadGrid();
            });
        }

        // Sort
        var sortSelect = document.getElementById('grid-sort');
        if (sortSelect) {
            sortSelect.addEventListener('change', function () {
                currentPage = 1;
                loadGrid();
            });
        }

        // Board modal
        initBoardModal();
    }

    function loadGrid() {
        if (isLoading) return;
        isLoading = true;

        var grid = document.getElementById('property-grid');
        grid.innerHTML = '<div class="property-grid-loading text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';

        var search = (document.getElementById('grid-search') || {}).value || '';
        var status = (document.getElementById('grid-status-filter') || {}).value || '';
        var sortVal = (document.getElementById('grid-sort') || {}).value || 'created_at-desc';
        var parts = sortVal.split('-');
        var sort, direction;

        if (sortVal === 'created_at-desc' || sortVal === 'created_at-asc') {
            sort = 'created_at';
            direction = parts[parts.length - 1];
        } else if (sortVal === 'price-asc' || sortVal === 'price-desc') {
            sort = 'price';
            direction = parts[parts.length - 1];
        } else {
            sort = parts[0];
            direction = parts[parts.length - 1];
        }

        var url = config.ajaxUrl + '?' + new URLSearchParams({
            page: currentPage,
            per_page: config.perPage || 18,
            search: search,
            status: status,
            sort: sort,
            direction: direction,
        }).toString();

        fetch(url, {
            headers: { 'Accept': 'application/json' },
        })
        .then(function (r) { return r.json(); })
        .then(function (response) {
            renderGrid(response.data, response.meta);
            renderPagination(response.meta);
            isLoading = false;
        })
        .catch(function () {
            grid.innerHTML = '<div class="property-grid-empty"><i class="fas fa-exclamation-triangle"></i>Error loading properties</div>';
            isLoading = false;
        });
    }

    function renderGrid(items, meta) {
        var grid = document.getElementById('property-grid');
        var t = config.translations || {};

        // Update count
        var countEl = document.querySelector('.property-grid-count');
        if (countEl && meta) {
            countEl.textContent = (meta.from || 0) + ' - ' + (meta.to || 0) + ' ' + (t.of || 'of') + ' ' + meta.total + ' ' + (t.properties || 'properties');
        }

        if (!items || items.length === 0) {
            grid.innerHTML = '<div class="property-grid-empty"><i class="fas fa-building"></i>' + (t.noProperties || 'No properties found') + '</div>';
            return;
        }

        var html = '';
        items.forEach(function (p) {
            var statusClass = 'badge-published';
            var statusText = p.status;
            if (p.moderation_status === 'pending') {
                statusClass = 'badge-pending';
                statusText = 'Pending';
            } else if (p.status === 'not_available') {
                statusClass = 'badge-unpublished';
            }

            html += '<div class="property-grid-card" data-id="' + p.id + '">';
            html += '  <div class="property-grid-card-image">';
            html += '    <a href="' + p.edit_url + '">';
            html += '      <img src="' + p.image + '" alt="' + escapeHtml(p.name) + '" loading="lazy">';
            html += '    </a>';
            html += '    <div class="property-grid-card-badges">';
            html += '      <span class="property-grid-card-status-badge ' + statusClass + '">';
            html += '        <i class="fas fa-circle" style="font-size:6px"></i> ' + escapeHtml(p.status.replace('_', ' '));
            html += '      </span>';
            html += '    </div>';
            html += '    <button type="button" class="property-grid-card-board-btn" data-property-id="' + p.id + '" title="Add to Board">';
            html += '      <i class="fas fa-clipboard-list"></i>';
            html += '    </button>';

            if (p.unique_id) {
                html += '    <span class="property-grid-card-uid">' + escapeHtml(p.unique_id) + '</span>';
            }

            html += '  </div>';
            html += '  <div class="property-grid-card-body">';
            html += '    <h6 class="property-grid-card-title"><a href="' + p.edit_url + '">' + escapeHtml(p.name) + '</a></h6>';
            html += '    <div class="property-grid-card-price">' + p.price + ' <span class="type-label">' + escapeHtml(p.type) + '</span></div>';

            if (p.location) {
                html += '    <div class="property-grid-card-location"><i class="fas fa-map-marker-alt me-1"></i>' + escapeHtml(p.location) + '</div>';
            }

            html += '    <div class="property-grid-card-specs">';
            if (p.bedrooms) {
                html += '      <span class="property-grid-card-spec"><i class="fas fa-bed"></i> ' + p.bedrooms + '</span>';
            }
            if (p.bathrooms) {
                html += '      <span class="property-grid-card-spec"><i class="fas fa-bath"></i> ' + p.bathrooms + '</span>';
            }
            if (p.floors) {
                html += '      <span class="property-grid-card-spec"><i class="fas fa-layer-group"></i> ' + p.floors + '</span>';
            }
            if (p.square_text) {
                html += '      <span class="property-grid-card-spec"><i class="fas fa-ruler-combined"></i> ' + escapeHtml(p.square_text) + '</span>';
            }
            html += '    </div>';
            html += '  </div>';

            // Actions bar
            html += '  <div class="property-grid-card-actions">';
            html += '    <a href="' + p.edit_url + '" class="action-edit"><i class="fas fa-pencil-alt"></i> Edit</a>';
            html += '    <button type="button" class="action-board" data-property-id="' + p.id + '"><i class="fas fa-clipboard-list"></i> Board</button>';
            html += '    <button type="button" class="action-delete" data-url="' + p.delete_url + '"><i class="fas fa-trash"></i> Delete</button>';
            html += '  </div>';
            html += '</div>';
        });

        grid.innerHTML = html;

        // Bind card events
        bindCardEvents();
    }

    function renderPagination(meta) {
        var container = document.getElementById('grid-pagination');
        if (!container || !meta) return;

        if (meta.last_page <= 1) {
            container.innerHTML = '';
            return;
        }

        var html = '<nav><ul class="pagination pagination-sm mb-0">';

        // Previous
        html += '<li class="page-item ' + (meta.current_page === 1 ? 'disabled' : '') + '">';
        html += '<a class="page-link" href="#" data-page="' + (meta.current_page - 1) + '"><i class="fas fa-chevron-left"></i></a></li>';

        // Page numbers
        var startPage = Math.max(1, meta.current_page - 2);
        var endPage = Math.min(meta.last_page, meta.current_page + 2);

        if (startPage > 1) {
            html += '<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>';
            if (startPage > 2) html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }

        for (var i = startPage; i <= endPage; i++) {
            html += '<li class="page-item ' + (i === meta.current_page ? 'active' : '') + '">';
            html += '<a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
        }

        if (endPage < meta.last_page) {
            if (endPage < meta.last_page - 1) html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            html += '<li class="page-item"><a class="page-link" href="#" data-page="' + meta.last_page + '">' + meta.last_page + '</a></li>';
        }

        // Next
        html += '<li class="page-item ' + (meta.current_page === meta.last_page ? 'disabled' : '') + '">';
        html += '<a class="page-link" href="#" data-page="' + (meta.current_page + 1) + '"><i class="fas fa-chevron-right"></i></a></li>';

        html += '</ul></nav>';
        container.innerHTML = html;

        // Bind pagination
        container.querySelectorAll('[data-page]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                var page = parseInt(this.dataset.page);
                if (page >= 1 && page !== currentPage) {
                    currentPage = page;
                    loadGrid();
                    document.getElementById('grid-view').scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    }

    function bindCardEvents() {
        // Board buttons (on image)
        document.querySelectorAll('.property-grid-card-board-btn, .action-board').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var propertyId = this.dataset.propertyId;
                openBoardModal(propertyId);
            });
        });

        // Delete buttons
        document.querySelectorAll('.action-delete').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var url = this.dataset.url;
                var card = this.closest('.property-grid-card');
                var t = config.translations || {};

                if (!confirm(t.confirmDelete || 'Are you sure you want to delete this property?')) return;

                fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': config.csrfToken,
                        'Accept': 'application/json',
                    },
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.error) {
                        card.style.transition = 'opacity 0.3s, transform 0.3s';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.9)';
                        setTimeout(function () {
                            card.remove();
                        }, 300);
                    }
                });
            });
        });
    }

    // Board Modal Logic
    var selectedPropertyId = null;

    function initBoardModal() {
        var modal = document.getElementById('gridAddToBoardModal');
        if (!modal) return;

        var boardSelect = document.getElementById('grid-board-select');
        var addBtn = document.getElementById('grid-btn-add-to-board');
        var resultDiv = document.getElementById('grid-board-result');

        boardSelect.addEventListener('change', function () {
            addBtn.disabled = !this.value;
        });

        addBtn.addEventListener('click', function () {
            var boardId = boardSelect.value;
            if (!boardId || !selectedPropertyId) return;

            addBtn.disabled = true;
            addBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch(config.addToBoardUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                },
                body: JSON.stringify({ board_id: boardId, property_id: selectedPropertyId }),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                resultDiv.style.display = 'block';
                resultDiv.innerHTML = '<div class="alert alert-' + (data.error ? 'warning' : 'success') + ' mb-0">' + data.message + '</div>';
                addBtn.innerHTML = '<i class="fas fa-plus"></i> Add';
                addBtn.disabled = false;
            })
            .catch(function () {
                resultDiv.style.display = 'block';
                resultDiv.innerHTML = '<div class="alert alert-danger mb-0">Error</div>';
                addBtn.innerHTML = '<i class="fas fa-plus"></i> Add';
                addBtn.disabled = false;
            });
        });
    }

    function openBoardModal(propertyId) {
        selectedPropertyId = propertyId;
        var modal = new bootstrap.Modal(document.getElementById('gridAddToBoardModal'));
        var loading = document.getElementById('grid-board-loading');
        var content = document.getElementById('grid-board-content');
        var boardSelect = document.getElementById('grid-board-select');
        var resultDiv = document.getElementById('grid-board-result');

        loading.style.display = 'block';
        content.style.display = 'none';
        resultDiv.style.display = 'none';
        resultDiv.innerHTML = '';

        modal.show();

        fetch(config.boardsUrl, {
            headers: { 'Accept': 'application/json' },
        })
        .then(function (r) { return r.json(); })
        .then(function (response) {
            boardSelect.innerHTML = '<option value="">-- Select Board --</option>';
            var boards = response.data || [];
            boards.forEach(function (board) {
                var opt = document.createElement('option');
                opt.value = board.id;
                opt.textContent = board.name + ' (' + board.client_name + ')';
                boardSelect.appendChild(opt);
            });
            loading.style.display = 'none';
            content.style.display = 'block';
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }
})();
