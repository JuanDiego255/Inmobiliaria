@php
    $sourceIcons = [
        'manual' => 'edit',
        'website' => 'language',
        'consult' => 'public',
        'referral' => 'person',
        'social' => 'public',
        'phone' => 'call',
        'other' => 'edit',
    ];
    $sourceLabels = \Botble\RealEstate\Enums\CrmLeadSourceEnum::labels();
    $stageLabelsMap = \Botble\RealEstate\Enums\CrmLeadStageEnum::labels();
@endphp
<section class="cd-card cd-reveal-up">
    <div class="cd-card-head">
        <div class="cd-ti"><h3>Leads recientes</h3></div>
        <a class="cd-card-link" href="{{ route('crm.leads.index') }}">Ver todos <span class="material-icons">arrow_right_alt</span></a>
    </div>
    <div class="cd-card-body">
        @if ($recentLeads->isEmpty())
            <div class="cd-empty">
                <span class="material-icons">person_search</span>
                <span>No hay leads registrados aún.</span>
            </div>
        @else
            <table class="cd-lead-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th class="cd-hide-sm">Teléfono</th>
                        <th>Etapa</th>
                        <th class="cd-hide-sm">Fuente</th>
                        <th class="cd-hide-sm">Agente</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentLeads as $lead)
                        @php
                            $initials = collect(explode(' ', $lead->name))->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->join('');
                            $stageVal = $lead->stage->getValue();
                            $sourceVal = $lead->source->getValue();
                        @endphp
                        <tr class="crm-clickable-row" data-lead-id="{{ $lead->id }}">
                            <td>
                                <span class="cd-lead-name">
                                    <span class="cd-lead-ava">{{ $initials }}</span>
                                    <b>{{ $lead->name }}</b>
                                </span>
                            </td>
                            <td class="cd-hide-sm cd-tel">{{ $lead->phone ?: '—' }}</td>
                            <td>
                                <span class="cd-pill cd-stage-{{ $stageVal }}">
                                    <span class="cd-d"></span>{{ $stageLabelsMap[$stageVal] ?? $stageVal }}
                                </span>
                            </td>
                            <td class="cd-hide-sm">
                                <span class="cd-pill cd-source">
                                    <span class="material-icons">{{ $sourceIcons[$sourceVal] ?? 'edit' }}</span>{{ $sourceLabels[$sourceVal] ?? $sourceVal }}
                                </span>
                            </td>
                            <td class="cd-hide-sm">{{ $lead->assignedAgent ? ($lead->assignedAgent->first_name . ' ' . $lead->assignedAgent->last_name) : '—' }}</td>
                            <td>{{ $lead->created_at->format('d/m/Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</section>
