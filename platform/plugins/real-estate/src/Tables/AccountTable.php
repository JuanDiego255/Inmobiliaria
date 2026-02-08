<?php

namespace Botble\RealEstate\Tables;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\Html;
use Botble\Media\Facades\RvMedia;
use Botble\RealEstate\Models\Account;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\EditAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\EmailColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\NameColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;

class AccountTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(Account::class)
            ->addActions([
                EditAction::make()->route('account.edit'),
                DeleteAction::make()->route('account.destroy'),
            ]);
    }

    public function ajax(): JsonResponse
    {
        $data = $this->table
            ->eloquent($this->query())
            ->editColumn('avatar_id', function (Account $item) {
                return Html::image(
                    RvMedia::getImageUrl($item->avatar->url, 'thumb', false, RvMedia::getDefaultImage()),
                    BaseHelper::clean($item->name),
                    ['width' => 50]
                );
            })
            ->editColumn('phone', function (Account $item) {
                return BaseHelper::clean($item->phone ?: '&mdash;');
            })
            ->editColumn('updated_at', function (Account $item) {
                return $item->properties_count;
            })
            ->filter(function (Builder $query) {
                $keyword = $this->request->input('search.value');

                if (! $keyword) {
                    return $query;
                }

                return $query->where(function (Builder $query) use ($keyword) {
                    $query
                        ->where('id', $keyword)
                        ->orWhere('first_name', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('last_name', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('email', 'LIKE', '%' . $keyword . '%')
                        ->orWhereDate('created_at', $keyword);
                });
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
                'first_name',
                'last_name',
                'email',
                'phone',
                'created_at',
                'credits',
                'avatar_id',
            ])
            ->with(['avatar'])
            ->withCount(['properties']);

        return $this->applyScopes($query);
    }

    public function columns(): array
    {
        return [
            IdColumn::make(),
            Column::make('avatar_id')
                ->title(trans('core/base::tables.image'))
                ->width(70),
            NameColumn::make()
                ->route('account.edit')
                ->orderable(false)
                ->searchable(false),
            EmailColumn::make(),
            Column::make('phone')
                ->title(trans('plugins/real-estate::account.phone'))
                ->alignLeft(),
            Column::make('credits')
                ->title(trans('plugins/real-estate::account.credits'))
                ->alignLeft(),
            Column::make('updated_at')
                ->title(trans('plugins/real-estate::account.number_of_properties'))
                ->width(100)
                ->orderable(false)
                ->searchable(false),
            CreatedAtColumn::make(),
        ];
    }

    public function buttons(): array
    {
        return $this->addCreateButton(route('account.create'), 'account.create');
    }

    public function bulkActions(): array
    {
        return [
            DeleteBulkAction::make()->permission('account.destroy'),
        ];
    }

    public function getBulkChanges(): array
    {
        return [
            'first_name' => [
                'title' => trans('plugins/real-estate::account.first_name'),
                'type' => 'text',
                'validate' => 'required|max:120',
            ],
            'last_name' => [
                'title' => trans('plugins/real-estate::account.last_name'),
                'type' => 'text',
                'validate' => 'required|max:120',
            ],
            'email' => [
                'title' => trans('core/base::tables.email'),
                'type' => 'text',
                'validate' => 'required|max:120|email',
            ],
            'created_at' => [
                'title' => trans('core/base::tables.created_at'),
                'type' => 'datePicker',
            ],
        ];
    }
}
