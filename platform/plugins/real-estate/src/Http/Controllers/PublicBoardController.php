<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Media\Facades\RvMedia;
use Botble\RealEstate\Models\Board;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PublicBoardController extends Controller
{
    public function show(string $token)
    {
        $board = Board::query()
            ->where('token', $token)
            ->where('status', 'active')
            ->with(['client', 'properties' => function ($query) {
                $query->with(['currency']);
            }])
            ->firstOrFail();

        SeoHelper::setTitle($board->name);

        $statuses = [
            'properties' => trans('plugins/real-estate::board.kanban.properties'),
            'interested' => trans('plugins/real-estate::board.kanban.interested'),
            'visited' => trans('plugins/real-estate::board.kanban.visited'),
            'discarded' => trans('plugins/real-estate::board.kanban.discarded'),
        ];

        $columns = [];
        foreach ($statuses as $key => $label) {
            $columns[$key] = [
                'label' => $label,
                'properties' => collect(),
            ];
        }

        foreach ($board->properties as $property) {
            $images = [];
            if (! empty($property->images) && is_array($property->images)) {
                foreach ($property->images as $image) {
                    $images[] = RvMedia::getImageUrl($image, null, false, RvMedia::getDefaultImage());
                }
            }
            $property->formatted_images = $images;

            $status = $property->pivot->property_status ?: 'properties';
            if (isset($columns[$status])) {
                $columns[$status]['properties']->push($property);
            } else {
                $columns['properties']['properties']->push($property);
            }
        }

        if (defined('THEME_MODULE_SCREEN_NAME')) {
            return Theme::scope('real-estate.board', compact('board', 'columns', 'statuses'))->render();
        }

        return view('plugins/real-estate::boards.public-board', compact('board', 'columns', 'statuses'));
    }

    public function updatePropertyStatus(string $token, Request $request, BaseHttpResponse $response)
    {
        $request->validate([
            'property_id' => ['required', 'integer'],
            'status' => ['required', 'string', 'in:properties,interested,visited,discarded'],
        ]);

        $board = Board::query()
            ->where('token', $token)
            ->where('status', 'active')
            ->firstOrFail();

        $board->properties()->updateExistingPivot($request->input('property_id'), [
            'property_status' => $request->input('status'),
        ]);

        return $response->setMessage(trans('plugins/real-estate::board.status_updated'));
    }
}
