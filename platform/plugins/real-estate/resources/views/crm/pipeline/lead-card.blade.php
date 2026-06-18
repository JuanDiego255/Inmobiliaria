{{-- This is a JS template, rendered client-side --}}
<script type="text/html" id="crm-lead-card-template">
<div class="crm-lead-card" draggable="true" data-lead-id="{id}">
    <div class="crm-lead-card-header">
        <span class="crm-lead-card-name">{name}</span>
        <span class="crm-lead-card-grab"><i class="fas fa-grip-vertical"></i></span>
    </div>
    <div class="crm-lead-card-body">
        <div class="crm-lead-card-info" title="Teléfono">
            <i class="fas fa-phone fa-sm"></i> <span>{phone}</span>
        </div>
        <div class="crm-lead-card-info" title="Agente">
            <i class="fas fa-user fa-sm"></i> <span>{agent}</span>
        </div>
        <div class="crm-lead-card-info crm-lead-card-budget" title="Presupuesto">
            <i class="fas fa-dollar-sign fa-sm"></i> <span>{budget}</span>
        </div>
    </div>
    <div class="crm-lead-card-footer">
        <span class="crm-lead-card-date"><i class="far fa-calendar fa-sm"></i> {date}</span>
        <span class="crm-lead-card-source">{source}</span>
    </div>
</div>
</script>
