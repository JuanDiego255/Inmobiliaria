<?php

namespace Botble\RealEstate\Tables;

use Botble\Base\Facades\BaseHelper;
use Botble\RealEstate\Enums\ModerationStatusEnum;
use Botble\RealEstate\Enums\PropertyStatusEnum;
use Botble\RealEstate\Models\Property;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\EditAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\EnumColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\ImageColumn;
use Botble\Table\Columns\NameColumn;
use Botble\Table\Columns\StatusColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\Relation as EloquentRelation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class PropertyTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(Property::class)
            ->addActions([
                EditAction::make()->route('property.edit'),
                DeleteAction::make()->route('property.destroy'),
            ]);
    }

    public function ajax(): JsonResponse
    {
        $data = $this->table
            ->eloquent($this->query())
            ->editColumn('views', function (Property $item) {
                return number_format($item->views);
            })
            ->editColumn('unique_id', function (Property $item) {
                return BaseHelper::clean($item->unique_id ?: '&mdash;');
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
                'name',
                'images',
                'views',
                'status',
                'moderation_status',
                'created_at',
                'unique_id',
            ]);

        return $this->applyScopes($query);
    }

    public function columns(): array
    {
        return [
            IdColumn::make(),
            ImageColumn::make()
                ->searchable(false)
                ->orderable(false),
            NameColumn::make()->route('property.edit'),
            Column::make('views')
                ->title(trans('plugins/real-estate::property.views')),
            Column::make('unique_id')
                ->title(trans('plugins/real-estate::property.unique_id')),
            CreatedAtColumn::make(),
            StatusColumn::make(),
            EnumColumn::make('moderation_status')
                ->title(trans('plugins/real-estate::property.moderation_status'))
                ->width(150),
        ];
    }

    public function buttons(): array
    {
        $buttons = $this->addCreateButton(route('property.create'), 'property.create');

        if ($this->hasPermission('import-properties.index')) {
            $buttons['import'] = [
                'link' => route('import-properties.index'),
                'text' => '<i class="fas fa-cloud-upload-alt"></i> ' . trans('plugins/real-estate::property.import_properties'),
                'class' => 'btn-warning',
            ];
        }

        if ($this->hasPermission('export-properties.index')) {
            $buttons['export'] = [
                'link' => route('export-properties.index'),
                'text' => '<i class="fas fa-cloud-download-alt"></i> ' . trans('plugins/real-estate::property.export_properties'),
                'class' => 'btn-warning',
            ];
        }

        return $buttons;
    }

    public function bulkActions(): array
    {
        return [
            DeleteBulkAction::make()->permission('property.destroy'),
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
                'choices' => PropertyStatusEnum::labels(),
                'validate' => 'required|' . Rule::in(PropertyStatusEnum::values()),
            ],
            'moderation_status' => [
                'title' => trans('plugins/real-estate::property.moderation_status'),
                'type' => 'select',
                'choices' => ModerationStatusEnum::labels(),
                'validate' => 'required|in:' . implode(',', ModerationStatusEnum::values()),
            ],
            'created_at' => [
                'title' => trans('core/base::tables.created_at'),
                'type' => 'datePicker',
            ],
        ];
    }

    public function applyFilterCondition(EloquentBuilder|QueryBuilder|EloquentRelation $query, string $key, string $operator, ?string $value): EloquentRelation|EloquentBuilder|QueryBuilder
    {
        if ($key == 'status') {
            switch ($value) {
                case 'expired':
                    // @phpstan-ignore-next-line
                    return $query->expired();
                case 'active':
                    // @phpstan-ignore-next-line
                    return $query->active();
            }
        }

        return parent::applyFilterCondition($query, $key, $operator, $value);
    }
}
