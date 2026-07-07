<?php

namespace Botble\RealEstate\Exports;

use Botble\RealEstate\Models\CrmLead;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CrmLeadsExport implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;

    public function collection()
    {
        return CrmLead::query()
            ->with(['assignedAgent', 'currency'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Email',
            'Teléfono',
            'Etapa',
            'Fuente',
            'Agente Asignado',
            'Presupuesto Mín',
            'Presupuesto Máx',
            'Moneda',
            'Cierre Estimado',
            'Último Contacto',
            'Notas',
            'Creado',
        ];
    }

    public function map($lead): array
    {
        return [
            $lead->id,
            $lead->name,
            $lead->email ?? '',
            $lead->phone ?? '',
            $lead->stage ? $lead->stage->label() : '',
            $lead->source ? $lead->source->label() : '',
            $lead->assignedAgent ? $lead->assignedAgent->first_name . ' ' . $lead->assignedAgent->last_name : '',
            $lead->budget_min ?? '',
            $lead->budget_max ?? '',
            $lead->currency ? $lead->currency->title : '',
            $lead->expected_close_date ? $lead->expected_close_date->format('d/m/Y') : '',
            $lead->last_contacted_at ? $lead->last_contacted_at->format('d/m/Y H:i') : '',
            $lead->notes ?? '',
            $lead->created_at->format('d/m/Y H:i'),
        ];
    }
}
