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
use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Tables\BoardTable;
use Exception;
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
        $board = Board::query()->create($request->input());

        event(new CreatedContentEvent(BOARD_MODULE_SCREEN_NAME, $request, $board));

        return $response
            ->setPreviousUrl(route('board.index'))
            ->setNextUrl(route('board.edit', $board->id))
            ->setMessage(trans('core/base::notices.create_success_message'));
    }

    public function edit(int|string $id, FormBuilder $formBuilder, Request $request)
    {
        $board = Board::query()->with(['client', 'properties'])->findOrFail($id);

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
            ->with('client')
            ->select(['id', 'name', 'client_id'])
            ->latest()
            ->get()
            ->map(function (Board $board) {
                return [
                    'id' => $board->id,
                    'name' => $board->name,
                    'client_name' => $board->client->name,
                ];
            });

        return $response->setData($boards);
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
