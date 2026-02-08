<?php

namespace Botble\RealEstate\Tables;

use Botble\RealEstate\Enums\BoardStatusEnum;
use Botble\RealEstate\Models\Board;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\EditAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\NameColumn;
use Botble\Table\Columns\StatusColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;

class BoardTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(Board::class)
            ->addActions([
                EditAction::make()->route('board.edit'),
                DeleteAction::make()->route('board.destroy'),
            ]);
    }

    public function ajax(): JsonResponse
    {
        $data = $this->table
            ->eloquent($this->query())
            ->editColumn('client_name', function (Board $item) {
                return $item->client->name;
            })
            ->editColumn('properties_count', function (Board $item) {
                return $item->properties_count;
            });

        return $this->toJson($data);
    }

    public function query(): Relation|Builder|QueryBuilder
    {
        $query = $this
            ->getModel()
            ->query()
            ->select([
                're_boards.id',
                're_boards.name',
                're_boards.client_id',
                're_boards.created_at',
                're_boards.status',
            ])
            ->with('client')
            ->withCount('properties');

        return $this->applyScopes($query);
    }

    public function columns(): array
    {
        return [
            IdColumn::make(),
            NameColumn::make()->route('board.edit'),
            Column::make('client_name')
                ->title(trans('plugins/real-estate::board.form.client'))
                ->searchable(false)
                ->orderable(false),
            Column::make('properties_count')
                ->title(trans('plugins/real-estate::board.properties_count'))
                ->searchable(false)
                ->orderable(false)
                ->width(120),
            CreatedAtColumn::make(),
            StatusColumn::make(),
        ];
    }

    public function buttons(): array
    {
        return $this->addCreateButton(route('board.create'), 'board.create');
    }

    public function bulkActions(): array
    {
        return [
            DeleteBulkAction::make()->permission('board.destroy'),
        ];
    }

    public function getBulkChanges(): array
    {
        return [
            'name' => [
                'title' => trans('core/base::tables.name'),
                'type' => 'text',
                'validate' => 'required|max:255',
            ],
            'status' => [
                'title' => trans('core/base::tables.status'),
                'type' => 'select',
                'choices' => BoardStatusEnum::labels(),
                'validate' => 'required|in:' . implode(',', BoardStatusEnum::values()),
            ],
            'created_at' => [
                'title' => trans('core/base::tables.created_at'),
                'type' => 'datePicker',
            ],
        ];
    }
}
