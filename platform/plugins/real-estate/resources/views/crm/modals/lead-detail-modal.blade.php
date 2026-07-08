<div class="modal fade cd-modal" id="crmLeadDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle del Lead: <span id="detailLeadName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <ul class="nav cd-detail-tabs" id="leadDetailTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabGeneral" type="button">
                            <span class="material-icons">info</span> General
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabProperties" type="button">
                            <span class="material-icons">home</span> Propiedades
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabActivities" type="button">
                            <span class="material-icons">history</span> Actividades
                        </button>
                    </li>
                </ul>

                <div class="tab-content pt-3" id="leadDetailTabContent">
                    {{-- Tab General --}}
                    <div class="tab-pane fade show active" id="tabGeneral">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="cd-detail-table">
                                    <tr><th>Nombre</th><td id="detailName"></td></tr>
                                    <tr><th>Email</th><td id="detailEmail"></td></tr>
                                    <tr><th>Teléfono</th><td id="detailPhone"></td></tr>
                                    <tr><th>Etapa</th>
                                        <td>
                                            <select id="detailStageSelect" class="form-control form-control-sm" style="width:auto;display:inline-block;font-size:13px;padding:4px 8px;border-radius:6px;cursor:pointer">
                                                @foreach(\Botble\RealEstate\Enums\CrmLeadStageEnum::labels() as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <span id="detailStageStatus" style="font-size:11px;color:var(--cd-ink-400);margin-left:6px;display:none"></span>
                                        </td>
                                    </tr>
                                    <tr><th>Fuente</th><td id="detailSource"></td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="cd-detail-table">
                                    <tr><th>Agente</th><td id="detailAgent"></td></tr>
                                    <tr><th>Presupuesto</th><td id="detailBudget"></td></tr>
                                    <tr><th>Cierre estimado</th><td id="detailCloseDate"></td></tr>
                                    <tr><th>Último contacto</th><td id="detailLastContact"></td></tr>
                                    <tr><th>Creado</th><td id="detailCreated"></td></tr>
                                    <tr><th>Score</th><td><span id="detailScore" class="cd-score-badge"></span></td></tr>
                                </table>
                            </div>
                        </div>
                        <div class="mt-3" style="font-size:13px">
                            <span style="font-size:11.5px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--cd-ink-400)">Notas</span>
                            <p id="detailNotes" style="margin-top:6px;color:var(--cd-ink-600);line-height:1.5"></p>
                        </div>
                        <div style="display:flex;gap:10px;margin-top:16px">
                            <button class="cd-btn-outline btn-crm-edit-lead" id="detailEditBtn">
                                <span class="material-icons">edit</span> Editar
                            </button>
                            <button class="cd-btn-outline btn-crm-add-activity-from-detail" id="detailAddActivityBtn">
                                <span class="material-icons">add_circle_outline</span> Registrar Actividad
                            </button>
                        </div>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:10px">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="CRM_quickActivity('call', 'Llamada de seguimiento realizada')" title="Registrar llamada">
                                <span class="material-icons" style="font-size:15px;vertical-align:middle">call</span> Llamada
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="CRM_quickActivity('email', 'Email de seguimiento enviado')" title="Registrar email">
                                <span class="material-icons" style="font-size:15px;vertical-align:middle">mail_outline</span> Email
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="CRM_quickActivity('whatsapp', 'Mensaje de WhatsApp enviado')" title="Registrar WhatsApp">
                                <span class="material-icons" style="font-size:15px;vertical-align:middle">chat</span> WhatsApp
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="CRM_quickActivity('visit', 'Visita a propiedad realizada')" title="Registrar visita">
                                <span class="material-icons" style="font-size:15px;vertical-align:middle">event_available</span> Visita
                            </button>
                        </div>
                    </div>

                    {{-- Tab Propiedades --}}
                    <div class="tab-pane fade" id="tabProperties">
                        <div id="detailBoardSection" style="display:none;margin-bottom:16px;padding:14px 16px;background:var(--cd-bg-raised);border-radius:var(--cd-radius-sm);border:1px solid var(--cd-line-soft)">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
                                <span style="font-size:13px;font-weight:600;color:var(--cd-ink-700)">
                                    <span class="material-icons" style="font-size:16px;vertical-align:middle;margin-right:4px">dashboard</span>
                                    Tablero compartido
                                </span>
                                <a id="detailBoardViewLink" href="#" target="_blank" style="font-size:12px;color:var(--cd-accent);text-decoration:none">
                                    Ver tablero <span class="material-icons" style="font-size:14px;vertical-align:middle">open_in_new</span>
                                </a>
                            </div>
                            <div style="display:flex;gap:8px;align-items:center">
                                <input type="text" id="detailBoardUrl" readonly style="flex:1;font-size:12px;padding:6px 10px;border:1px solid var(--cd-line);border-radius:6px;background:var(--cd-bg);color:var(--cd-ink-600);font-family:monospace" />
                                <button type="button" onclick="CRM_copyBoardUrl()" class="btn btn-sm btn-outline-secondary" title="Copiar enlace" style="height:31px">
                                    <span class="material-icons" style="font-size:16px">content_copy</span>
                                </button>
                                <a id="detailBoardWhatsApp" href="#" target="_blank" class="btn btn-sm btn-outline-success" title="Compartir por WhatsApp" style="height:31px">
                                    <span class="material-icons" style="font-size:16px">chat</span>
                                </a>
                                <a id="detailBoardEmail" href="#" class="btn btn-sm btn-outline-primary" title="Compartir por Email" style="height:31px">
                                    <span class="material-icons" style="font-size:16px">mail_outline</span>
                                </a>
                            </div>
                        </div>
                        <div id="detailPropertiesList"></div>
                        <div class="cd-empty" id="detailPropertiesEmpty">
                            <span class="material-icons">home_work</span>
                            <span>No hay propiedades asociadas.</span>
                        </div>
                    </div>

                    {{-- Tab Actividades --}}
                    <div class="tab-pane fade" id="tabActivities">
                        <div id="detailActivitiesTimeline" class="cd-modal-timeline cd-feed"></div>
                        <div class="cd-empty" id="detailActivitiesEmpty">
                            <span class="material-icons">history</span>
                            <span>No hay actividades registradas.</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cd-btn-ghost" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
