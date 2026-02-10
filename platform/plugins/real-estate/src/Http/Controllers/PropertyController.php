<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\Base\Events\BeforeEditContentEvent;
use Botble\Base\Events\CreatedContentEvent;
use Botble\Base\Events\DeletedContentEvent;
use Botble\Base\Events\UpdatedContentEvent;
use Botble\Base\Facades\Assets;
use Botble\Base\Facades\PageTitle;
use Botble\Base\Forms\FormBuilder;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Media\Facades\RvMedia;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Forms\PropertyForm;
use Botble\RealEstate\Http\Requests\PropertyRequest;
use Botble\RealEstate\Models\Account;
use Botble\RealEstate\Models\CustomFieldValue;
use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Services\SaveFacilitiesService;
use Botble\RealEstate\Services\StorePropertyCategoryService;
use Botble\RealEstate\Tables\PropertyTable;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyController extends BaseController
{
    public function index(PropertyTable $dataTable)
    {
        PageTitle::setTitle(trans('plugins/real-estate::property.name'));

        return $dataTable->renderTable();
    }

    public function create(FormBuilder $formBuilder)
    {
        PageTitle::setTitle(trans('plugins/real-estate::property.create'));

        return $formBuilder->create(PropertyForm::class)->renderForm();
    }

    public function store(
        PropertyRequest $request,
        BaseHttpResponse $response,
        StorePropertyCategoryService $propertyCategoryService,
        SaveFacilitiesService $saveFacilitiesService
    ) {
        $request->merge([
            'expire_date' => Carbon::now()->addDays(RealEstateHelper::propertyExpiredDays()),
            'images' => array_filter($request->input('images', [])),
            'author_type' => Account::class,
        ]);

        $property = new Property();
        $property = $property->fill($request->input());
        $property->moderation_status = $request->input('moderation_status');
        $property->never_expired = $request->input('never_expired');
        $property->save();

        event(new CreatedContentEvent(PROPERTY_MODULE_SCREEN_NAME, $request, $property));

        if (RealEstateHelper::isEnabledCustomFields()) {
            $this->saveCustomFields($property, $request->input('custom_fields', []));
        }

        $property->features()->sync($request->input('features', []));

        $saveFacilitiesService->execute($property, $request->input('facilities', []));

        $propertyCategoryService->execute($request, $property);

        return $response
            ->setPreviousUrl(route('property.index'))
            ->setNextUrl(route('property.edit', $property->id))
            ->setMessage(trans('core/base::notices.create_success_message'));
    }

    public function edit(int|string $id, Request $request, FormBuilder $formBuilder)
    {
        $property = Property::query()->with(['features', 'author'])->findOrFail($id);

        Assets::addScriptsDirectly(['vendor/core/plugins/real-estate/js/duplicate-property.js']);

        PageTitle::setTitle(trans('plugins/real-estate::property.edit') . ' "' . $property->name . '"');

        event(new BeforeEditContentEvent($request, $property));

        return $formBuilder->create(PropertyForm::class, ['model' => $property])->renderForm();
    }

    public function update(
        int|string $id,
        PropertyRequest $request,
        BaseHttpResponse $response,
        StorePropertyCategoryService $propertyCategoryService,
        SaveFacilitiesService $saveFacilitiesService
    ) {
        $property = Property::query()->findOrFail($id);
        $property->fill($request->except(['expire_date']));

        $property->author_type = Account::class;
        $property->images = array_filter($request->input('images', []));
        $property->moderation_status = $request->input('moderation_status');
        $property->never_expired = $request->input('never_expired');

        $property->save();

        event(new UpdatedContentEvent(PROPERTY_MODULE_SCREEN_NAME, $request, $property));

        if (RealEstateHelper::isEnabledCustomFields()) {
            $this->saveCustomFields($property, $request->input('custom_fields', []));
        }

        $property->features()->sync($request->input('features', []));

        $saveFacilitiesService->execute($property, $request->input('facilities', []));

        $propertyCategoryService->execute($request, $property);

        return $response
            ->setPreviousUrl(route('property.index'))
            ->setNextUrl(route('property.edit', $property->id))
            ->setMessage(trans('core/base::notices.update_success_message'));
    }

    public function destroy(int|string $id, Request $request, BaseHttpResponse $response)
    {
        try {
            $property = Property::query()->findOrFail($id);
            $property->delete();

            event(new DeletedContentEvent(PROPERTY_MODULE_SCREEN_NAME, $request, $property));

            return $response->setMessage(trans('core/base::notices.delete_success_message'));
        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }

    public function getGridData(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 18);
        $search = $request->input('search', '');
        $status = $request->input('status', '');
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');

        $allowedSorts = ['name', 'created_at', 'price'];
        $allowedDirections = ['asc', 'desc'];

        if (! in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }

        if (! in_array($direction, $allowedDirections)) {
            $direction = 'desc';
        }

        $query = Property::query()
            ->select([
                'id', 'name', 'images', 'price', 'currency_id', 'type', 'period',
                'status', 'moderation_status', 'number_bedroom', 'number_bathroom',
                'number_floor', 'square', 'location', 'unique_id', 'created_at',
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('unique_id', 'LIKE', "%{$search}%")
                    ->orWhere('location', 'LIKE', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $query->orderBy($sort, $direction);

        $paginated = $query->paginate($perPage);

        $items = $paginated->getCollection()->map(function (Property $property) {
            $image = RvMedia::getDefaultImage();
            $images = $property->images;
            if (! empty($images) && is_array($images) && count($images) > 0) {
                $image = RvMedia::getImageUrl($images[0], 'medium', false, RvMedia::getDefaultImage());
            }

            $price = '';
            try {
                $property->loadMissing('currency');
                $price = $property->price_format;
            } catch (\Throwable $e) {
                $price = $property->price ? number_format($property->price) : __('Contact');
            }

            $typeLabel = '';
            try {
                $typeLabel = $property->type->label();
            } catch (\Throwable $e) {
                $typeLabel = (string) $property->type->value;
            }

            return [
                'id' => $property->id,
                'name' => $property->name,
                'image' => $image,
                'price' => $price,
                'type' => $typeLabel,
                'type_value' => $property->type->value ?? '',
                'status' => $property->status->value ?? '',
                'status_html' => $property->status->toHtml(),
                'moderation_status' => $property->moderation_status->value ?? '',
                'moderation_html' => $property->moderation_status->toHtml(),
                'unique_id' => $property->unique_id ?: '',
                'location' => $property->location ?: '',
                'bedrooms' => $property->number_bedroom,
                'bathrooms' => $property->number_bathroom,
                'floors' => $property->number_floor,
                'square' => $property->square,
                'square_text' => $property->square ? number_format($property->square) . ' ' . setting('real_estate_square_unit', 'm²') : '',
                'created_at' => $property->created_at ? $property->created_at->format('Y-m-d') : '',
                'edit_url' => route('property.edit', $property->id),
                'delete_url' => route('property.destroy', $property->id),
            ];
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ]);
    }

    protected function saveCustomFields(Property $property, array $customFields = []): void
    {
        $customFields = CustomFieldValue::formatCustomFields($customFields);

        $property->customFields()
            ->whereNotIn('id', collect($customFields)->pluck('id')->all())
            ->delete();

        $property->customFields()->saveMany($customFields);
    }
}
