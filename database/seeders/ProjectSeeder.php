<?php

namespace Database\Seeders;

use Botble\Base\Supports\BaseSeeder;
use Illuminate\Support\Facades\DB;

class ProjectSeeder extends BaseSeeder
{
    public function run(): void
    {
        $this->uploadFiles('projects');

        DB::statement('UPDATE re_projects SET views = FLOOR(rand() * 10000) + 1;');
    }
}
