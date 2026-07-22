(function () {
    'use strict';

    var calendar;
    var detailModal, newEventModal;
    var activeSources = ['tasks', 'reminders', 'activities', 'leads', 'google'];
    var cfg = {};

    function init() {
        var calEl = document.getElementById('crmCalendar');
        if (!calEl) return;

        cfg = {
            eventsUrl: calEl.getAttribute('data-events-url') || '',
            updateUrl: calEl.getAttribute('data-update-url') || '',
            storeUrl: calEl.getAttribute('data-store-url') || '',
            deleteUrl: calEl.getAttribute('data-delete-url') || '',
            csrfToken: calEl.getAttribute('data-csrf') || '',
            isGcalConnected: calEl.getAttribute('data-gcal') === '1',
        };

        console.log('[CRM Calendar] Config loaded:', JSON.stringify(cfg));

        if (!cfg.eventsUrl) {
            console.error('[CRM Calendar] eventsUrl is empty - data attributes missing from #crmCalendar');
            return;
        }

        detailModal = new bootstrap.Modal(document.getElementById('calEventDetailModal'));
        newEventModal = new bootstrap.Modal(document.getElementById('calNewEventModal'));

        calendar = new FullCalendar.Calendar(calEl, {
            locale: 'es',
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            buttonText: {
                today: 'Hoy',
                month: 'Mes',
                week: 'Semana',
                day: 'Día',
                list: 'Lista'
            },
            height: 'auto',
            navLinks: true,
            editable: true,
            selectable: true,
            dayMaxEvents: 4,
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            },

            events: function (info, successCallback, failureCallback) {
                fetchEvents(info.startStr, info.endStr, successCallback, failureCallback);
            },

            eventClick: function (info) {
                info.jsEvent.preventDefault();
                showEventDetail(info.event);
            },

            eventDrop: function (info) {
                moveEvent(info.event, info.revert);
            },

            eventResize: function (info) {
                moveEvent(info.event, info.revert);
            },

            select: function (info) {
                openNewEventForm(info.startStr, info.endStr);
                calendar.unselect();
            },

            dateClick: function (info) {
                if (calendar.view.type === 'dayGridMonth') {
                    openNewEventForm(info.dateStr);
                }
            },

            eventDidMount: function (info) {
                var ep = info.event.extendedProps || {};
                var tip = info.event.title;
                if (ep.lead_name) tip += '\nLead: ' + ep.lead_name;
                if (ep.assigned_to) tip += '\nAsignado: ' + ep.assigned_to;
                info.el.title = tip;
            }
        });

        calendar.render();

        // Filter chips
        document.querySelectorAll('.cal-filter-chip').forEach(function (chip) {
            chip.addEventListener('click', function () {
                chip.classList.toggle('cal-filter-active');
                rebuildSources();
                calendar.refetchEvents();
            });
        });

        // New event button
        document.getElementById('calNewEventBtn').addEventListener('click', function () {
            var now = new Date();
            now.setMinutes(0, 0, 0);
            now.setHours(now.getHours() + 1);
            openNewEventForm(toLocalISO(now));
        });

        // New event form type toggle
        document.getElementById('calNewType').addEventListener('change', toggleNewFormFields);

        // New event form submit
        document.getElementById('calNewEventForm').addEventListener('submit', function (e) {
            e.preventDefault();
            submitNewEvent();
        });

        // Delete button
        document.getElementById('calEventDeleteBtn').addEventListener('click', function () {
            var eventId = this.dataset.eventId;
            if (!eventId) return;
            if (!confirm('¿Eliminar este evento? Si está sincronizado con Google Calendar también se eliminará ahí.')) return;
            deleteEvent(eventId);
        });

        // Populate leads for the new event form
        populateLeads();
    }

    function rebuildSources() {
        activeSources = [];
        document.querySelectorAll('.cal-filter-chip.cal-filter-active').forEach(function (chip) {
            activeSources.push(chip.dataset.source);
        });
    }

    function fetchEvents(start, end, success, failure) {
        var xhr = new XMLHttpRequest();
        var params = 'start=' + encodeURIComponent(start) +
            '&end=' + encodeURIComponent(end) +
            '&sources=' + encodeURIComponent(activeSources.join(',')) +
            '&include_google=' + (activeSources.indexOf('google') !== -1 ? '1' : '0');

        xhr.open('GET', cfg.eventsUrl + '?' + params);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function () {
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    success(data.data || data);
                } catch (e) {
                    failure(e);
                }
            } else {
                failure(new Error('HTTP ' + xhr.status));
            }
        };
        xhr.onerror = function () { failure(new Error('Network error')); };
        xhr.send();
    }

    function showEventDetail(event) {
        var ep = event.extendedProps || {};
        var type = ep.type || event.source || '';
        var title = event.title;
        var html = '';

        var typeLabels = {
            task: '<span style="background:#e65100;color:#fff;padding:2px 10px;border-radius:12px;font-size:.78rem">Tarea</span>',
            reminder: '<span style="background:#7b1fa2;color:#fff;padding:2px 10px;border-radius:12px;font-size:.78rem">Recordatorio</span>',
            activity: '<span style="background:#0288d1;color:#fff;padding:2px 10px;border-radius:12px;font-size:.78rem">Actividad</span>',
            lead: '<span style="background:#00897b;color:#fff;padding:2px 10px;border-radius:12px;font-size:.78rem">Lead</span>',
            google: '<span style="background:#4285f4;color:#fff;padding:2px 10px;border-radius:12px;font-size:.78rem">Google Calendar</span>'
        };

        html += '<div style="margin-bottom:12px">' + (typeLabels[type] || '') + '</div>';
        html += '<h5 style="margin-bottom:12px">' + escapeHtml(title) + '</h5>';

        if (event.start) {
            html += '<div style="margin-bottom:8px;color:#666"><span class="material-icons" style="font-size:16px;vertical-align:middle">schedule</span> ';
            html += formatDate(event.start);
            if (event.end && event.end.getTime() !== event.start.getTime()) {
                html += ' — ' + formatDate(event.end);
            }
            html += '</div>';
        }

        if (ep.description) {
            html += '<div style="margin-bottom:8px;padding:10px;background:#f5f5f5;border-radius:8px;font-size:.86rem">' + escapeHtml(ep.description) + '</div>';
        }

        if (ep.lead_name) {
            html += '<div style="margin-bottom:6px;font-size:.86rem"><strong>Lead:</strong> ' + escapeHtml(ep.lead_name) + '</div>';
        }
        if (ep.assigned_to) {
            html += '<div style="margin-bottom:6px;font-size:.86rem"><strong>Asignado:</strong> ' + escapeHtml(ep.assigned_to) + '</div>';
        }
        if (ep.priority) {
            var prioColors = { low: '#43a047', medium: '#e65100', high: '#c62828' };
            var prioLabels = { low: 'Baja', medium: 'Media', high: 'Alta' };
            html += '<div style="margin-bottom:6px;font-size:.86rem"><strong>Prioridad:</strong> <span style="color:' + (prioColors[ep.priority] || '#666') + '">' + (prioLabels[ep.priority] || ep.priority) + '</span></div>';
        }
        if (ep.status) {
            var statusLabels = { pending: 'Pendiente', in_progress: 'En progreso', completed: 'Completada', cancelled: 'Cancelada' };
            html += '<div style="margin-bottom:6px;font-size:.86rem"><strong>Estado:</strong> ' + (statusLabels[ep.status] || ep.status) + '</div>';
        }

        if (type === 'google' && event.url) {
            html += '<div style="margin-top:12px"><a href="' + event.url + '" target="_blank" rel="noopener" style="color:#4285f4;font-size:.86rem"><span class="material-icons" style="font-size:14px;vertical-align:middle">open_in_new</span> Ver en Google Calendar</a></div>';
        }

        document.getElementById('calEventDetailTitle').textContent = title;
        document.getElementById('calEventDetailBody').innerHTML = html;

        var deleteBtn = document.getElementById('calEventDeleteBtn');
        if (type === 'task' || type === 'reminder' || type === 'gcal' || type === 'google') {
            deleteBtn.style.display = '';
            deleteBtn.dataset.eventId = event.id;
        } else {
            deleteBtn.style.display = 'none';
        }

        detailModal.show();
    }

    function openNewEventForm(startStr, endStr) {
        var form = document.getElementById('calNewEventForm');
        form.reset();

        var startInput = document.getElementById('calNewStart');
        if (startStr) {
            if (startStr.length <= 10) startStr += 'T09:00';
            startInput.value = startStr.substring(0, 16);
        }

        toggleNewFormFields();
        newEventModal.show();
        setTimeout(function () { document.getElementById('calNewTitle').focus(); }, 300);
    }

    function toggleNewFormFields() {
        var type = document.getElementById('calNewType').value;
        var isTask = type === 'task';
        document.getElementById('calNewDescGroup').style.display = isTask ? '' : 'none';
        document.getElementById('calNewPriorityGroup').style.display = isTask ? '' : 'none';
        document.getElementById('calNewAssignGroup').style.display = isTask ? '' : 'none';
    }

    function submitNewEvent() {
        var form = document.getElementById('calNewEventForm');
        var formData = new FormData(form);
        var data = {};
        formData.forEach(function (v, k) { data[k] = v; });

        var btn = document.getElementById('calNewSubmit');
        btn.disabled = true;
        btn.innerHTML = '<span class="material-icons" style="font-size:16px;animation:spin 1s linear infinite">refresh</span> Creando...';

        ajaxPost(cfg.storeUrl, data, function (err, resp) {
            btn.disabled = false;
            btn.innerHTML = '<span class="material-icons" style="font-size:16px">save</span> Crear';

            if (err) {
                showToast(err, 'error');
                return;
            }

            showToast(resp.message || 'Evento creado', 'success');
            newEventModal.hide();
            calendar.refetchEvents();
        });
    }

    function moveEvent(event, revert) {
        var data = {
            id: event.id,
            start: event.start ? event.start.toISOString() : '',
            end: event.end ? event.end.toISOString() : ''
        };

        ajaxPost(cfg.updateUrl, data, function (err) {
            if (err) {
                showToast(err, 'error');
                revert();
            } else {
                showToast('Evento movido', 'success');
            }
        }, 'PUT');
    }

    function deleteEvent(eventId) {
        var sourceId = eventId;
        if (eventId.indexOf('gcal_') === 0 || eventId.indexOf('google_') === 0) {
            sourceId = eventId;
        }

        var xhr = new XMLHttpRequest();
        xhr.open('DELETE', cfg.deleteUrl + '/' + encodeURIComponent(sourceId));
        xhr.setRequestHeader('X-CSRF-TOKEN', cfg.csrfToken);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onload = function () {
            if (xhr.status >= 200 && xhr.status < 300) {
                showToast('Evento eliminado', 'success');
                detailModal.hide();
                calendar.refetchEvents();
            } else {
                showToast('Error al eliminar', 'error');
            }
        };
        xhr.onerror = function () { showToast('Error de red', 'error'); };
        xhr.send();
    }

    function populateLeads() {
        if (typeof window.CRM_populateLeadSelect === 'function') {
            var select = document.getElementById('calNewLead');
            window.CRM_populateLeadSelect(select, function () {});
        }
    }

    // ---- Helpers ----

    function ajaxPost(url, data, callback, method) {
        method = method || 'POST';
        var xhr = new XMLHttpRequest();
        xhr.open(method, url);
        xhr.setRequestHeader('X-CSRF-TOKEN', cfg.csrfToken);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function () {
            try {
                var resp = JSON.parse(xhr.responseText);
                if (xhr.status >= 200 && xhr.status < 300 && !resp.error) {
                    callback(null, resp);
                } else {
                    callback(resp.message || 'Error del servidor');
                }
            } catch (e) {
                callback('Error al procesar respuesta');
            }
        };
        xhr.onerror = function () { callback('Error de red'); };
        xhr.send(JSON.stringify(data));
    }

    function showToast(msg, type) {
        if (type === 'error' && typeof Botble !== 'undefined' && Botble.showError) {
            Botble.showError(msg);
        } else if (typeof Botble !== 'undefined' && Botble.showSuccess) {
            Botble.showSuccess(msg);
        } else if (typeof window.CRM_showToast === 'function') {
            window.CRM_showToast(msg, type);
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function formatDate(d) {
        if (!d) return '';
        var options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        return d.toLocaleDateString('es-CR', options);
    }

    function toLocalISO(d) {
        var pad = function (n) { return n < 10 ? '0' + n : n; };
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) +
            'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
