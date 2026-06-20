<div class="modal fade cd-modal" id="crmReminderFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Recordatorio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="crmReminderForm" method="POST">
                @csrf
                <input type="hidden" name="lead_id" id="reminderLeadId" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Título <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" id="reminderTitle" required maxlength="300" placeholder="Ej: Llamar a cliente por oferta">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Recordarme en <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" name="remind_at" id="reminderAt" required>
                    </div>
                    <div class="mb-3" id="reminderLeadSelectGroup">
                        <label class="form-label">Lead relacionado</label>
                        <select class="form-select" name="lead_id_select" id="reminderLeadSelect">
                            <option value="">— Ninguno —</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="cd-btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="cd-btn-primary" id="crmReminderFormSubmit">
                        <span class="material-icons">notifications</span> Crear Recordatorio
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
