(function () {
    'use strict';

    var ROUTES = {
        tasksIndex: '/admin/real-estate/crm/tasks',
        tasksStore: '/admin/real-estate/crm/tasks/store',
        tasksUpdate: '/admin/real-estate/crm/tasks/{id}',
        tasksDestroy: '/admin/real-estate/crm/tasks/{id}',
        tasksUpdateStatus: '/admin/real-estate/crm/tasks/{id}/status',
        myTasks: '/admin/real-estate/crm/tasks/my-tasks',
        remindersStore: '/admin/real-estate/crm/reminders/store',
        remindersPending: '/admin/real-estate/crm/reminders/pending',
        remindersDismiss: '/admin/real-estate/crm/reminders/{id}/dismiss',
        remindersDismissAll: '/admin/real-estate/crm/reminders/dismiss-all',
    };

    function getRoute(name, params) {
        var url = ROUTES[name] || '';
        if (params) {
            Object.keys(params).forEach(function (k) {
                url = url.replace('{' + k + '}', params[k]);
            });
        }
        return url;
    }

    var taskFormModal, reminderFormModal;

    function init() {
        var taskFormEl = document.getElementById('crmTaskFormModal');
        var reminderFormEl = document.getElementById('crmReminderFormModal');

        if (taskFormEl) taskFormModal = new bootstrap.Modal(taskFormEl);
        if (reminderFormEl) reminderFormModal = new bootstrap.Modal(reminderFormEl);

        // New Task buttons
        document.querySelectorAll('.btn-crm-new-task').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                openTaskForm();
            });
        });

        // New Reminder buttons
        document.querySelectorAll('.btn-crm-new-reminder').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                openReminderForm();
            });
        });

        // Task form submit
        var taskForm = document.getElementById('crmTaskForm');
        if (taskForm) {
            taskForm.addEventListener('submit', function (e) {
                e.preventDefault();
                submitTaskForm();
            });
        }

        // Reminder form submit
        var reminderForm = document.getElementById('crmReminderForm');
        if (reminderForm) {
            reminderForm.addEventListener('submit', function (e) {
                e.preventDefault();
                submitReminderForm();
            });
        }

        // Complete task buttons (server-rendered)
        document.querySelectorAll('.crm-task-complete-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                completeTask(btn.dataset.taskId, btn);
            });
        });

        // Dismiss reminder buttons (header bell)
        setupReminderDismiss();

        // Poll reminders every 60s for the bell badge
        pollReminders();
        setInterval(pollReminders, 60000);
    }

    // ---- Task Form ----
    function openTaskForm(task) {
        var form = document.getElementById('crmTaskForm');
        if (!form) return;
        form.reset();

        var title = document.getElementById('crmTaskFormModalTitle');
        var methodField = document.getElementById('crmTaskFormMethod');
        var idField = document.getElementById('crmTaskFormId');
        var leadSelect = document.getElementById('taskLeadId');

        var populateDone = function () {
            if (task) {
                title.textContent = 'Editar Tarea';
                methodField.value = 'PUT';
                idField.value = task.id;
                document.getElementById('taskTitle').value = task.title || '';
                document.getElementById('taskDescription').value = task.description || '';
                document.getElementById('taskAssignedTo').value = task.assigned_to || '';
                if (leadSelect) leadSelect.value = task.lead_id || '';
                document.getElementById('taskPriority').value = task.priority || 'medium';
                document.getElementById('taskStatus').value = task.status || 'pending';
                document.getElementById('taskDueDate').value = task.due_date || '';
            } else {
                title.textContent = 'Nueva Tarea';
                methodField.value = 'POST';
                idField.value = '';
            }
            taskFormModal.show();
        };

        if (leadSelect && typeof window.CRM_populateLeadSelect === 'function') {
            window.CRM_populateLeadSelect(leadSelect, populateDone);
        } else {
            populateDone();
        }
    }

    function submitTaskForm() {
        var form = document.getElementById('crmTaskForm');
        var method = document.getElementById('crmTaskFormMethod').value;
        var taskId = document.getElementById('crmTaskFormId').value;
        var formData = new FormData(form);
        var data = {};
        formData.forEach(function (v, k) {
            if (k !== '_token' && k !== '_method' && k !== 'task_id' && k !== 'create_reminder') data[k] = v;
        });

        var createReminder = document.getElementById('taskCreateReminder').checked;
        var url = method === 'PUT'
            ? getRoute('tasksUpdate', { id: taskId })
            : getRoute('tasksStore');

        var submitBtn = document.getElementById('crmTaskFormSubmit');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="material-icons" style="font-size:16px;animation:spin 1s linear infinite">refresh</span> Guardando...';

        if (typeof window.CRM_ajaxRequest !== 'function') return;

        window.CRM_ajaxRequest(method, url, data, function (err, resp) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span class="material-icons" style="font-size:16px">save</span> Guardar';

            if (err) {
                window.CRM_showToast(err, 'error');
                return;
            }

            window.CRM_showToast(resp.message || 'Tarea guardada', 'success');
            taskFormModal.hide();

            // Create auto-reminder if checked and due_date exists
            if (createReminder && data.due_date && method === 'POST') {
                window.CRM_ajaxRequest('POST', getRoute('remindersStore'), {
                    title: 'Tarea vence: ' + data.title,
                    remind_at: data.due_date + 'T09:00',
                    lead_id: data.lead_id || '',
                }, function () {});
            }

            setTimeout(function () { location.reload(); }, 600);
        });
    }

    // ---- Complete Task ----
    function completeTask(taskId, btn) {
        if (typeof window.CRM_ajaxRequest !== 'function') return;

        btn.disabled = true;
        btn.innerHTML = '<span class="material-icons" style="font-size:14px;animation:spin 1s linear infinite">refresh</span>';

        window.CRM_ajaxRequest('PATCH', getRoute('tasksUpdateStatus', { id: taskId }), { status: 'completed' }, function (err) {
            if (err) {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-icons" style="font-size:14px">check</span>';
                window.CRM_showToast(err, 'error');
                return;
            }

            var item = btn.closest('.cd-task') || btn.closest('.crm-task-item');
            if (item) {
                item.classList.add('cd-done');
                setTimeout(function () { item.remove(); updateTaskCount(); }, 500);
            }
            window.CRM_showToast('Tarea completada', 'success');
        });
    }

    function updateTaskCount() {
        var countBadge = document.getElementById('myTasksCount');
        var list = document.getElementById('myTasksList');
        if (countBadge && list) {
            var items = list.querySelectorAll('.cd-task');
            countBadge.textContent = items.length;
        }
    }

    // ---- Reminder Form ----
    function openReminderForm(leadId) {
        var form = document.getElementById('crmReminderForm');
        if (!form) return;
        form.reset();

        var hiddenLeadId = document.getElementById('reminderLeadId');
        var selectGroup = document.getElementById('reminderLeadSelectGroup');
        var leadSelect = document.getElementById('reminderLeadSelect');

        // Set default remind_at to tomorrow 9am
        var tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        tomorrow.setHours(9, 0, 0, 0);
        var iso = tomorrow.toISOString().slice(0, 16);
        document.getElementById('reminderAt').value = iso;

        if (leadId) {
            hiddenLeadId.value = leadId;
            selectGroup.style.display = 'none';
            reminderFormModal.show();
        } else {
            hiddenLeadId.value = '';
            selectGroup.style.display = '';
            if (leadSelect && typeof window.CRM_populateLeadSelect === 'function') {
                window.CRM_populateLeadSelect(leadSelect, function () {
                    reminderFormModal.show();
                });
            } else {
                reminderFormModal.show();
            }
        }
    }

    function submitReminderForm() {
        var form = document.getElementById('crmReminderForm');
        var formData = new FormData(form);
        var data = {};
        formData.forEach(function (v, k) { if (k !== '_token') data[k] = v; });

        if (!data.lead_id && data.lead_id_select) data.lead_id = data.lead_id_select;
        delete data.lead_id_select;

        var submitBtn = document.getElementById('crmReminderFormSubmit');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="material-icons" style="font-size:16px;animation:spin 1s linear infinite">refresh</span> Creando...';

        if (typeof window.CRM_ajaxRequest !== 'function') return;

        window.CRM_ajaxRequest('POST', getRoute('remindersStore'), data, function (err, resp) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span class="material-icons" style="font-size:16px">notifications</span> Crear Recordatorio';

            if (err) {
                window.CRM_showToast(err, 'error');
                return;
            }

            window.CRM_showToast(resp.message || 'Recordatorio creado', 'success');
            reminderFormModal.hide();
        });
    }

    // ---- Reminder Bell Polling ----
    function pollReminders() {
        if (typeof window.CRM_ajaxRequest !== 'function') return;

        window.CRM_ajaxRequest('GET', getRoute('remindersPending'), null, function (err, resp) {
            if (err) return;
            var reminders = resp.data || [];
            var badge = document.querySelector('.crm-reminder-count');
            if (badge) {
                badge.textContent = reminders.length;
                badge.style.display = reminders.length > 0 ? '' : 'none';
            }
        });
    }

    function setupReminderDismiss() {
        document.querySelectorAll('.crm-dismiss-reminder').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var id = btn.dataset.reminderId;
                if (typeof window.CRM_ajaxRequest !== 'function') return;
                window.CRM_ajaxRequest('PATCH', getRoute('remindersDismiss', { id: id }), {}, function (err) {
                    if (!err) {
                        var item = btn.closest('.crm-reminder-item');
                        if (item) item.remove();
                        pollReminders();
                    }
                });
            });
        });

        document.querySelectorAll('.crm-dismiss-all-reminders').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                if (typeof window.CRM_ajaxRequest !== 'function') return;
                window.CRM_ajaxRequest('POST', getRoute('remindersDismissAll'), {}, function (err) {
                    if (!err) {
                        var list = document.querySelector('.crm-reminders-dropdown .dropdown-menu-list');
                        if (list) list.innerHTML = '<li><a href="javascript:;"><span class="message text-center">No hay recordatorios pendientes.</span></a></li>';
                        pollReminders();
                    }
                });
            });
        });
    }

    // Expose helpers
    window.CRM_openTaskForm = openTaskForm;
    window.CRM_openReminderForm = openReminderForm;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
