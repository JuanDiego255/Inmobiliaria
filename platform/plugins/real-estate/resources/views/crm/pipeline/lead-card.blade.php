{{-- This is a JS template, rendered client-side --}}
<script type="text/html" id="crm-lead-card-template">
<div class="cd-lead-card" draggable="true" data-lead-id="{id}">
    <div class="cd-lead-card-header">
        <span class="cd-lead-card-name">{name}</span>
        <span class="cd-lead-card-grab"><span class="material-icons">drag_indicator</span></span>
    </div>
    <div class="cd-lead-card-body">
        <div class="cd-lead-card-info" title="Teléfono">
            <span class="material-icons">call</span> <span>{phone}</span>
        </div>
        <div class="cd-lead-card-info" title="Agente">
            <span class="material-icons">person</span> <span>{agent}</span>
        </div>
        <div class="cd-lead-card-info cd-lead-card-budget" title="Presupuesto">
            <span class="material-icons">payments</span> <span>{budget}</span>
        </div>
    </div>
    <div class="cd-lead-card-footer">
        <span class="cd-lead-card-date"><span class="material-icons">calendar_today</span> {date}</span>
        <span class="cd-lead-card-source">{source}</span>
    </div>
</div>
</script>
