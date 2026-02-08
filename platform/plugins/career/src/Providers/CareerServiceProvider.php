<?php

namespace Botble\Career\Providers;

use Botble\Base\Traits\LoadAndPublishDataTrait;
use Botble\Career\Models\Career;
use Botble\Career\Repositories\Eloquent\CareerRepository;
use Botble\Career\Repositories\Interfaces\CareerInterface;
use Botble\Language\Facades\Language;
use Botble\LanguageAdvanced\Supports\LanguageAdvancedManager;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Slug\Facades\SlugHelper;
use Botble\Theme\Facades\SiteMapManager;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class CareerServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function register(): void
    {
        $this->app->bind(CareerInterface::class, function () {
            return new CareerRepository(new Career());
        });
    }

    public function boot(): void
    {
        SlugHelper::registerModule(Career::class, 'Careers');
        SlugHelper::setPrefix(Career::class, 'careers');

        $this->setNamespace('plugins/career')
            ->loadAndPublishConfigurations(['permissions', 'general'])
            ->loadMigrations()
            ->loadHelpers()
            ->loadAndPublishTranslations()
            ->loadRoutes();

        Event::listen(RouteMatched::class, function () {
            dashboard_menu()->registerItem([
                'id' => 'cms-plugins-career',
                'priority' => 5,
                'parent_id' => null,
                'name' => 'plugins/career::career.name',
                'icon' => 'far fa-newspaper',
                'url' => route('career.index'),
                'permissions' => ['career.index'],
            ]);
        });

        if (defined('LANGUAGE_MODULE_SCREEN_NAME')) {
            if (
                defined('LANGUAGE_ADVANCED_MODULE_SCREEN_NAME') &&
                $this->app['config']->get('plugins.career.general.use_language_v2')
            ) {
                LanguageAdvancedManager::registerModule(Career::class, [
                    'name',
                    'location',
                    'salary',
                    'description',
                    'content',
                ]);
            } else {
                Language::registerModule([Career::class]);
            }
        }

        $this->app->booted(function () {
            SeoHelper::registerModule(Career::class);
        });

        $this->app->register(EventServiceProvider::class);

        SiteMapManager::registerKey(['careers']);
    }
}
