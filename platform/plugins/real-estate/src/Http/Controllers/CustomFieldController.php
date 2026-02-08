<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\ACL\Models\User;
use Botble\Base\Events\BeforeEditContentEvent;
use Botble\Base\Events\CreatedContentEvent;
use Botble\Base\Events\DeletedContentEvent;
use Botble\Base\Events\UpdatedContentEvent;
use Botble\Base\Facades\PageTitle;
use Botble\Base\Forms\FormBuilder;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Forms\CustomFieldForm;
use Botble\RealEstate\Http\Requests\CustomFieldRequest;
use Botble\RealEstate\Http\Resources\CustomFieldResource;
use Botble\RealEstate\Models\CustomField;
use Botble\RealEstate\Repositories\Interfaces\CustomFieldInterface;
use Botble\RealEstate\Tables\CustomFieldTable;
use Closure;
use Exception;
use Illuminate\Http\Request;

class CustomFieldController extends BaseController
{
    public function __construct(protected CustomFieldInterface $customFieldRepository)
    {
        $this->middleware(function (Request $request, Closure $next) {
            if (! RealEstateHelper::isEnabledCustomFields()) {
                abort(404);
            }

            return $next($request);
        });
    }

    public function index(CustomFieldTable $table)
    {
        PageTitle::setTitle(trans('plugins/real-estate::custom-fields.name'));

        return $table->renderTable();
    }

    public function create(FormBuilder $formBuilder)
    {
        PageTitle::setTitle(trans('plugins/real-estate::custom-fields.create'));

        return $formBuilder->create(CustomFieldForm::class)->renderForm();
    }

    public function store(CustomFieldRequest $request, BaseHttpResponse $response)
    {
        $customField = new CustomField();
        $customField->fill($request->validated());
        $customField->authorable_type = User::class;
        $customField->authorable_id = auth()->id();
        $customField->save();

        $customField->saveRelations($request->validated());

        event(new CreatedContentEvent(REAL_ESTATE_CUSTOM_FIELD_MODULE_SCREEN_NAME, $request, $customField));

        return $response
            ->setPreviousUrl(route('real-estate.custom-fields.index'))
            ->setNextUrl(route('real-estate.custom-fields.edit', $customField->id))
            ->setMessage(trans('core/base::notices.create_success_message'));
    }

    public function edit(int|string $id, FormBuilder $formBuilder, Request $request)
    {
        $customField = CustomField::query()->with(['options'])->findOrFail($id);

        event(new BeforeEditContentEvent($request, $customField));

        PageTitle::setTitle(trans('plugins/real-estate::custom-fields.edit', ['name' => $customField->name]));

        return $formBuilder->create(CustomFieldForm::class, ['model' => $customField])->renderForm();
    }

    public function update(int|string $id, CustomFieldRequest $request, BaseHttpResponse $response)
    {
        $customField = CustomField::query()->findOrFail($id);

        $customField->fill($request->validated());
        $customField->save();

        $customField->saveRelations($request->validated());

        event(new UpdatedContentEvent(REAL_ESTATE_CUSTOM_FIELD_MODULE_SCREEN_NAME, $request, $customField));

        return $response
            ->setPreviousUrl(route('real-estate.custom-fields.index'))
            ->setMessage(trans('core/base::notices.update_success_message'));
    }

    public function destroy(int|string $id, Request $request, BaseHttpResponse $response)
    {
        try {
            $customField = CustomField::query()->findOrFail($id);

            $customField->delete();

            event(new DeletedContentEvent(REAL_ESTATE_CUSTOM_FIELD_MODULE_SCREEN_NAME, $request, $customField));

            return $response->setMessage(trans('core/base::notices.delete_success_message'));
        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }

    public function getInfo(Request $request)
    {
        $customField = CustomField::query()->with(['options'])->findOrFail($request->input('id'), );

        return new CustomFieldResource($customField);
    }
}
