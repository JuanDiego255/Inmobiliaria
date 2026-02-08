<?php

namespace Botble\RealEstate\Tables;

use Botble\RealEstate\Enums\ClientStatusEnum;
use Botble\RealEstate\Models\Client;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\EditAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\EmailColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\NameColumn;
use Botble\Table\Columns\StatusColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;

class ClientTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(Client::class)
            ->addActions([
                EditAction::make()->route('client.edit'),
                DeleteAction::make()->route('client.destroy'),
            ]);
    }

    public function query(): Relation|Builder|QueryBuilder
    {
        $query = $this
            ->getModel()
            ->query()
            ->select([
                'id',
                'name',
                'email',
                'phone',
                'occupation',
                'age',
                'created_at',
                'status',
            ]);

        return $this->applyScopes($query);
    }

    public function columns(): array
    {
        return [
            IdColumn::make(),
            NameColumn::make()->route('client.edit'),
            EmailColumn::make(),
            Column::make('phone')
                ->title(trans('plugins/real-estate::client.form.phone')),
            Column::make('occupation')
                ->title(trans('plugins/real-estate::client.form.occupation')),
            Column::make('age')
                ->title(trans('plugins/real-estate::client.form.age'))
                ->width(80),
            CreatedAtColumn::make(),
            StatusColumn::make(),
        ];
    }

    public function buttons(): array
    {
        return $this->addCreateButton(route('client.create'), 'client.create');
    }

    public function bulkActions(): array
    {
        return [
            DeleteBulkAction::make()->permission('client.destroy'),
        ];
    }

    public function getBulkChanges(): array
    {
        return [
            'name' => [
                'title' => trans('core/base::tables.name'),
                'type' => 'text',
                'validate' => 'required|max:200',
            ],
            'email' => [
                'title' => trans('core/base::tables.email'),
                'type' => 'text',
                'validate' => 'nullable|email|max:120',
            ],
            'status' => [
                'title' => trans('core/base::tables.status'),
                'type' => 'select',
                'choices' => ClientStatusEnum::labels(),
                'validate' => 'required|in:' . implode(',', ClientStatusEnum::values()),
            ],
            'created_at' => [
                'title' => trans('core/base::tables.created_at'),
                'type' => 'datePicker',
            ],
        ];
    }
}
