<div class="modal fade" id="crmActivityFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Actividad</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="crmActivityForm" method="POST">
                @csrf
                <input type="hidden" name="lead_id" id="activityLeadId" value="">
                <div class="modal-body">
                    <div class="mb-3" id="activityLeadSelectGroup">
                        <label class="form-label">Lead <span class="text-danger">*</span></label>
                        <select class="form-select" name="lead_id_select" id="activityLeadSelect">
                            <option value="">— Seleccionar lead —</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo <span class="text-danger">*</span></label>
                        <div class="crm-activity-type-grid">
                            @foreach (\Botble\RealEstate\Enums\CrmActivityTypeEnum::labels() as $val => $label)
                                @php $icon = \Botble\RealEstate\Enums\CrmActivityTypeEnum::icons()[$val] ?? 'fas fa-circle'; @endphp
                                <label class="crm-activity-type-option">
                                    <input type="radio" name="type" value="{{ $val }}" {{ $loop->first ? 'checked' : '' }}>
                                    <span class="crm-activity-type-btn">
                                        <i class="{{ $icon }}"></i>
                                        <span>{{ $label }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="description" id="activityDescription" rows="3" required maxlength="5000" placeholder="Detalle de la actividad..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Programar para (opcional)</label>
                        <input type="datetime-local" class="form-control" name="scheduled_at" id="activityScheduledAt">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="crmActivityFormSubmit">
                        <i class="fas fa-save me-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
