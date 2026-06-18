<div class="crm-card">
    <div class="crm-card-header">
        <h5 class="crm-card-title">Acciones Rápidas</h5>
    </div>
    <div class="crm-card-body">
        <div class="d-grid gap-2">
            <button type="button" class="btn btn-primary btn-crm-new-lead">
                <i class="fas fa-user-plus me-2"></i>Nuevo Lead
            </button>
            <button type="button" class="btn btn-outline-primary btn-crm-new-activity-quick">
                <i class="fas fa-plus-circle me-2"></i>Registrar Actividad
            </button>
            <button type="button" class="btn btn-outline-secondary btn-crm-import-consults">
                <i class="fas fa-file-import me-2"></i>Importar Consultas
            </button>
            <a href="{{ route('crm.pipeline') }}" class="btn btn-outline-info">
                <i class="fas fa-columns me-2"></i>Ver Pipeline
            </a>
        </div>
    </div>
</div>
