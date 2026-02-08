<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\Media\Facades\RvMedia;
use Botble\RealEstate\Models\Board;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;
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

        $properties = $board->properties->map(function ($property) {
            $images = [];
            if (! empty($property->images) && is_array($property->images)) {
                foreach ($property->images as $image) {
                    $images[] = RvMedia::getImageUrl($image, null, false, RvMedia::getDefaultImage());
                }
            }
            $property->formatted_images = $images;
            return $property;
        });

        if (defined('THEME_MODULE_SCREEN_NAME')) {
            return Theme::scope('real-estate.board', compact('board', 'properties'))->render();
        }

        return view('plugins/real-estate::boards.public-board', compact('board', 'properties'));
    }
}
