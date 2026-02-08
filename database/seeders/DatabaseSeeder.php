<?php

namespace Database\Seeders;

use Botble\ACL\Database\Seeders\UserSeeder;
use Botble\Base\Events\SeederPrepared;
use Botble\Base\Supports\BaseSeeder;
use Botble\Base\Supports\Database;
use Botble\Language\Database\Seeders\LanguageSeeder;
use Botble\RealEstate\Database\Seeders\CurrencySeeder;

class DatabaseSeeder extends BaseSeeder
{
    public function run(): void
    {
        SeederPrepared::dispatch();

        Database::restoreFromPath(base_path('database.sql'));

        $this->call([
            LanguageSeeder::class,
            CurrencySeeder::class,
            CategorySeeder::class,
            FacilitySeeder::class,
            FeatureSeeder::class,
            PackageSeeder::class,
            AccountSeeder::class,
            UserSeeder::class,
            SettingSeeder::class,
            PageSeeder::class,
            LatLongSeeder::class,
            MenuSeeder::class,
            ThemeOptionSeeder::class,
            BlogSeeder::class,
            ProjectSeeder::class,
            PropertySeeder::class,
            ReviewSeeder::class,
        ]);

        $this->uploadFiles('banner');
        $this->uploadFiles('cities');
        $this->uploadFiles('logo');

        $this->finished();
    }
}
