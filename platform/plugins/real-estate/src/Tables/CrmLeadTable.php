<?php

namespace Botble\RealEstate\Tables;

use Botble\RealEstate\Enums\CrmLeadSourceEnum;
use Botble\RealEstate\Enums\CrmLeadStageEnum;
use Botble\RealEstate\Models\CrmLead;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\NameColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;

class CrmLeadTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(CrmLead::class)
            ->addActions([
                DeleteAction::make()->route('crm.leads.destroy'),
            ]);
    }

    public function query(): Relation|Builder|QueryBuilder
    {
        $query = $this
            ->getModel()
            ->query()
            ->with(['assignedAgent'])
            ->select([
                'id',
                'name',
                'phone',
                'email',
                'stage',
                'source',
                'assigned_agent_id',
                'created_at',
            ]);

        return $this->applyScopes($query);
    }

    public function columns(): array
    {
        return [
            IdColumn::make(),
            NameColumn::make()
                ->route('')
                ->alignStart(),
            Column::make('phone')
                ->title('Teléfono'),
            Column::make('stage')
                ->title('Etapa'),
            Column::make('source')
                ->title('Fuente'),
            Column::make('assigned_agent_id')
                ->title('Agente')
                ->searchable(false)
                ->orderable(false),
            CreatedAtColumn::make(),
        ];
    }

    public function buttons(): array
    {
        return [
            'create' => [
                'link' => '#',
                'text' => '<i class="fa fa-plus"></i> Nuevo Lead',
                'class' => 'btn-primary btn-crm-new-lead',
            ],
            'pipeline' => [
                'link' => route('crm.pipeline'),
                'text' => '<i class="fas fa-columns"></i> Pipeline',
                'class' => 'btn-outline-primary',
            ],
            'dashboard' => [
                'link' => route('crm.dashboard'),
                'text' => '<i class="fas fa-tachometer-alt"></i> Dashboard',
                'class' => 'btn-outline-info',
            ],
        ];
    }

    public function bulkActions(): array
    {
        return [
            DeleteBulkAction::make()->permission('crm-lead.destroy'),
        ];
    }

    public function getBulkChanges(): array
    {
        return [
            'name' => [
                'title' => 'Nombre',
                'type' => 'text',
                'validate' => 'required|max:200',
            ],
            'stage' => [
                'title' => 'Etapa',
                'type' => 'select',
                'choices' => CrmLeadStageEnum::labels(),
                'validate' => 'required|in:' . implode(',', CrmLeadStageEnum::values()),
            ],
            'source' => [
                'title' => 'Fuente',
                'type' => 'select',
                'choices' => CrmLeadSourceEnum::labels(),
                'validate' => 'required|in:' . implode(',', CrmLeadSourceEnum::values()),
            ],
            'created_at' => [
                'title' => 'Fecha de creación',
                'type' => 'datePicker',
            ],
        ];
    }
}
