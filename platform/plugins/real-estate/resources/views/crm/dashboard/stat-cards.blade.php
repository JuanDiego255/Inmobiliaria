<section class="cd-kpis cd-reveal-up">
    <article class="cd-kpi" data-tone="accent">
        <span class="cd-kpi-ic"><span class="material-icons">person_add</span></span>
        <div><div class="cd-kpi-num cd-tnum">{{ $stats['leads_today'] }}</div><div class="cd-kpi-lab">Leads hoy</div></div>
    </article>
    <article class="cd-kpi" data-tone="gold">
        <span class="cd-kpi-ic"><span class="material-icons">schedule</span></span>
        <div><div class="cd-kpi-num cd-tnum">{{ $stats['pending_followups'] }}</div><div class="cd-kpi-lab">Pendientes</div></div>
    </article>
    <article class="cd-kpi" data-tone="red">
        <span class="cd-kpi-ic"><span class="material-icons">error_outline</span></span>
        <div><div class="cd-kpi-num cd-tnum">{{ $stats['overdue'] }}</div><div class="cd-kpi-lab">Atrasados</div></div>
    </article>
    <article class="cd-kpi" data-tone="green">
        <span class="cd-kpi-ic"><span class="material-icons">emoji_events</span></span>
        <div><div class="cd-kpi-num cd-tnum">{{ $stats['won_this_month'] }}</div><div class="cd-kpi-lab">Ganados este mes</div></div>
    </article>
</section>
