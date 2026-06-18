<div class="row crm-stat-cards">
    <div class="col-md-3 col-sm-6">
        <div class="crm-stat-card crm-stat-primary">
            <div class="crm-stat-icon"><i class="fas fa-user-plus"></i></div>
            <div class="crm-stat-content">
                <span class="crm-stat-number">{{ $stats['leads_today'] }}</span>
                <span class="crm-stat-label">Leads Hoy</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="crm-stat-card crm-stat-info">
            <div class="crm-stat-icon"><i class="fas fa-clock"></i></div>
            <div class="crm-stat-content">
                <span class="crm-stat-number">{{ $stats['pending_followups'] }}</span>
                <span class="crm-stat-label">Pendientes</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="crm-stat-card crm-stat-danger">
            <div class="crm-stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="crm-stat-content">
                <span class="crm-stat-number">{{ $stats['overdue'] }}</span>
                <span class="crm-stat-label">Atrasados</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="crm-stat-card crm-stat-success">
            <div class="crm-stat-icon"><i class="fas fa-trophy"></i></div>
            <div class="crm-stat-content">
                <span class="crm-stat-number">{{ $stats['won_this_month'] }}</span>
                <span class="crm-stat-label">Ganados este mes</span>
            </div>
        </div>
    </div>
</div>
