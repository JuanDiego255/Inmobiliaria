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
use Botble\RealEstate\Forms\PackageForm;
use Botble\RealEstate\Http\Requests\PackageRequest;
use Botble\RealEstate\Models\Package;
use Botble\RealEstate\Repositories\Interfaces\PackageInterface;
use Botble\RealEstate\Tables\PackageTable;
use Exception;
use Illuminate\Http\Request;

class PackageController extends BaseController
{
    public function __construct(protected PackageInterface $packageRepository)
    {
    }

    public function index(PackageTable $table)
    {
        PageTitle::setTitle(trans('plugins/real-estate::package.name'));

        return $table->renderTable();
    }

    public function create(FormBuilder $formBuilder)
    {
        PageTitle::setTitle(trans('plugins/real-estate::package.create'));

        return $formBuilder->create(PackageForm::class)->renderForm();
    }

    public function store(PackageRequest $request, BaseHttpResponse $response)
    {
        $package = Package::query()->create($request->input());

        event(new CreatedContentEvent(PACKAGE_MODULE_SCREEN_NAME, $request, $package));

        return $response
            ->setPreviousUrl(route('package.index'))
            ->setNextUrl(route('package.edit', $package->id))
            ->setMessage(trans('core/base::notices.create_success_message'));
    }

    public function edit(int|string $id, FormBuilder $formBuilder, Request $request)
    {
        $package = Package::query()->findOrFail($id);

        event(new BeforeEditContentEvent($request, $package));

        PageTitle::setTitle(trans('plugins/real-estate::package.edit') . ' "' . $package->name . '"');

        return $formBuilder->create(PackageForm::class, ['model' => $package])->renderForm();
    }

    public function update(int|string $id, PackageRequest $request, BaseHttpResponse $response)
    {
        $package = Package::query()->findOrFail($id);

        $package->fill($request->input());
        $package->save();

        event(new UpdatedContentEvent(PACKAGE_MODULE_SCREEN_NAME, $request, $package));

        return $response
            ->setPreviousUrl(route('package.index'))
            ->setMessage(trans('core/base::notices.update_success_message'));
    }

    public function destroy(int|string $id, Request $request, BaseHttpResponse $response)
    {
        try {
            $package = Package::query()->findOrFail($id);

            $package->delete();

            event(new DeletedContentEvent(PACKAGE_MODULE_SCREEN_NAME, $request, $package));

            return $response->setMessage(trans('core/base::notices.delete_success_message'));
        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }
}
