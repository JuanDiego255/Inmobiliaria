(function () {
    'use strict';

    var CRM_ROUTES = {
        leadsStore: '/admin/real-estate/crm/leads',
        leadsUpdate: '/admin/real-estate/crm/leads/{id}',
        leadsDestroy: '/admin/real-estate/crm/leads/{id}',
        leadsDetail: '/admin/real-estate/crm/leads/{id}/detail',
        activitiesStore: '/admin/real-estate/crm/activities',
        activitiesListForLead: '/admin/real-estate/crm/activities/lead/{id}',
        activitiesComplete: '/admin/real-estate/crm/activities/{id}/complete',
        importConsults: '/admin/real-estate/crm/import-consults',
    };

    function getRoute(name, params) {
        var url = CRM_ROUTES[name] || '';
        if (params) {
            Object.keys(params).forEach(function (k) {
                url = url.replace('{' + k + '}', params[k]);
            });
        }
        return url;
    }

    function getCsrfToken() {
        var el = document.querySelector('meta[name="csrf-token"]');
        return el ? el.getAttribute('content') : '';
    }

    function showToast(message, type) {
        var existing = document.querySelector('.crm-toast');
        if (existing) existing.remove();
        var toast = document.createElement('div');
        toast.className = 'crm-toast ' + (type || 'success');
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(function () { toast.classList.add('show'); }, 50);
        setTimeout(function () {
            toast.classList.remove('show');
            setTimeout(function () { toast.remove(); }, 300);
        }, 3000);
    }

    function ajaxRequest(method, url, data, callback) {
        var xhr = new XMLHttpRequest();
        xhr.open(method, url, true);
        xhr.setRequestHeader('X-CSRF-TOKEN', getCsrfToken());
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');
        if (data && !(data instanceof FormData)) {
            xhr.setRequestHeader('Content-Type', 'application/json');
            data = JSON.stringify(data);
        }
        xhr.onload = function () {
            var resp;
            try { resp = JSON.parse(xhr.responseText); } catch (e) { resp = {}; }
            if (xhr.status >= 200 && xhr.status < 300) {
                callback(null, resp);
            } else {
                var msg = resp.message || 'Error en la solicitud';
                if (resp.errors) {
                    var errs = [];
                    Object.keys(resp.errors).forEach(function (k) {
                        errs.push(resp.errors[k].join(', '));
                    });
                    msg = errs.join('\n');
                }
                callback(msg, resp);
            }
        };
        xhr.onerror = function () { callback('Error de red'); };
        xhr.send(data || null);
    }

    // ---- Lead Form Modal ----
    var leadFormModal, leadFormEl, leadDetailModal, activityFormModal;

    function initModals() {
        var formModalEl = document.getElementById('crmLeadFormModal');
        var detailModalEl = document.getElementById('crmLeadDetailModal');
        var actFormModalEl = document.getElementById('crmActivityFormModal');

        if (formModalEl) leadFormModal = new bootstrap.Modal(formModalEl);
        if (detailModalEl) leadDetailModal = new bootstrap.Modal(detailModalEl);
        if (actFormModalEl) activityFormModal = new bootstrap.Modal(actFormModalEl);

        leadFormEl = document.getElementById('crmLeadForm');

        // New Lead buttons
        document.querySelectorAll('.btn-crm-new-lead').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                openLeadForm();
            });
        });

        // Lead form submit
        if (leadFormEl) {
            leadFormEl.addEventListener('submit', function (e) {
                e.preventDefault();
                submitLeadForm();
            });
        }

        // Activity form submit
        var actForm = document.getElementById('crmActivityForm');
        if (actForm) {
            actForm.addEventListener('submit', function (e) {
                e.preventDefault();
                submitActivityForm();
            });
        }

        // Import consults
        document.querySelectorAll('.btn-crm-import-consults').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                importConsults(btn);
            });
        });

        // Quick activity (no lead pre-selected)
        document.querySelectorAll('.btn-crm-new-activity-quick').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                openActivityForm(null);
            });
        });
    }

    function openLeadForm(lead) {
        if (!leadFormEl) return;
        leadFormEl.reset();
        var title = document.getElementById('crmLeadFormModalTitle');
        var methodField = document.getElementById('crmLeadFormMethod');
        var idField = document.getElementById('crmLeadFormId');

        if (lead) {
            title.textContent = 'Editar Lead';
            methodField.value = 'PUT';
            idField.value = lead.id;
            document.getElementById('leadName').value = lead.name || '';
            document.getElementById('leadEmail').value = lead.email || '';
            document.getElementById('leadPhone').value = lead.phone || '';
            document.getElementById('leadAgent').value = lead.assigned_agent_id || '';
            document.getElementById('leadStage').value = lead.stage || 'nuevo';
            document.getElementById('leadSource').value = lead.source || 'manual';
            document.getElementById('leadBudgetMin').value = lead.budget_min || '';
            document.getElementById('leadBudgetMax').value = lead.budget_max || '';
            document.getElementById('leadCloseDate').value = lead.expected_close_date || '';
            document.getElementById('leadNotes').value = lead.notes || '';
        } else {
            title.textContent = 'Nuevo Lead';
            methodField.value = 'POST';
            idField.value = '';
        }

        if (leadDetailModal) {
            try { leadDetailModal.hide(); } catch (e) {}
        }
        leadFormModal.show();
    }

    function submitLeadForm() {
        var method = document.getElementById('crmLeadFormMethod').value;
        var leadId = document.getElementById('crmLeadFormId').value;
        var formData = new FormData(leadFormEl);
        var data = {};
        formData.forEach(function (v, k) { if (k !== '_token' && k !== '_method' && k !== 'lead_id') data[k] = v; });

        var url = method === 'PUT'
            ? getRoute('leadsUpdate', { id: leadId })
            : getRoute('leadsStore');

        var submitBtn = document.getElementById('crmLeadFormSubmit');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Guardando...';

        ajaxRequest(method, url, data, function (err, resp) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Guardar';

            if (err) {
                showToast(err, 'error');
                return;
            }

            showToast(resp.message || 'Guardado correctamente', 'success');
            leadFormModal.hide();

            if (typeof window.CRM_onLeadSaved === 'function') {
                window.CRM_onLeadSaved(resp.data, method);
            } else {
                setTimeout(function () { location.reload(); }, 600);
            }
        });
    }

    // ---- Lead Detail Modal ----
    window.CRM_openLeadDetail = function (leadId) {
        ajaxRequest('GET', getRoute('leadsDetail', { id: leadId }), null, function (err, resp) {
            if (err) { showToast(err, 'error'); return; }
            populateDetailModal(resp.data);
            leadDetailModal.show();
        });
    };

    function populateDetailModal(lead) {
        document.getElementById('detailLeadName').textContent = lead.name;
        document.getElementById('detailName').textContent = lead.name;
        document.getElementById('detailEmail').textContent = lead.email || '—';
        document.getElementById('detailPhone').textContent = lead.phone || '—';
        document.getElementById('detailAgent').textContent = lead.assigned_agent ? (lead.assigned_agent.first_name + ' ' + lead.assigned_agent.last_name) : '—';
        document.getElementById('detailBudget').textContent = formatBudget(lead);
        document.getElementById('detailCloseDate').textContent = lead.expected_close_date || '—';
        document.getElementById('detailLastContact').textContent = lead.last_contacted_at || '—';
        document.getElementById('detailCreated').textContent = lead.created_at ? lead.created_at.substring(0, 10) : '—';
        document.getElementById('detailNotes').textContent = lead.notes || 'Sin notas.';

        // Stage badge
        var stageLabels = { nuevo:'Nuevo', contactado:'Contactado', calificado:'Calificado', en_negociacion:'En Negociación', ganado:'Ganado', perdido:'Perdido' };
        document.getElementById('detailStage').textContent = stageLabels[lead.stage] || lead.stage;

        var sourceLabels = { manual:'Manual', website:'Sitio Web', consult:'Consulta', referral:'Referido', social:'Redes Sociales', phone:'Teléfono', other:'Otro' };
        document.getElementById('detailSource').textContent = sourceLabels[lead.source] || lead.source;

        // Edit button
        var editBtn = document.getElementById('detailEditBtn');
        editBtn.onclick = function () { openLeadForm(lead); };

        // Add activity button
        var addActBtn = document.getElementById('detailAddActivityBtn');
        addActBtn.onclick = function () {
            leadDetailModal.hide();
            openActivityForm(lead.id);
        };

        // Properties tab
        var propList = document.getElementById('detailPropertiesList');
        var propEmpty = document.getElementById('detailPropertiesEmpty');
        propList.innerHTML = '';
        if (lead.properties && lead.properties.length) {
            propEmpty.style.display = 'none';
            lead.properties.forEach(function (p) {
                var level = p.pivot ? p.pivot.interest_level : 'medium';
                var html = '<div class="crm-property-item">'
                    + '<div class="flex-grow-1">'
                    + '<div class="crm-property-item-name">' + escapeHtml(p.name) + '</div>'
                    + (p.pivot && p.pivot.notes ? '<small class="text-muted">' + escapeHtml(p.pivot.notes) + '</small>' : '')
                    + '</div>'
                    + '<span class="crm-property-item-interest ' + level + '">' + level + '</span>'
                    + '</div>';
                propList.insertAdjacentHTML('beforeend', html);
            });
        } else {
            propEmpty.style.display = '';
        }

        // Activities tab
        var actTimeline = document.getElementById('detailActivitiesTimeline');
        var actEmpty = document.getElementById('detailActivitiesEmpty');
        actTimeline.innerHTML = '';
        if (lead.activities && lead.activities.length) {
            actEmpty.style.display = 'none';
            lead.activities.forEach(function (a) {
                var iconMap = { note:'fas fa-sticky-note', call:'fas fa-phone', email:'fas fa-envelope', whatsapp:'fab fa-whatsapp', visit:'fas fa-walking', meeting:'fas fa-users' };
                var icon = iconMap[a.type] || 'fas fa-circle';
                var html = '<div class="crm-timeline-item ' + (a.completed_at ? 'completed' : '') + '">'
                    + '<div class="crm-timeline-icon crm-activity-' + a.type + '"><i class="' + icon + '"></i></div>'
                    + '<div class="crm-timeline-content">'
                    + '<div class="crm-timeline-header"><strong>' + escapeHtml(a.type) + '</strong>'
                    + '<span class="crm-timeline-meta">' + (a.user ? escapeHtml(a.user.name || a.user.first_name || '') : 'Sistema') + ' · ' + (a.created_at ? a.created_at.substring(0, 16).replace('T', ' ') : '') + '</span></div>'
                    + '<p class="crm-timeline-desc">' + escapeHtml(a.description) + '</p>';
                if (a.scheduled_at && !a.completed_at) {
                    html += '<button class="btn btn-sm btn-outline-success mt-1 btn-complete-activity" data-activity-id="' + a.id + '"><i class="fas fa-check me-1"></i>Completar</button>';
                }
                html += '</div></div>';
                actTimeline.insertAdjacentHTML('beforeend', html);
            });

            actTimeline.querySelectorAll('.btn-complete-activity').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var actId = btn.dataset.activityId;
                    ajaxRequest('PATCH', getRoute('activitiesComplete', { id: actId }), {}, function (err) {
                        if (err) { showToast(err, 'error'); return; }
                        showToast('Actividad completada', 'success');
                        btn.closest('.crm-timeline-item').classList.add('completed');
                        btn.remove();
                    });
                });
            });
        } else {
            actEmpty.style.display = '';
        }
    }

    // ---- Activity Form Modal ----
    function openActivityForm(leadId) {
        var form = document.getElementById('crmActivityForm');
        if (!form) return;
        form.reset();

        var hiddenLeadId = document.getElementById('activityLeadId');
        var selectGroup = document.getElementById('activityLeadSelectGroup');
        var select = document.getElementById('activityLeadSelect');

        if (leadId) {
            hiddenLeadId.value = leadId;
            selectGroup.style.display = 'none';
        } else {
            hiddenLeadId.value = '';
            selectGroup.style.display = '';
            // Load leads for dropdown
            ajaxRequest('GET', getRoute('leadsStore'), null, function (err, resp) {
                // A simple fallback: we won't populate if error
            });
        }

        activityFormModal.show();
    }

    function submitActivityForm() {
        var form = document.getElementById('crmActivityForm');
        var formData = new FormData(form);
        var data = {};
        formData.forEach(function (v, k) { if (k !== '_token') data[k] = v; });

        // Use hidden lead_id or select
        if (!data.lead_id && data.lead_id_select) {
            data.lead_id = data.lead_id_select;
        }
        delete data.lead_id_select;

        if (!data.lead_id) {
            showToast('Debe seleccionar un lead', 'error');
            return;
        }

        var submitBtn = document.getElementById('crmActivityFormSubmit');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Guardando...';

        ajaxRequest('POST', getRoute('activitiesStore'), data, function (err, resp) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Guardar';

            if (err) { showToast(err, 'error'); return; }
            showToast(resp.message || 'Actividad registrada', 'success');
            activityFormModal.hide();

            if (typeof window.CRM_onActivitySaved === 'function') {
                window.CRM_onActivitySaved(resp.data);
            } else {
                setTimeout(function () { location.reload(); }, 600);
            }
        });
    }

    function importConsults(btn) {
        var origText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Importando...';

        ajaxRequest('POST', getRoute('importConsults'), {}, function (err, resp) {
            btn.disabled = false;
            btn.innerHTML = origText;
            if (err) { showToast(err, 'error'); return; }
            showToast(resp.message || 'Consultas importadas', 'success');
            setTimeout(function () { location.reload(); }, 800);
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

    // Expose helpers
    window.CRM_openLeadForm = openLeadForm;
    window.CRM_openActivityForm = openActivityForm;
    window.CRM_showToast = showToast;
    window.CRM_ajaxRequest = ajaxRequest;
    window.CRM_getRoute = getRoute;

    // Init when DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModals);
    } else {
        initModals();
    }
})();
