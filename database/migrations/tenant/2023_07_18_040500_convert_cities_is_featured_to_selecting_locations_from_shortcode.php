<?php

use Botble\Location\Models\City;
use Botble\Page\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! is_plugin_active('real-estate') || ! is_plugin_active('location')) {
            return;
        }

        if (! Schema::hasColumn('cities', 'is_featured')) {
            return;
        }

        $featuredCities = City::query()
            ->where('is_featured', true)
            ->pluck('id');

        if (empty($featuredCities)) {
            return;
        }

        $featuredCityIds = $featuredCities->implode(',');

        $pages = Page::query()
            ->where('content', 'like', '%projects-by-locations%')
            ->orWhere('content', 'like', '%properties-by-locations%')
            ->get();

        foreach ($pages as $page) {
            $content = $page->content;

            $content = str_replace(
                ['][/projects-by-locations]', '][/properties-by-locations]'],
                [
                    ' city="' . $featuredCityIds . '"][/projects-by-locations]',
                    ' city="' . $featuredCityIds . '"][/properties-by-locations]',
                ],
                $content
            );

            $page->update([
                'content' => $content,
            ]);
        }

        if (! is_plugin_active('language') || ! is_plugin_active('language-advanced')) {
            return;
        }

        $translations = DB::table('pages_translations')
            ->where('content', 'like', '%projects-by-locations%')
            ->orWhere('content', 'like', '%properties-by-locations%')
            ->get();

        foreach ($translations as $translation) {
            $content = $translation->content;

            $content = str_replace(
                ['][/projects-by-locations]', '][/properties-by-locations]'],
                [
                    ' city="' . $featuredCityIds . '"][/projects-by-locations]',
                    ' city="' . $featuredCityIds . '"][/properties-by-locations]',
                ],
                $content
            );

            DB::table('pages_translations')
                ->where('pages_id', $translation->pages_id)
                ->update([
                    'content' => $content,
                ]);
        }
    }
};
