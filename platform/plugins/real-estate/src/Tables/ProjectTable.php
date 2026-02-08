<?php

namespace Botble\RealEstate\Tables;

use Botble\Base\Facades\BaseHelper;
use Botble\RealEstate\Enums\ProjectStatusEnum;
use Botble\RealEstate\Models\Project;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\EditAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\ImageColumn;
use Botble\Table\Columns\NameColumn;
use Botble\Table\Columns\StatusColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ProjectTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(Project::class)
            ->addActions([
                EditAction::make()->route('project.edit'),
                DeleteAction::make()->route('project.destroy'),
            ]);
    }

    public function ajax(): JsonResponse
    {
        $data = $this->table
            ->eloquent($this->query())
            ->editColumn('views', function (Project $item) {
                return number_format($item->views);
            })
            ->editColumn('unique_id', function (Project $item) {
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
            NameColumn::make()->route('project.edit'),
            Column::make('views')
                ->title(trans('plugins/real-estate::project.views')),
            Column::make('unique_id')
                ->title(trans('plugins/real-estate::project.unique_id')),
            CreatedAtColumn::make(),
            StatusColumn::make(),
        ];
    }

    public function buttons(): array
    {
        $buttons = $this->addCreateButton(route('project.create'), 'project.create');

        if ($this->hasPermission('import-projects.index')) {
            $buttons['import'] = [
                'link' => route('import-projects.index'),
                'text' => '<i class="fas fa-cloud-upload-alt"></i> ' . trans('plugins/real-estate::project.import_projects'),
                'class' => 'btn-warning',
            ];
        }

        if ($this->hasPermission('export-projects.index')) {
            $buttons['export'] = [
                'link' => route('export-projects.index'),
                'text' => '<i class="fas fa-cloud-download-alt"></i> ' . trans('plugins/real-estate::project.export_projects'),
                'class' => 'btn-warning',
            ];
        }

        return $buttons;
    }

    public function bulkActions(): array
    {
        return [
            DeleteBulkAction::make()->permission('project.destroy'),
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
                'choices' => ProjectStatusEnum::labels(),
                'validate' => 'required|' . Rule::in(ProjectStatusEnum::values()),
            ],
        ];
    }
}
