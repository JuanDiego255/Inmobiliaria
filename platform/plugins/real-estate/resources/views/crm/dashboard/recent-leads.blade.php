<div class="crm-card">
    <div class="crm-card-header">
        <h5 class="crm-card-title">Leads Recientes</h5>
        <a href="{{ route('crm.leads.index') }}" class="crm-card-link">Ver todos</a>
    </div>
    <div class="crm-card-body p-0">
        <div class="table-responsive">
            <table class="table crm-table mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Etapa</th>
                        <th>Fuente</th>
                        <th>Agente</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentLeads as $lead)
                        <tr class="crm-clickable-row" data-lead-id="{{ $lead->id }}">
                            <td class="fw-semibold">{{ $lead->name }}</td>
                            <td>{{ $lead->phone ?: '—' }}</td>
                            <td>{!! $lead->stage->toHtml() !!}</td>
                            <td>{!! $lead->source->toHtml() !!}</td>
                            <td>{{ $lead->assignedAgent?->name ?: '—' }}</td>
                            <td>{{ $lead->created_at->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No hay leads registrados aún.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
