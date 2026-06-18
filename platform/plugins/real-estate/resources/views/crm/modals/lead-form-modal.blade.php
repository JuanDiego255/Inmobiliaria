<div class="modal fade" id="crmLeadFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="crmLeadFormModalTitle">Nuevo Lead</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="crmLeadForm" method="POST">
                @csrf
                <input type="hidden" name="_method" id="crmLeadFormMethod" value="POST">
                <input type="hidden" name="lead_id" id="crmLeadFormId" value="">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="leadName" required maxlength="200" placeholder="Nombre del contacto">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="leadEmail" maxlength="120" placeholder="email@ejemplo.com">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control" name="phone" id="leadPhone" maxlength="25" placeholder="+506 8888-8888">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Agente asignado</label>
                            <select class="form-select" name="assigned_agent_id" id="leadAgent">
                                <option value="">— Sin asignar —</option>
                                @foreach ($agents as $agent)
                                    <option value="{{ $agent->id }}">{{ $agent->first_name }} {{ $agent->last_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Etapa</label>
                            <select class="form-select" name="stage" id="leadStage">
                                @foreach (\Botble\RealEstate\Enums\CrmLeadStageEnum::labels() as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fuente</label>
                            <select class="form-select" name="source" id="leadSource">
                                @foreach (\Botble\RealEstate\Enums\CrmLeadSourceEnum::labels() as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Presupuesto mín.</label>
                            <input type="number" class="form-control" name="budget_min" id="leadBudgetMin" step="0.01" min="0" placeholder="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Presupuesto máx.</label>
                            <input type="number" class="form-control" name="budget_max" id="leadBudgetMax" step="0.01" min="0" placeholder="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Fecha estimada cierre</label>
                            <input type="date" class="form-control" name="expected_close_date" id="leadCloseDate">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notas</label>
                        <textarea class="form-control" name="notes" id="leadNotes" rows="3" maxlength="5000" placeholder="Notas adicionales..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="crmLeadFormSubmit">
                        <i class="fas fa-save me-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
