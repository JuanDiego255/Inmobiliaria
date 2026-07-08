<?php

namespace Botble\RealEstate\Tables;

use Botble\Base\Facades\BaseHelper;
use Botble\RealEstate\Models\CrmReminder;
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

class CrmReminderTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(CrmReminder::class)
            ->addActions([
                DeleteAction::make()->route('crm.reminders.destroy'),
            ]);
    }

    public function ajax(): JsonResponse
    {
        $data = $this->table
            ->eloquent($this->query())
            ->editColumn('title', function (CrmReminder $item) {
                return e($item->title);
            })
            ->editColumn('remind_at', function (CrmReminder $item) {
                return $item->remind_at ? $item->remind_at->format('d/m/Y H:i') : '&mdash;';
            })
            ->editColumn('user_id', function (CrmReminder $item) {
                if (! $item->user) {
                    return '&mdash;';
                }

                return BaseHelper::clean($item->user->first_name . ' ' . $item->user->last_name);
            })
            ->editColumn('lead_id', function (CrmReminder $item) {
                if (! $item->lead_id || ! $item->lead) {
                    return '&mdash;';
                }

                return BaseHelper::clean($item->lead->name);
            })
            ->editColumn('is_dismissed', function (CrmReminder $item) {
                if ($item->is_dismissed) {
                    return '<span class="badge bg-success">Visto</span>';
                }

                return '<span class="badge bg-warning">Pendiente</span>';
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
            ->with(['user', 'lead'])
            ->select([
                'id',
                'title',
                'remind_at',
                'user_id',
                'lead_id',
                'is_dismissed',
                'created_at',
            ])
            ->orderBy('remind_at', 'desc');

        return $this->applyScopes($query);
    }

    public function columns(): array
    {
        return [
            IdColumn::make(),
            Column::make('title')
                ->title('Recordatorio')
                ->alignStart(),
            Column::make('remind_at')
                ->title('Fecha/Hora'),
            Column::make('user_id')
                ->title('Usuario')
                ->searchable(false)
                ->orderable(false),
            Column::make('lead_id')
                ->title('Lead')
                ->searchable(false)
                ->orderable(false),
            Column::make('is_dismissed')
                ->title('Estado'),
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
            DeleteBulkAction::make()->permission('crm-reminder.destroy'),
        ];
    }

    public function getBulkChanges(): array
    {
        return [
            'is_dismissed' => [
                'title' => 'Estado',
                'type' => 'select',
                'choices' => [
                    '0' => 'Pendiente',
                    '1' => 'Visto',
                ],
                'validate' => 'required|in:0,1',
            ],
        ];
    }
}
