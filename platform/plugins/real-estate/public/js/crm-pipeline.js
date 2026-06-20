(function () {
    'use strict';

    var pipelineDataUrl = '/admin/real-estate/crm/pipeline-data';
    var updateStageUrl = '/admin/real-estate/crm/leads/{id}/stage';

    var sourceLabels = {
        manual: 'Manual', website: 'Web', consult: 'Consulta',
        referral: 'Referido', social: 'Social', phone: 'Tel', other: 'Otro'
    };

    function enumVal(e) {
        if (!e) return '';
        if (typeof e === 'string') return e;
        if (typeof e === 'object' && e.value !== undefined) return e.value;
        return String(e);
    }

    function init() {
        loadPipelineData();
        setupDragAndDrop();
    }

    function loadPipelineData() {
        if (typeof window.CRM_ajaxRequest !== 'function') return;
        window.CRM_ajaxRequest('GET', pipelineDataUrl, null, function (err, resp) {
            if (err) {
                if (typeof window.CRM_showToast === 'function') window.CRM_showToast(err, 'error');
                return;
            }
            renderPipeline(resp.data);
        });
    }

    function renderPipeline(data) {
        var stages = Object.keys(data);
        stages.forEach(function (stage) {
            var col = document.querySelector('.cd-drop-zone[data-stage="' + stage + '"]');
            if (!col) return;

            col.innerHTML = '';
            var leads = data[stage] || [];

            var countBadge = document.querySelector('[data-stage-count="' + stage + '"]');
            if (countBadge) countBadge.textContent = leads.length;

            if (leads.length === 0) {
                col.innerHTML = '<div class="cd-pipeline-empty" style="display:block"><span class="material-icons">inbox</span>Sin leads</div>';
                return;
            }

            leads.forEach(function (lead) {
                var card = createLeadCard(lead);
                col.appendChild(card);
            });
        });
    }

    function createLeadCard(lead) {
        var card = document.createElement('div');
        card.className = 'cd-lead-card';
        card.draggable = true;
        card.dataset.leadId = lead.id;

        var agentName = lead.assigned_agent
            ? (lead.assigned_agent.first_name + ' ' + (lead.assigned_agent.last_name || '')).trim()
            : '—';
        var budget = formatBudget(lead);
        var phone = lead.phone || '—';
        var date = lead.created_at ? lead.created_at.substring(0, 10) : '';
        var source = sourceLabels[enumVal(lead.source)] || enumVal(lead.source) || '';

        card.innerHTML = '<div class="cd-lead-card-header">'
            + '<span class="cd-lead-card-name">' + escapeHtml(lead.name) + '</span>'
            + '<span class="cd-lead-card-grab"><span class="material-icons">drag_indicator</span></span>'
            + '</div>'
            + '<div class="cd-lead-card-body">'
            + '<div class="cd-lead-card-info" title="Teléfono"><span class="material-icons">call</span> <span>' + escapeHtml(phone) + '</span></div>'
            + '<div class="cd-lead-card-info" title="Agente"><span class="material-icons">person</span> <span>' + escapeHtml(agentName) + '</span></div>'
            + (budget !== '—' ? '<div class="cd-lead-card-info cd-lead-card-budget" title="Presupuesto"><span class="material-icons">payments</span> <span>' + budget + '</span></div>' : '')
            + '</div>'
            + '<div class="cd-lead-card-footer">'
            + '<span class="cd-lead-card-date"><span class="material-icons">calendar_today</span> ' + date + '</span>'
            + '<span class="cd-lead-card-source">' + source + '</span>'
            + '</div>';

        card.addEventListener('click', function (e) {
            if (e.target.closest('.cd-lead-card-grab')) return;
            if (typeof window.CRM_openLeadDetail === 'function') {
                window.CRM_openLeadDetail(lead.id);
            }
        });

        return card;
    }

    function setupDragAndDrop() {
        var board = document.getElementById('crmPipelineBoard');
        if (!board) return;

        var draggedCard = null;

        board.addEventListener('dragstart', function (e) {
            var card = e.target.closest('.cd-lead-card');
            if (!card) return;
            draggedCard = card;
            card.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', card.dataset.leadId);
        });

        board.addEventListener('dragend', function (e) {
            var card = e.target.closest('.cd-lead-card');
            if (card) card.classList.remove('dragging');
            document.querySelectorAll('.cd-drop-zone').forEach(function (z) {
                z.classList.remove('drag-over');
            });
            draggedCard = null;
        });

        document.querySelectorAll('.cd-drop-zone').forEach(function (zone) {
            zone.addEventListener('dragover', function (e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                zone.classList.add('drag-over');
            });

            zone.addEventListener('dragleave', function (e) {
                if (!zone.contains(e.relatedTarget)) {
                    zone.classList.remove('drag-over');
                }
            });

            zone.addEventListener('drop', function (e) {
                e.preventDefault();
                zone.classList.remove('drag-over');

                var leadId = e.dataTransfer.getData('text/plain');
                var newStage = zone.dataset.stage;
                if (!leadId || !newStage) return;

                if (draggedCard) {
                    var empty = zone.querySelector('.cd-pipeline-empty');
                    if (empty) empty.remove();
                    zone.appendChild(draggedCard);
                    draggedCard.classList.remove('dragging');
                }

                updateColumnCounts();

                var url = updateStageUrl.replace('{id}', leadId);
                if (typeof window.CRM_ajaxRequest === 'function') {
                    window.CRM_ajaxRequest('PATCH', url, { stage: newStage }, function (err) {
                        if (err) {
                            if (typeof window.CRM_showToast === 'function') window.CRM_showToast(err, 'error');
                            loadPipelineData();
                        }
                    });
                }
            });
        });

        var touchCard = null, touchStartX, touchStartY;

        board.addEventListener('touchstart', function (e) {
            var card = e.target.closest('.cd-lead-card');
            if (!card) return;
            touchCard = card;
            var touch = e.touches[0];
            touchStartX = touch.clientX;
            touchStartY = touch.clientY;
        }, { passive: true });

        board.addEventListener('touchend', function (e) {
            if (!touchCard) return;
            var touch = e.changedTouches[0];
            var dx = touch.clientX - touchStartX;
            if (Math.abs(dx) < 50) { touchCard = null; return; }

            var currentZone = touchCard.closest('.cd-drop-zone');
            var zones = Array.from(document.querySelectorAll('.cd-drop-zone'));
            var idx = zones.indexOf(currentZone);
            var targetIdx = dx > 0 ? idx + 1 : idx - 1;

            if (targetIdx >= 0 && targetIdx < zones.length) {
                var targetZone = zones[targetIdx];
                var empty = targetZone.querySelector('.cd-pipeline-empty');
                if (empty) empty.remove();
                targetZone.appendChild(touchCard);
                updateColumnCounts();

                var leadId = touchCard.dataset.leadId;
                var newStage = targetZone.dataset.stage;
                var url = updateStageUrl.replace('{id}', leadId);
                if (typeof window.CRM_ajaxRequest === 'function') {
                    window.CRM_ajaxRequest('PATCH', url, { stage: newStage }, function (err) {
                        if (err) loadPipelineData();
                    });
                }
            }
            touchCard = null;
        }, { passive: true });
    }

    function updateColumnCounts() {
        document.querySelectorAll('.cd-drop-zone').forEach(function (zone) {
            var stage = zone.dataset.stage;
            var cards = zone.querySelectorAll('.cd-lead-card').length;
            var badge = document.querySelector('[data-stage-count="' + stage + '"]');
            if (badge) badge.textContent = cards;
        });
    }

    function formatBudget(lead) {
        var min = lead.budget_min ? Number(lead.budget_min).toLocaleString() : '';
        var max = lead.budget_max ? Number(lead.budget_max).toLocaleString() : '';
        if (!min && !max) return '—';
        if (min && max) return '$' + min + ' – $' + max;
        return '$' + (min || max);
    }

    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    window.CRM_onLeadSaved = function () {
        loadPipelineData();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
