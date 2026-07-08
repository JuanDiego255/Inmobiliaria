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
use Botble\RealEstate\Forms\BoardForm;
use Botble\RealEstate\Http\Requests\BoardRequest;
use Botble\RealEstate\Models\Board;
use Botble\RealEstate\Models\Client;
use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Tables\BoardTable;
use Botble\Media\Facades\RvMedia;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardController extends BaseController
{
    public function index(BoardTable $table)
    {
        PageTitle::setTitle(trans('plugins/real-estate::board.name'));

        return $table->renderTable();
    }

    public function create(FormBuilder $formBuilder)
    {
        PageTitle::setTitle(trans('plugins/real-estate::board.create'));

        return $formBuilder->create(BoardForm::class)->renderForm();
    }

    public function store(BoardRequest $request, BaseHttpResponse $response)
    {
        $board = Board::query()->create($request->only(['name', 'description', 'client_id', 'lead_id', 'status']));

        event(new CreatedContentEvent(BOARD_MODULE_SCREEN_NAME, $request, $board));

        return $response
            ->setPreviousUrl(route('board.index'))
            ->setNextUrl(route('board.edit', $board->id))
            ->setMessage(trans('core/base::notices.create_success_message'));
    }

    public function edit(int|string $id, FormBuilder $formBuilder, Request $request)
    {
        $board = Board::query()->with(['client', 'lead', 'properties'])->findOrFail($id);

        event(new BeforeEditContentEvent($request, $board));

        PageTitle::setTitle(trans('plugins/real-estate::board.edit') . ' "' . $board->name . '"');

        return $formBuilder->create(BoardForm::class, ['model' => $board])->renderForm();
    }

    public function update(int|string $id, BoardRequest $request, BaseHttpResponse $response)
    {
        $board = Board::query()->findOrFail($id);
        $board->fill($request->input());
        $board->save();

        event(new UpdatedContentEvent(BOARD_MODULE_SCREEN_NAME, $request, $board));

        return $response
            ->setPreviousUrl(route('board.index'))
            ->setNextUrl(route('board.edit', $board->id))
            ->setMessage(trans('core/base::notices.update_success_message'));
    }

    public function destroy(int|string $id, Request $request, BaseHttpResponse $response)
    {
        try {
            $board = Board::query()->findOrFail($id);
            $board->delete();

            event(new DeletedContentEvent(BOARD_MODULE_SCREEN_NAME, $request, $board));

            return $response->setMessage(trans('core/base::notices.delete_success_message'));
        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }

    public function addProperty(int|string $id, Request $request, BaseHttpResponse $response)
    {
        $request->validate([
            'property_id' => ['required', 'exists:re_properties,id'],
        ]);

        $board = Board::query()->findOrFail($id);

        if ($board->properties()->where('property_id', $request->input('property_id'))->exists()) {
            return $response
                ->setError()
                ->setMessage(trans('plugins/real-estate::board.property_already_in_board'));
        }

        $maxOrder = $board->properties()->max('re_board_properties.order') ?? 0;

        $board->properties()->attach($request->input('property_id'), [
            'notes' => $request->input('notes', ''),
            'order' => $maxOrder + 1,
        ]);

        return $response->setMessage(trans('plugins/real-estate::board.property_added'));
    }

    public function removeProperty(int|string $id, int|string $propertyId, BaseHttpResponse $response)
    {
        $board = Board::query()->findOrFail($id);
        $board->properties()->detach($propertyId);

        return $response->setMessage(trans('plugins/real-estate::board.property_removed'));
    }

    public function getBoardsForProperty(Request $request, BaseHttpResponse $response)
    {
        $boards = Board::query()
            ->with(['client', 'lead'])
            ->select(['id', 'name', 'client_id', 'lead_id'])
            ->latest()
            ->get()
            ->map(function (Board $board) {
                return [
                    'id' => $board->id,
                    'name' => $board->name,
                    'client_name' => $board->client ? $board->client->name : null,
                    'lead_name' => $board->lead ? $board->lead->name : null,
                    'owner_name' => $board->client ? $board->client->name : ($board->lead ? $board->lead->name : 'Sin asignar'),
                ];
            });

        return $response->setData($boards);
    }

    public function searchPropertiesForBoard(Request $request): JsonResponse
    {
        try {
            $boardId = $request->input('board_id');
            $search = $request->input('search', '');
            $type = $request->input('type', '');
            $bedrooms = $request->input('bedrooms', '');
            $bathrooms = $request->input('bathrooms', '');
            $priceMin = $request->input('price_min', '');
            $priceMax = $request->input('price_max', '');
            $clientNotes = $request->input('client_notes', '');
            $page = (int) $request->input('page', 1);
            $perPage = 24;

            $board = Board::query()->findOrFail($boardId);
            $existingIds = $board->properties()->pluck('re_board_properties.property_id')->toArray();

            $query = Property::query()->with('currency');

            // Smart filter from client notes
            if ($clientNotes) {
                $this->applySmartFilter($query, $clientNotes);
            }

            // Manual search
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('location', 'LIKE', "%{$search}%")
                        ->orWhere('unique_id', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            // Type filter
            if ($type) {
                $query->where('type', $type);
            }

            // Bedrooms filter
            if ($bedrooms !== '' && $bedrooms !== null) {
                $query->where('number_bedroom', '>=', (int) $bedrooms);
            }

            // Bathrooms filter
            if ($bathrooms !== '' && $bathrooms !== null) {
                $query->where('number_bathroom', '>=', (int) $bathrooms);
            }

            // Price range
            if ($priceMin !== '' && $priceMin !== null) {
                $query->where('price', '>=', (float) $priceMin);
            }
            if ($priceMax !== '' && $priceMax !== null) {
                $query->where('price', '<=', (float) $priceMax);
            }

            $query->orderBy('created_at', 'desc');

            $paginated = $query->paginate($perPage, ['*'], 'page', $page);

            $items = $paginated->getCollection()->map(function (Property $property) use ($existingIds) {
                try {
                    $image = $property->image
                        ? RvMedia::getImageUrl($property->image, null, false, RvMedia::getDefaultImage())
                        : RvMedia::getDefaultImage();

                    $price = '';
                    try {
                        $price = $property->price_format;
                    } catch (\Throwable $e) {
                        $price = $property->price ? number_format($property->price) : '';
                    }

                    $typeLabel = '';
                    try {
                        $typeLabel = $property->type ? $property->type->label() : '';
                    } catch (\Throwable $e) {
                        $typeLabel = $property->getRawOriginal('type') ?: '';
                    }

                    return [
                        'id' => $property->id,
                        'name' => $property->name,
                        'image' => $image,
                        'price' => $price,
                        'type' => $typeLabel,
                        'location' => $property->location ?: '',
                        'bedrooms' => $property->number_bedroom,
                        'bathrooms' => $property->number_bathroom,
                        'square' => $property->square,
                        'already_added' => in_array($property->id, $existingIds),
                    ];
                } catch (\Throwable $e) {
                    return [
                        'id' => $property->id,
                        'name' => $property->name ?? 'Property #' . $property->id,
                        'image' => RvMedia::getDefaultImage(),
                        'price' => '',
                        'type' => '',
                        'location' => '',
                        'bedrooms' => 0,
                        'bathrooms' => 0,
                        'square' => 0,
                        'already_added' => in_array($property->id, $existingIds),
                    ];
                }
            });

            return response()->json([
                'data' => $items,
                'meta' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'total' => $paginated->total(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'file' => basename($e->getFile()) . ':' . $e->getLine(),
            ], 500);
        }
    }

    protected function applySmartFilter($query, string $notes): void
    {
        $notes = mb_strtolower($notes);

        // Extract bedroom numbers: "3 cuartos", "3 habitaciones", "3 dormitorios"
        if (preg_match('/(\d+)\s*(?:cuarto|habitaci[oó]n|dormitorio|bedroom|room)/iu', $notes, $m)) {
            $query->where('number_bedroom', '>=', (int) $m[1]);
        }

        // Extract bathroom numbers: "2 baños", "2 bathrooms"
        if (preg_match('/(\d+)\s*(?:ba[nñ]o|bathroom)/iu', $notes, $m)) {
            $query->where('number_bathroom', '>=', (int) $m[1]);
        }

        // Extract floor numbers: "2 pisos", "2 niveles", "2 floors"
        if (preg_match('/(\d+)\s*(?:piso|nivel|floor|planta)/iu', $notes, $m)) {
            $query->where('number_floor', '>=', (int) $m[1]);
        }

        // Location keywords: extract place names after "en" preposition
        // e.g. "propiedades en occidente", "casas en San José"
        if (preg_match_all('/(?:en|near|cerca\s+de)\s+([a-záéíóúñü\s]+?)(?:,|$|\.|;|\d)/iu', $notes, $matches)) {
            $query->where(function ($q) use ($matches) {
                foreach ($matches[1] as $location) {
                    $location = trim($location);
                    if (mb_strlen($location) >= 3) {
                        $q->orWhere('location', 'LIKE', "%{$location}%")
                          ->orWhere('name', 'LIKE', "%{$location}%")
                          ->orWhere('description', 'LIKE', "%{$location}%");
                    }
                }
            });
        }

        // Property type keywords
        $typeMap = [
            'sale' => 'sale', 'venta' => 'sale', 'compra' => 'sale', 'comprar' => 'sale',
            'rent' => 'rent', 'alquiler' => 'rent', 'renta' => 'rent', 'arrendamiento' => 'rent',
        ];
        foreach ($typeMap as $keyword => $type) {
            if (str_contains($notes, $keyword)) {
                $query->where('type', $type);
                break;
            }
        }
    }

    public function bulkAddProperties(Request $request, BaseHttpResponse $response)
    {
        $request->validate([
            'board_id' => ['required', 'exists:re_boards,id'],
            'property_ids' => ['required', 'array', 'min:1'],
            'property_ids.*' => ['exists:re_properties,id'],
        ]);

        $board = Board::query()->findOrFail($request->input('board_id'));
        $existingIds = $board->properties()->pluck('re_board_properties.property_id')->toArray();
        $maxOrder = $board->properties()->max('re_board_properties.order') ?? 0;

        $added = 0;
        $skipped = 0;

        foreach ($request->input('property_ids') as $propertyId) {
            if (in_array((int) $propertyId, $existingIds)) {
                $skipped++;
                continue;
            }

            $maxOrder++;
            $board->properties()->attach($propertyId, [
                'order' => $maxOrder,
            ]);
            $added++;
        }

        $message = trans('plugins/real-estate::board.bulk_add_result', [
            'added' => $added,
            'skipped' => $skipped,
        ]);

        return $response->setMessage($message);
    }

    public function addPropertyToBoard(Request $request, BaseHttpResponse $response)
    {
        $request->validate([
            'board_id' => ['required', 'exists:re_boards,id'],
            'property_id' => ['required', 'exists:re_properties,id'],
        ]);

        $board = Board::query()->findOrFail($request->input('board_id'));

        if ($board->properties()->where('property_id', $request->input('property_id'))->exists()) {
            return $response
                ->setError()
                ->setMessage(trans('plugins/real-estate::board.property_already_in_board'));
        }

        $maxOrder = $board->properties()->max('re_board_properties.order') ?? 0;

        $board->properties()->attach($request->input('property_id'), [
            'order' => $maxOrder + 1,
        ]);

        return $response->setMessage(trans('plugins/real-estate::board.property_added'));
    }
}
