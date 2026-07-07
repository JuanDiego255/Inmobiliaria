<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\RealEstate\Models\Property;
use Illuminate\Http\Request;

class CrmPropertySearchController extends BaseController
{
    public function search(Request $request)
    {
        $q = $request->input('q', '');
        if (strlen($q) < 2) {
            return response()->json(['data' => []]);
        }
        $properties = Property::query()
            ->where('name', 'LIKE', "%{$q}%")
            ->select('id', 'name', 'price')
            ->limit(20)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price_formatted' => number_format($p->price ?? 0),
                ];
            });
        return response()->json(['data' => $properties]);
    }
}
