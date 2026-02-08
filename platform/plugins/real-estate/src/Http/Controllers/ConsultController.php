<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\Base\Events\BeforeEditContentEvent;
use Botble\Base\Events\DeletedContentEvent;
use Botble\Base\Events\UpdatedContentEvent;
use Botble\Base\Facades\PageTitle;
use Botble\Base\Forms\FormBuilder;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\RealEstate\Forms\ConsultForm;
use Botble\RealEstate\Http\Requests\ConsultRequest;
use Botble\RealEstate\Models\Consult;
use Botble\RealEstate\Repositories\Interfaces\ConsultInterface;
use Botble\RealEstate\Tables\ConsultTable;
use Exception;
use Illuminate\Http\Request;

class ConsultController extends BaseController
{
    public function __construct(protected ConsultInterface $consultRepository)
    {
    }

    public function index(ConsultTable $table)
    {
        PageTitle::setTitle(trans('plugins/real-estate::consult.name'));

        return $table->renderTable();
    }

    public function edit(int|string $id, FormBuilder $formBuilder, Request $request)
    {
        $consult = Consult::query()->with(['project', 'property'])->findOrFail($id);

        event(new BeforeEditContentEvent($request, $consult));

        PageTitle::setTitle(trans('core/base::forms.edit_item', ['name' => $consult->name]));

        return $formBuilder->create(ConsultForm::class, ['model' => $consult])->renderForm();
    }

    public function update(int|string $id, ConsultRequest $request, BaseHttpResponse $response)
    {
        $consult = Consult::query()->findOrFail($id);

        $consult->fill($request->input());
        $consult->save();

        event(new UpdatedContentEvent(CONSULT_MODULE_SCREEN_NAME, $request, $consult));

        return $response
            ->setPreviousUrl(route('consult.index'))
            ->setMessage(trans('core/base::notices.update_success_message'));
    }

    public function destroy(int|string $id, Request $request, BaseHttpResponse $response)
    {
        try {
            $consult = Consult::query()->findOrFail($id);

            $consult->delete();

            event(new DeletedContentEvent(CONSULT_MODULE_SCREEN_NAME, $request, $consult));

            return $response->setMessage(trans('core/base::notices.delete_success_message'));
        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }
}
