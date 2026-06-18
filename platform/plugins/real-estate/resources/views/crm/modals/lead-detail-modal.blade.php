<div class="modal fade" id="crmLeadDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle del Lead: <span id="detailLeadName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs crm-detail-tabs" id="leadDetailTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabGeneral" type="button">
                            <i class="fas fa-info-circle me-1"></i>General
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabProperties" type="button">
                            <i class="fas fa-home me-1"></i>Propiedades
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabActivities" type="button">
                            <i class="fas fa-history me-1"></i>Actividades
                        </button>
                    </li>
                </ul>

                <div class="tab-content pt-3" id="leadDetailTabContent">
                    {{-- Tab General --}}
                    <div class="tab-pane fade show active" id="tabGeneral">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm crm-detail-table">
                                    <tr><th>Nombre</th><td id="detailName"></td></tr>
                                    <tr><th>Email</th><td id="detailEmail"></td></tr>
                                    <tr><th>Teléfono</th><td id="detailPhone"></td></tr>
                                    <tr><th>Etapa</th><td id="detailStage"></td></tr>
                                    <tr><th>Fuente</th><td id="detailSource"></td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm crm-detail-table">
                                    <tr><th>Agente</th><td id="detailAgent"></td></tr>
                                    <tr><th>Presupuesto</th><td id="detailBudget"></td></tr>
                                    <tr><th>Cierre estimado</th><td id="detailCloseDate"></td></tr>
                                    <tr><th>Último contacto</th><td id="detailLastContact"></td></tr>
                                    <tr><th>Creado</th><td id="detailCreated"></td></tr>
                                </table>
                            </div>
                        </div>
                        <div class="mt-2">
                            <strong>Notas:</strong>
                            <p id="detailNotes" class="text-muted mt-1"></p>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <button class="btn btn-sm btn-outline-primary btn-crm-edit-lead" id="detailEditBtn">
                                <i class="fas fa-edit me-1"></i>Editar
                            </button>
                            <button class="btn btn-sm btn-outline-success btn-crm-add-activity-from-detail" id="detailAddActivityBtn">
                                <i class="fas fa-plus me-1"></i>Registrar Actividad
                            </button>
                        </div>
                    </div>

                    {{-- Tab Propiedades --}}
                    <div class="tab-pane fade" id="tabProperties">
                        <div id="detailPropertiesList" class="crm-properties-list"></div>
                        <div class="text-muted text-center py-3" id="detailPropertiesEmpty">
                            No hay propiedades asociadas.
                        </div>
                    </div>

                    {{-- Tab Actividades --}}
                    <div class="tab-pane fade" id="tabActivities">
                        <div id="detailActivitiesTimeline" class="crm-timeline"></div>
                        <div class="text-muted text-center py-3" id="detailActivitiesEmpty">
                            No hay actividades registradas.
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
