<div class="modal fade cd-modal" id="crmTaskFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="crmTaskFormModalTitle">Nueva Tarea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="crmTaskForm" method="POST">
                @csrf
                <input type="hidden" name="_method" id="crmTaskFormMethod" value="POST">
                <input type="hidden" name="task_id" id="crmTaskFormId" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Título <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" id="taskTitle" required maxlength="300" placeholder="Título de la tarea">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Asignar a</label>
                            <select class="form-select" name="assigned_to" id="taskAssignedTo">
                                <option value="">— Yo mismo —</option>
                                @if(isset($adminUsers))
                                    @foreach ($adminUsers as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Lead relacionado</label>
                            <select class="form-select crm-lead-select2" name="lead_id" id="taskLeadId">
                                <option value="">— Ninguno —</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Prioridad</label>
                            <select class="form-select" name="priority" id="taskPriority">
                                @foreach (\Botble\RealEstate\Enums\CrmTaskPriorityEnum::labels() as $val => $label)
                                    <option value="{{ $val }}" {{ $val === 'medium' ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="status" id="taskStatus">
                                @foreach (\Botble\RealEstate\Enums\CrmTaskStatusEnum::labels() as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Fecha límite</label>
                            <input type="date" class="form-control" name="due_date" id="taskDueDate">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="description" id="taskDescription" rows="3" maxlength="5000" placeholder="Descripción de la tarea..."></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="taskCreateReminder" name="create_reminder">
                        <label class="form-check-label" for="taskCreateReminder">
                            <span class="material-icons" style="font-size:16px;color:var(--cd-s-calificado)">notifications</span> Crear recordatorio automático al vencer
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="cd-btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="cd-btn-primary" id="crmTaskFormSubmit">
                        <span class="material-icons">save</span> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
