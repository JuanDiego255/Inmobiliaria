<?php

namespace Botble\RealEstate\Tables;

use Botble\Base\Facades\BaseHelper;
use Botble\RealEstate\Enums\CrmTaskPriorityEnum;
use Botble\RealEstate\Enums\CrmTaskStatusEnum;
use Botble\RealEstate\Models\CrmTask;
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

class CrmTaskTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(CrmTask::class)
            ->addActions([
                DeleteAction::make()->route('crm.tasks.destroy'),
            ]);
    }

    public function ajax(): JsonResponse
    {
        $data = $this->table
            ->eloquent($this->query())
            ->editColumn('title', function (CrmTask $item) {
                return '<strong>' . e($item->title) . '</strong>';
            })
            ->editColumn('status', function (CrmTask $item) {
                if (! $item->status) {
                    return '&mdash;';
                }

                return $item->status->toHtml();
            })
            ->editColumn('priority', function (CrmTask $item) {
                if (! $item->priority) {
                    return '&mdash;';
                }

                return $item->priority->toHtml();
            })
            ->editColumn('assigned_to', function (CrmTask $item) {
                if (! $item->assigned_to || ! $item->assignedUser) {
                    return 'Sin asignar';
                }

                return BaseHelper::clean($item->assignedUser->first_name . ' ' . $item->assignedUser->last_name);
            })
            ->editColumn('lead_id', function (CrmTask $item) {
                if (! $item->lead_id || ! $item->lead) {
                    return '&mdash;';
                }

                return e($item->lead->name);
            })
            ->editColumn('due_date', function (CrmTask $item) {
                if (! $item->due_date) {
                    return '&mdash;';
                }

                return $item->due_date->format('d/m/Y');
            })
            ->filter(function ($query) {
                $keyword = $this->request->input('search.value');

                if (! $keyword) {
                    return $query;
                }

                return $query->where(function ($query) use ($keyword) {
                    $query
                        ->where('id', $keyword)
                        ->orWhere('title', 'LIKE', '%' . $keyword . '%');
                });
            });

        return $this->toJson($data);
    }

    public function query(): Relation|Builder|QueryBuilder
    {
        $query = $this
            ->getModel()
            ->query()
            ->with(['assignedUser', 'lead'])
            ->select([
                'id',
                'title',
                'status',
                'priority',
                'assigned_to',
                'lead_id',
                'due_date',
                'created_at',
            ]);

        return $this->applyScopes($query);
    }

    public function columns(): array
    {
        return [
            IdColumn::make(),
            Column::make('title')
                ->title('Tarea')
                ->alignStart(),
            Column::make('status')
                ->title('Estado'),
            Column::make('priority')
                ->title('Prioridad'),
            Column::make('assigned_to')
                ->title('Asignado')
                ->searchable(false)
                ->orderable(false),
            Column::make('lead_id')
                ->title('Lead')
                ->searchable(false)
                ->orderable(false),
            Column::make('due_date')
                ->title('Vencimiento'),
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
            DeleteBulkAction::make()->permission('crm-task.destroy'),
        ];
    }

    public function getBulkChanges(): array
    {
        return [
            'status' => [
                'title' => 'Estado',
                'type' => 'select',
                'choices' => CrmTaskStatusEnum::labels(),
                'validate' => 'required|in:' . implode(',', array_keys(CrmTaskStatusEnum::labels())),
            ],
            'priority' => [
                'title' => 'Prioridad',
                'type' => 'select',
                'choices' => CrmTaskPriorityEnum::labels(),
                'validate' => 'required|in:' . implode(',', array_keys(CrmTaskPriorityEnum::labels())),
            ],
        ];
    }
}
