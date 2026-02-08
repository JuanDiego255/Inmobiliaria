<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\Base\Events\BeforeEditContentEvent;
use Botble\Base\Events\CreatedContentEvent;
use Botble\Base\Events\DeletedContentEvent;
use Botble\Base\Events\UpdatedContentEvent;
use Botble\Base\Facades\PageTitle;
use Botble\Base\Forms\FormBuilder;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Forms\ProjectForm;
use Botble\RealEstate\Http\Requests\ProjectRequest;
use Botble\RealEstate\Models\CustomFieldValue;
use Botble\RealEstate\Models\Project;
use Botble\RealEstate\Services\SaveFacilitiesService;
use Botble\RealEstate\Services\StoreProjectCategoryService;
use Botble\RealEstate\Tables\ProjectTable;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ProjectController extends BaseController
{
    public function index(ProjectTable $dataTable)
    {
        PageTitle::setTitle(trans('plugins/real-estate::project.name'));

        return $dataTable->renderTable();
    }

    public function create(FormBuilder $formBuilder)
    {
        PageTitle::setTitle(trans('plugins/real-estate::project.create'));

        return $formBuilder->create(ProjectForm::class)->renderForm();
    }

    public function store(
        ProjectRequest $request,
        BaseHttpResponse $response,
        StoreProjectCategoryService $projectCategoryService,
        SaveFacilitiesService $saveFacilitiesService
    ) {
        $request->merge(['images' => array_filter($request->input('images', []))]);

        $project = Project::query()->create($request->input());

        if (RealEstateHelper::isEnabledCustomFields()) {
            $this->saveCustomFields($project, $request->input('custom_fields', []));
        }

        $project->features()->sync($request->input('features', []));

        $saveFacilitiesService->execute($project, $request->input('facilities', []));

        $projectCategoryService->execute($request, $project);

        event(new CreatedContentEvent(PROJECT_MODULE_SCREEN_NAME, $request, $project));

        return $response
            ->setPreviousUrl(route('project.index'))
            ->setNextUrl(route('project.edit', $project->id))
            ->setMessage(trans('core/base::notices.create_success_message'));
    }

    public function edit(int|string $id, Request $request, FormBuilder $formBuilder)
    {
        $project = Project::query()->findOrFail($id);

        PageTitle::setTitle(trans('plugins/real-estate::project.edit') . ' "' . $project->name . '"');

        event(new BeforeEditContentEvent($request, $project));

        return $formBuilder->create(ProjectForm::class, ['model' => $project])->renderForm();
    }

    public function update(
        ProjectRequest $request,
        int|string $id,
        BaseHttpResponse $response,
        StoreProjectCategoryService $projectCategoryService,
        SaveFacilitiesService $saveFacilitiesService
    ) {
        $project = Project::query()->findOrFail($id);

        $request->merge(['images' => array_filter($request->input('images', []))]);

        $project->fill($request->input());
        $project->save();

        if (RealEstateHelper::isEnabledCustomFields()) {
            $this->saveCustomFields($project, $request->input('custom_fields', []));
        }

        $project->features()->sync($request->input('features', []));

        $saveFacilitiesService->execute($project, $request->input('facilities', []));

        $projectCategoryService->execute($request, $project);

        event(new UpdatedContentEvent(PROJECT_MODULE_SCREEN_NAME, $request, $project));

        return $response
            ->setPreviousUrl(route('project.index'))
            ->setMessage(trans('core/base::notices.update_success_message'));
    }

    public function destroy(int|string $id, Request $request, BaseHttpResponse $response)
    {
        try {
            $project = Project::query()->findOrFail($id);
            $project->delete();

            event(new DeletedContentEvent(PROJECT_MODULE_SCREEN_NAME, $request, $project));

            return $response->setMessage(trans('core/base::notices.delete_success_message'));
        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }

    protected function formatCustomFields(array $customFields = []): array
    {
        $newCustomFields = [];

        foreach ($customFields as $item) {
            $customField = null;

            if ($item['id']) {
                $customField = CustomFieldValue::query()->find($item['id']);
                $customField->fill($item);
            } else {
                Arr::forget($item, 'id');
                $customField = new CustomFieldValue($item);
            }

            $newCustomFields[] = $customField;
        }

        return $newCustomFields;
    }

    protected function saveCustomFields(Project $project, array $customFields = []): void
    {
        $customFields = $this->formatCustomFields($customFields);

        $project->customFields()
            ->whereNotIn('id', collect($customFields)->pluck('id')->all())
            ->delete();

        $project->customFields()->saveMany($customFields);
    }
}
