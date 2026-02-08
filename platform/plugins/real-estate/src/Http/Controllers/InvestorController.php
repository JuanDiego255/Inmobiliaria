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
use Botble\RealEstate\Forms\InvestorForm;
use Botble\RealEstate\Http\Requests\InvestorRequest;
use Botble\RealEstate\Models\Investor;
use Botble\RealEstate\Repositories\Interfaces\InvestorInterface;
use Botble\RealEstate\Tables\InvestorTable;
use Exception;
use Illuminate\Http\Request;

class InvestorController extends BaseController
{
    public function __construct(protected InvestorInterface $investorRepository)
    {
    }

    public function index(InvestorTable $table)
    {
        PageTitle::setTitle(trans('plugins/real-estate::investor.name'));

        return $table->renderTable();
    }

    public function create(FormBuilder $formBuilder)
    {
        PageTitle::setTitle(trans('plugins/real-estate::investor.create'));

        return $formBuilder->create(InvestorForm::class)->renderForm();
    }

    public function store(InvestorRequest $request, BaseHttpResponse $response)
    {
        $investor = Investor::query()->create($request->input());

        event(new CreatedContentEvent(INVESTOR_MODULE_SCREEN_NAME, $request, $investor));

        return $response
            ->setPreviousUrl(route('investor.index'))
            ->setNextUrl(route('investor.edit', $investor->id))
            ->setMessage(trans('core/base::notices.create_success_message'));
    }

    public function edit(int|string $id, FormBuilder $formBuilder, Request $request)
    {
        $investor = Investor::query()->findOrFail($id);

        event(new BeforeEditContentEvent($request, $investor));

        PageTitle::setTitle(trans('plugins/real-estate::investor.edit') . ' "' . $investor->name . '"');

        return $formBuilder->create(InvestorForm::class, ['model' => $investor])->renderForm();
    }

    public function update(int|string $id, InvestorRequest $request, BaseHttpResponse $response)
    {
        $investor = Investor::query()->findOrFail($id);

        $investor->fill($request->input());
        $investor->save();

        event(new UpdatedContentEvent(INVESTOR_MODULE_SCREEN_NAME, $request, $investor));

        return $response
            ->setPreviousUrl(route('investor.index'))
            ->setMessage(trans('core/base::notices.update_success_message'));
    }

    public function destroy(int|string $id, Request $request, BaseHttpResponse $response)
    {
        try {
            $investor = Investor::query()->findOrFail($id);

            $investor->delete();

            event(new DeletedContentEvent(INVESTOR_MODULE_SCREEN_NAME, $request, $investor));

            return $response->setMessage(trans('core/base::notices.delete_success_message'));
        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }
}
