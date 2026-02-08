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
use Botble\RealEstate\Forms\FacilityForm;
use Botble\RealEstate\Http\Requests\FacilityRequest;
use Botble\RealEstate\Models\Facility;
use Botble\RealEstate\Repositories\Interfaces\FacilityInterface;
use Botble\RealEstate\Tables\FacilityTable;
use Exception;
use Illuminate\Http\Request;

class FacilityController extends BaseController
{
    public function __construct(protected FacilityInterface $facilityRepository)
    {
    }

    public function index(FacilityTable $table)
    {
        PageTitle::setTitle(trans('plugins/real-estate::facility.name'));

        return $table->renderTable();
    }

    public function create(FormBuilder $formBuilder)
    {
        PageTitle::setTitle(trans('plugins/real-estate::facility.create'));

        return $formBuilder->create(FacilityForm::class)->renderForm();
    }

    public function store(FacilityRequest $request, BaseHttpResponse $response)
    {
        $facility = Facility::query()->create($request->input());

        event(new CreatedContentEvent(FACILITY_MODULE_SCREEN_NAME, $request, $facility));

        return $response
            ->setPreviousUrl(route('facility.index'))
            ->setNextUrl(route('facility.edit', $facility->id))
            ->setMessage(trans('core/base::notices.create_success_message'));
    }

    public function edit(int|string $id, FormBuilder $formBuilder, Request $request)
    {
        $facility = Facility::query()->findOrFail($id);

        event(new BeforeEditContentEvent($request, $facility));

        PageTitle::setTitle(trans('plugins/real-estate::facility.edit') . ' "' . $facility->name . '"');

        return $formBuilder->create(FacilityForm::class, ['model' => $facility])->renderForm();
    }

    public function update(int|string $id, FacilityRequest $request, BaseHttpResponse $response)
    {
        $facility = Facility::query()->findOrFail($id);

        $facility->fill($request->input());
        $facility->save();

        event(new UpdatedContentEvent(FACILITY_MODULE_SCREEN_NAME, $request, $facility));

        return $response
            ->setPreviousUrl(route('facility.index'))
            ->setMessage(trans('core/base::notices.update_success_message'));
    }

    public function destroy(int|string $id, Request $request, BaseHttpResponse $response)
    {
        try {
            $facility = Facility::query()->findOrFail($id);

            $facility->delete();

            event(new DeletedContentEvent(FACILITY_MODULE_SCREEN_NAME, $request, $facility));

            return $response->setMessage(trans('core/base::notices.delete_success_message'));
        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }
}
