<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\RealEstate\Services\VirtualTourService;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;

class VirtualTourController
{
    public function index(Request $request)
    {
        SeoHelper::setTitle(__('Tours Virtuales 360'));
        SeoHelper::setDescription(__('Explora nuestras propiedades en recorridos virtuales inmersivos de 360 grados.'));

        $service = new VirtualTourService();

        $tours = [];
        $meta = [];

        if ($service->isConfigured()) {
            $result = $service->listTours([
                'q' => $request->input('q'),
                'page' => $request->input('page', 1),
                'per_page' => 12,
            ]);

            if ($result) {
                $tours = $result['data'] ?? [];
                $meta = $result['meta'] ?? [];
            }
        }

        $q = $request->input('q');

        return Theme::scope('real-estate.virtual-tours', compact('tours', 'meta', 'q'))->render();
    }

    public function apiList(Request $request)
    {
        $service = new VirtualTourService();

        if (! $service->isConfigured()) {
            return response()->json(['data' => [], 'meta' => []]);
        }

        $result = $service->listTours([
            'q' => $request->input('q'),
            'page' => $request->input('page', 1),
            'per_page' => $request->input('per_page', 12),
        ]);

        return response()->json($result ?? ['data' => [], 'meta' => []]);
    }
}
