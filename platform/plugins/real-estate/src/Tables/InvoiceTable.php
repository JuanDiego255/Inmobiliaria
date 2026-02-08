<?php

namespace Botble\RealEstate\Tables;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Facades\Html;
use Botble\RealEstate\Models\Invoice;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\EditAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\LinkableColumn;
use Botble\Table\Columns\StatusColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;

class InvoiceTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(Invoice::class)
            ->addActions([
                EditAction::make()->route('invoices.show')->permission('invoices.edit')->icon('fa fa-eye')->label(__('View')),
                DeleteAction::make()->route('invoices.destroy'),
            ]);
    }

    public function ajax(): JsonResponse
    {
        $data = $this->table
            ->eloquent($this->query())
            ->editColumn('account_id', function (Invoice $item) {
                return Html::link(route('account.edit', $item->account), $item->account->name);
            })
            ->editColumn('amount', function (Invoice $item) {
                return format_price($item->amount);
            });

        return $this->toJson($data);
    }

    public function query(): Relation|Builder|QueryBuilder
    {
        $query = $this
            ->getModel()
            ->query()
            ->select([
                'id',
                'account_id',
                'code',
                'amount',
                'created_at',
                'status',
            ])
            ->with('account');

        return $this->applyScopes($query);
    }

    public function columns(): array
    {
        return [
            IdColumn::make(),
            Column::make('account_id')
                ->title(trans('plugins/real-estate::invoice.account'))
                ->alignLeft(),
            LinkableColumn::make('code')
                ->title(trans('plugins/real-estate::invoice.code'))
                ->route('invoices.show')
                ->permission('invoices.edit')
                ->alignLeft(),
            Column::make('amount')
                ->title(trans('plugins/real-estate::invoice.amount'))
                ->alignLeft(),
            CreatedAtColumn::make(),
            StatusColumn::make(),
        ];
    }

    public function bulkActions(): array
    {
        return [
            DeleteBulkAction::make()->permission('invoices.destroy'),
        ];
    }

    public function getBulkChanges(): array
    {
        return [
            'name' => [
                'title' => trans('core/base::tables.name'),
                'type' => 'text',
                'validate' => 'required|max:120',
            ],
            'status' => [
                'title' => trans('core/base::tables.status'),
                'type' => 'select',
                'choices' => BaseStatusEnum::labels(),
                'validate' => 'required|in:' . implode(',', BaseStatusEnum::values()),
            ],
            'created_at' => [
                'title' => trans('core/base::tables.created_at'),
                'type' => 'datePicker',
            ],
        ];
    }
}
