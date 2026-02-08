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
use Botble\RealEstate\Forms\ClientForm;
use Botble\RealEstate\Http\Requests\ClientRequest;
use Botble\RealEstate\Models\Client;
use Botble\RealEstate\Tables\ClientTable;
use Exception;
use Illuminate\Http\Request;

class ClientController extends BaseController
{
    public function index(ClientTable $table)
    {
        PageTitle::setTitle(trans('plugins/real-estate::client.name'));

        return $table->renderTable();
    }

    public function create(FormBuilder $formBuilder)
    {
        PageTitle::setTitle(trans('plugins/real-estate::client.create'));

        return $formBuilder->create(ClientForm::class)->renderForm();
    }

    public function store(ClientRequest $request, BaseHttpResponse $response)
    {
        $client = Client::query()->create($request->input());

        event(new CreatedContentEvent(CLIENT_MODULE_SCREEN_NAME, $request, $client));

        return $response
            ->setPreviousUrl(route('client.index'))
            ->setNextUrl(route('client.edit', $client->id))
            ->setMessage(trans('core/base::notices.create_success_message'));
    }

    public function edit(int|string $id, FormBuilder $formBuilder, Request $request)
    {
        $client = Client::query()->findOrFail($id);

        event(new BeforeEditContentEvent($request, $client));

        PageTitle::setTitle(trans('plugins/real-estate::client.edit') . ' "' . $client->name . '"');

        return $formBuilder->create(ClientForm::class, ['model' => $client])->renderForm();
    }

    public function update(int|string $id, ClientRequest $request, BaseHttpResponse $response)
    {
        $client = Client::query()->findOrFail($id);
        $client->fill($request->input());
        $client->save();

        event(new UpdatedContentEvent(CLIENT_MODULE_SCREEN_NAME, $request, $client));

        return $response
            ->setPreviousUrl(route('client.index'))
            ->setNextUrl(route('client.edit', $client->id))
            ->setMessage(trans('core/base::notices.update_success_message'));
    }

    public function destroy(int|string $id, Request $request, BaseHttpResponse $response)
    {
        try {
            $client = Client::query()->findOrFail($id);
            $client->delete();

            event(new DeletedContentEvent(CLIENT_MODULE_SCREEN_NAME, $request, $client));

            return $response->setMessage(trans('core/base::notices.delete_success_message'));
        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }
}
