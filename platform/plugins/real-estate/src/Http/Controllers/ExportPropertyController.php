<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\Base\Facades\Assets;
use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\PageTitle;
use Botble\Base\Http\Controllers\BaseController;
use Botble\RealEstate\Exports\PropertiesExport;
use Botble\RealEstate\Models\Property;
use Maatwebsite\Excel\Excel;

class ExportPropertyController extends BaseController
{
    public function index()
    {
        PageTitle::setTitle(trans('plugins/real-estate::export.properties.name'));

        Assets::addScriptsDirectly(['vendor/core/plugins/real-estate/js/export.js']);

        $totalProperties = Property::query()->count();

        return view('plugins/real-estate::export.properties', compact('totalProperties'));
    }

    public function export(PropertiesExport $propertiesExport)
    {
        BaseHelper::maximumExecutionTimeAndMemoryLimit();

        return $propertiesExport->download('export_properties.csv', Excel::CSV, ['Content-Type' => 'text/csv']);
    }
}
