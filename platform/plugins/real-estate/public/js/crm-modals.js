(function () {
    'use strict';

    var CRM_ROUTES = {
        leadsStore: '/admin/real-estate/crm/leads/store',
        leadsList: '/admin/real-estate/crm/leads/list',
        leadsUpdate: '/admin/real-estate/crm/leads/{id}',
        leadsDestroy: '/admin/real-estate/crm/leads/{id}',
        leadsDetail: '/admin/real-estate/crm/leads/{id}/detail',
        activitiesStore: '/admin/real-estate/crm/activities',
        activitiesListForLead: '/admin/real-estate/crm/activities/lead/{id}',
        activitiesComplete: '/admin/real-estate/crm/activities/{id}/complete',
        importConsults: '/admin/real-estate/crm/import-consults',
        leadsUpdateStage: '/admin/real-estate/crm/leads/{id}/stage',
        leadsAddProperty: '/admin/real-estate/crm/leads/{id}/properties',
        leadsRemoveProperty: '/admin/real-estate/crm/leads/{id}/properties/{propertyId}',
        propertiesSearch: '/admin/real-estate/crm/properties/search',
        activitiesQuick: '/admin/real-estate/crm/activities',
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
        var existing = document.querySelector('.cd-toast');
        if (existing) existing.remove();
        var toast = document.createElement('div');
        toast.className = 'cd-toast ' + (type || 'success');
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

        // Inline stage change from detail modal
        var stageSelect = document.getElementById('detailStageSelect');
        if (stageSelect) {
            stageSelect.addEventListener('change', function () {
                var modal = document.getElementById('crmLeadDetailModal');
                var leadId = modal ? modal.dataset.leadId : null;
                if (!leadId) return;

                var newStage = stageSelect.value;
                var statusEl = document.getElementById('detailStageStatus');
                statusEl.textContent = 'Guardando...';
                statusEl.style.color = 'var(--cd-ink-400)';
                statusEl.style.display = 'inline';

                ajaxRequest('PATCH', getRoute('leadsUpdateStage', { id: leadId }), { stage: newStage }, function (err) {
                    if (err) {
                        statusEl.textContent = 'Error al guardar';
                        statusEl.style.color = '#ef4444';
                        return;
                    }
                    statusEl.textContent = '✓ Guardado';
                    statusEl.style.color = '#10b981';
                    setTimeout(function () { statusEl.style.display = 'none'; }, 2000);

                    // Notify dashboard if callback exists
                    if (typeof window.CRM_onLeadSaved === 'function') {
                        window.CRM_onLeadSaved(null, 'PATCH');
                    }
                });
            });
        }

        // Initialize Select2 on lead dropdowns in modals
        setTimeout(function() {
            if (window.jQuery && jQuery.fn.select2) {
                jQuery('.crm-lead-select2').select2({
                    placeholder: 'Buscar lead...',
                    allowClear: true,
                    width: '100%'
                });
            }
        }, 200);
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
            document.getElementById('leadStage').value = enumVal(lead.stage) || 'nuevo';
            document.getElementById('leadSource').value = enumVal(lead.source) || 'manual';
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
        submitBtn.innerHTML = '<span class="material-icons" style="font-size:16px;animation:spin 1s linear infinite">refresh</span> Guardando...';

        ajaxRequest(method, url, data, function (err, resp) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span class="material-icons" style="font-size:16px">save</span> Guardar';

            if (err) {
                // Check if it's a duplicate warning (409)
                if (resp && resp.data && resp.data.duplicates) {
                    var dupes = resp.data.duplicates;
                    var msg = 'Posibles duplicados encontrados:\n';
                    dupes.forEach(function(d) {
                        msg += '- ' + d.name + ' (' + (d.email || d.phone || '') + ')\n';
                    });
                    msg += '\n¿Desea crear el lead de todas formas?';
                    if (confirm(msg)) {
                        data.force_create = 1;
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="material-icons" style="font-size:16px;animation:spin 1s linear infinite">refresh</span> Guardando...';
                        ajaxRequest('POST', getRoute('leadsStore'), data, function(err2, resp2) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<span class="material-icons" style="font-size:16px">save</span> Guardar';
                            if (err2) { showToast(err2, 'error'); return; }
                            showToast(resp2.message || 'Lead creado correctamente', 'success');
                            if (leadFormModal) leadFormModal.hide();
                            if (typeof window.CRM_onLeadSaved === 'function') {
                                window.CRM_onLeadSaved(resp2.data, 'POST');
                            } else {
                                setTimeout(function () { location.reload(); }, 600);
                            }
                        });
                    }
                    return;
                }
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

    function enumVal(e) {
        if (!e) return '';
        if (typeof e === 'string') return e;
        if (typeof e === 'object' && e.value !== undefined) return e.value;
        return String(e);
    }

    function enumLabel(e) {
        if (!e) return '—';
        if (typeof e === 'string') return e;
        if (typeof e === 'object' && e.label !== undefined) return e.label;
        return String(e);
    }

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

        var scoreEl = document.getElementById('detailScore');
        if (scoreEl) {
            var score = lead.score || 0;
            var color, label;
            if (score >= 76) { color = '#10b981'; label = 'Muy alto'; }
            else if (score >= 51) { color = '#f59e0b'; label = 'Alto'; }
            else if (score >= 26) { color = '#3b82f6'; label = 'Medio'; }
            else { color = '#94a3b8'; label = 'Bajo'; }
            scoreEl.innerHTML = '<span style="display:inline-flex;align-items:center;gap:6px;font-weight:600;color:' + color + '">' +
                '<span style="width:10px;height:10px;border-radius:50%;background:' + color + ';display:inline-block"></span>' +
                score + '/100 — ' + label +
            '</span>';
        }

        document.getElementById('detailNotes').textContent = lead.notes || 'Sin notas.';

        // Stage dropdown
        var stageKey = enumVal(lead.stage);
        var stageSelect = document.getElementById('detailStageSelect');
        if (stageSelect) stageSelect.value = stageKey;

        // Store lead ID on modal for stage change handler
        document.getElementById('crmLeadDetailModal').dataset.leadId = lead.id;

        var sourceLabels = { manual:'Manual', website:'Sitio Web', consult:'Consulta', referral:'Referido', social:'Redes Sociales', phone:'Teléfono', facebook_lead_ad:'Facebook Lead Ad', whatsapp:'WhatsApp', messenger:'Messenger', instagram_dm:'Instagram DM', other:'Otro' };
        var sourceKey = enumVal(lead.source);
        document.getElementById('detailSource').textContent = sourceLabels[sourceKey] || enumLabel(lead.source);

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
                var html = '<div class="cd-property-item">'
                    + '<div class="cd-property-name">' + escapeHtml(p.name)
                    + (p.pivot && p.pivot.notes ? '<br><span style="font-size:12px;color:var(--cd-ink-400);font-weight:300">' + escapeHtml(p.pivot.notes) + '</span>' : '')
                    + '</div>'
                    + '<span class="cd-interest-pill ' + level + '">' + level + '</span>'
                    + '<span class="material-icons" style="cursor:pointer;font-size:16px;color:var(--cd-ink-400)" onclick="CRM_removePropertyFromLead(' + p.id + ')" title="Desvincular">close</span>'
                    + '</div>';
                propList.insertAdjacentHTML('beforeend', html);
            });
        } else {
            propEmpty.style.display = '';
        }

        // Board section
        var boardSection = document.getElementById('detailBoardSection');
        if (boardSection) {
            if (lead.boards && lead.boards.length > 0) {
                var board = lead.boards[0]; // Use the first/primary board
                boardSection.style.display = '';

                var baseUrl = window.location.origin;
                var publicUrl = baseUrl + '/board/' + board.token;

                document.getElementById('detailBoardUrl').value = publicUrl;
                document.getElementById('detailBoardViewLink').href = publicUrl;

                var boardName = board.name || 'Tablero';
                var waText = encodeURIComponent(boardName + '\n' + publicUrl);
                document.getElementById('detailBoardWhatsApp').href = 'https://wa.me/?text=' + waText;

                var emailSubject = encodeURIComponent(boardName);
                var emailBody = encodeURIComponent('Te comparto las propiedades seleccionadas:\n' + publicUrl);
                document.getElementById('detailBoardEmail').href = 'mailto:?subject=' + emailSubject + '&body=' + emailBody;
            } else {
                boardSection.style.display = 'none';
            }
        }

        // Add property form
        var addPropHtml = '<div class="cd-prop-add" style="margin-top:16px;padding-top:16px;border-top:1px solid var(--cd-line-soft)">';
        addPropHtml += '<div style="display:flex;gap:8px;align-items:end">';
        addPropHtml += '<div style="flex:1"><label style="font-size:11px;color:var(--cd-ink-400);margin-bottom:4px;display:block">Buscar propiedad</label>';
        addPropHtml += '<select id="detailPropertySearch" style="width:100%"><option></option></select></div>';
        addPropHtml += '<div><label style="font-size:11px;color:var(--cd-ink-400);margin-bottom:4px;display:block">Interés</label>';
        addPropHtml += '<select id="detailPropertyInterest" class="form-select form-select-sm" style="width:100px"><option value="medium">Medio</option><option value="high">Alto</option><option value="low">Bajo</option></select></div>';
        addPropHtml += '<button type="button" class="btn btn-sm btn-primary" onclick="CRM_addPropertyToLead()" style="height:31px"><span class="material-icons" style="font-size:16px">add</span></button>';
        addPropHtml += '</div></div>';
        propList.insertAdjacentHTML('beforeend', addPropHtml);

        // Initialize Select2 on property search
        setTimeout(function() {
            if (window.jQuery && jQuery.fn.select2) {
                jQuery('#detailPropertySearch').select2({
                    placeholder: 'Escribí para buscar...',
                    minimumInputLength: 2,
                    ajax: {
                        url: getRoute('propertiesSearch'),
                        dataType: 'json',
                        delay: 300,
                        data: function(params) { return { q: params.term }; },
                        processResults: function(data) {
                            return { results: (data.data || []).map(function(p) {
                                return { id: p.id, text: p.name + ' - ' + (p.price_formatted || '') };
                            })};
                        }
                    },
                    dropdownParent: jQuery('#crmLeadDetailModal')
                });
            }
        }, 100);

        // Activities tab
        var actTimeline = document.getElementById('detailActivitiesTimeline');
        var actEmpty = document.getElementById('detailActivitiesEmpty');
        actTimeline.innerHTML = '';
        if (lead.activities && lead.activities.length) {
            actEmpty.style.display = 'none';
            var materialIconMap = { note:'sticky_note_2', call:'call', email:'mail_outline', whatsapp:'chat', visit:'event_available', meeting:'groups', meta_auto:'share' };
            lead.activities.forEach(function (a) {
                var aType = enumVal(a.type);
                var aTypeLabel = enumLabel(a.type);
                var mIcon = materialIconMap[aType] || 'notes';
                var html = '<div class="cd-act ' + (a.completed_at ? 'cd-completed' : '') + '">'
                    + '<span class="cd-act-ic" data-k="' + aType + '"><span class="material-icons">' + mIcon + '</span></span>'
                    + '<div class="cd-act-main">'
                    + '<div class="cd-act-top"><span class="cd-act-who">' + escapeHtml(aTypeLabel) + '</span>'
                    + '<span class="cd-act-when">' + (a.user ? escapeHtml(a.user.name || a.user.first_name || '') : 'Sistema') + ' · ' + (a.created_at ? a.created_at.substring(0, 16).replace('T', ' ') : '') + '</span></div>'
                    + '<div class="cd-act-txt">' + escapeHtml(a.description) + '</div>';
                if (a.scheduled_at && !a.completed_at) {
                    html += '<button class="cd-modal-complete-btn btn-complete-activity" data-activity-id="' + a.id + '"><span class="material-icons">check</span> Completar</button>';
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
                        btn.closest('.cd-act').classList.add('cd-completed');
                        btn.remove();
                    });
                });
            });
        } else {
            actEmpty.style.display = '';
        }
    }

    // ---- Property Management ----
    function CRM_addPropertyToLead() {
        var modal = document.getElementById('crmLeadDetailModal');
        var leadId = modal.dataset.leadId;
        var propertyId = jQuery('#detailPropertySearch').val();
        var interestLevel = document.getElementById('detailPropertyInterest').value;
        if (!propertyId) { showToast('Seleccioná una propiedad', 'warning'); return; }
        ajaxRequest('POST', getRoute('leadsAddProperty', {id: leadId}), {
            property_id: propertyId, interest_level: interestLevel
        }, function(err) {
            if (err) { showToast(err, 'error'); return; }
            showToast('Propiedad vinculada');
            window.CRM_openLeadDetail(leadId);
        });
    }

    function CRM_removePropertyFromLead(propertyId) {
        if (!confirm('¿Desvincular esta propiedad?')) return;
        var modal = document.getElementById('crmLeadDetailModal');
        var leadId = modal.dataset.leadId;
        ajaxRequest('DELETE', getRoute('leadsRemoveProperty', {id: leadId, propertyId: propertyId}), {}, function(err) {
            if (err) { showToast(err, 'error'); return; }
            showToast('Propiedad desvinculada');
            window.CRM_openLeadDetail(leadId);
        });
    }

    // ---- Copy Board URL ----
    function CRM_copyBoardUrl() {
        var input = document.getElementById('detailBoardUrl');
        if (!input) return;
        input.select();
        document.execCommand('copy');
        showToast('Enlace copiado al portapapeles');
    }

    // ---- Quick Activity ----
    function CRM_quickActivity(type, description) {
        var modal = document.getElementById('crmLeadDetailModal');
        var leadId = modal.dataset.leadId;
        if (!leadId) return;
        ajaxRequest('POST', getRoute('activitiesStore'), {
            lead_id: leadId,
            type: type,
            description: description
        }, function(err) {
            if (err) { showToast(err, 'error'); return; }
            showToast('Actividad registrada');
            window.CRM_openLeadDetail(leadId);
        });
    }

    // ---- Activity Form Modal ----
    function populateLeadSelect(select, callback) {
        ajaxRequest('GET', getRoute('leadsList'), null, function (err, resp) {
            if (err || !resp.data) { if (callback) callback(); return; }
            select.innerHTML = '<option value="">— Seleccionar lead —</option>';
            resp.data.forEach(function (lead) {
                var opt = document.createElement('option');
                opt.value = lead.id;
                opt.textContent = lead.name;
                select.appendChild(opt);
            });
            if (callback) callback();
        });
    }

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
            if (select) populateLeadSelect(select);
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
        submitBtn.innerHTML = '<span class="material-icons" style="font-size:16px;animation:spin 1s linear infinite">refresh</span> Guardando...';

        ajaxRequest('POST', getRoute('activitiesStore'), data, function (err, resp) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span class="material-icons" style="font-size:16px">save</span> Guardar';

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
        btn.innerHTML = '<span class="material-icons" style="font-size:16px;animation:spin 1s linear infinite">refresh</span> Importando...';

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
    window.CRM_populateLeadSelect = populateLeadSelect;
    window.CRM_showToast = showToast;
    window.CRM_ajaxRequest = ajaxRequest;
    window.CRM_getRoute = getRoute;
    window.CRM_addPropertyToLead = CRM_addPropertyToLead;
    window.CRM_removePropertyFromLead = CRM_removePropertyFromLead;
    window.CRM_quickActivity = CRM_quickActivity;
    window.CRM_copyBoardUrl = CRM_copyBoardUrl;

    // Init when DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModals);
    } else {
        initModals();
    }
})();
