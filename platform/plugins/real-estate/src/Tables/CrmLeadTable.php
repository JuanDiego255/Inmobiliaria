<?php

namespace Botble\RealEstate\Tables;

use Botble\Base\Facades\BaseHelper;
use Botble\RealEstate\Enums\CrmLeadSourceEnum;
use Botble\RealEstate\Enums\CrmLeadStageEnum;
use Botble\RealEstate\Models\CrmLead;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\IdColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;

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

    public function ajax(): JsonResponse
    {
        $data = $this->table
            ->eloquent($this->query())
            ->editColumn('name', function (CrmLead $item) {
                return BaseHelper::clean($item->name);
            })
            ->editColumn('stage', function (CrmLead $item) {
                if (! $item->stage) {
                    return '&mdash;';
                }

                return $item->stage->toHtml();
            })
            ->editColumn('source', function (CrmLead $item) {
                if (! $item->source) {
                    return '&mdash;';
                }

                return $item->source->toHtml();
            })
            ->editColumn('assigned_agent_id', function (CrmLead $item) {
                if (! $item->assigned_agent_id || ! $item->assignedAgent) {
                    return '&mdash;';
                }

                return BaseHelper::clean($item->assignedAgent->first_name . ' ' . $item->assignedAgent->last_name);
            })
            ->filter(function ($query) {
                $keyword = $this->request->input('search.value');

                if (! $keyword) {
                    return $query;
                }

                return $query->where(function ($query) use ($keyword) {
                    $query
                        ->where('id', $keyword)
                        ->orWhere('name', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('email', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('phone', 'LIKE', '%' . $keyword . '%');
                });
            });

        return $this->toJson($data);
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
            Column::make('name')
                ->title('Nombre')
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
        return [];
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
                'validate' => 'required|in:' . implode(',', array_keys(CrmLeadStageEnum::labels())),
            ],
            'source' => [
                'title' => 'Fuente',
                'type' => 'select',
                'choices' => CrmLeadSourceEnum::labels(),
                'validate' => 'required|in:' . implode(',', array_keys(CrmLeadSourceEnum::labels())),
            ],
            'created_at' => [
                'title' => 'Fecha de creación',
                'type' => 'datePicker',
            ],
        ];
    }
}
