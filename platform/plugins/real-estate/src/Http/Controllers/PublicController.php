<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\EmailHandler;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Helper;
use Botble\Base\Supports\RepositoryHelper;
use Botble\Location\Models\State;
use Botble\Location\Repositories\Interfaces\CityInterface;
use Botble\Media\Facades\RvMedia;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Http\Requests\SendConsultRequest;
use Botble\RealEstate\Models\Account;
use Botble\RealEstate\Models\Category;
use Botble\RealEstate\Models\Consult;
use Botble\RealEstate\Models\Currency;
use Botble\RealEstate\Models\Project;
use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Repositories\Interfaces\ProjectInterface;
use Botble\RealEstate\Repositories\Interfaces\PropertyInterface;
use Botble\RssFeed\Facades\RssFeed;
use Botble\RssFeed\FeedItem;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\SeoHelper\SeoOpenGraph;
use Botble\Slug\Facades\SlugHelper;
use Botble\Theme\Facades\Theme;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Mimey\MimeTypes;

class PublicController extends Controller
{
    public function postSendConsult(
        SendConsultRequest $request,
        BaseHttpResponse $response,
        PropertyInterface $propertyRepository,
        ProjectInterface $projectRepository
    ) {
        try {
            $sendTo = null;
            $link = null;
            $subject = null;

            if ($request->input('type') == 'project') {
                $request->merge(['project_id' => $request->input('data_id')]);
                $project = $projectRepository->findById($request->input('data_id'));
                if ($project) {
                    $link = $project->url;
                    $subject = $project->name;
                }
            } else {
                $request->merge(['property_id' => $request->input('data_id')]);
                $property = $propertyRepository->findById($request->input('data_id'), ['author']);
                if ($property) {
                    $link = $property->url;
                    $subject = $property->name;

                    if ($property->author->email) {
                        $sendTo = $property->author->email;
                    }
                }
            }

            $ipAddress = $request->ip();

            $consult = Consult::query()->create(array_merge($request->input(), ['ip_address' => $ipAddress]));

            EmailHandler::setModule(REAL_ESTATE_MODULE_SCREEN_NAME)
                ->setVariableValues([
                    'consult_name' => $consult->name,
                    'consult_email' => $consult->email,
                    'consult_phone' => $consult->phone,
                    'consult_content' => $consult->content,
                    'consult_link' => $link,
                    'consult_subject' => $subject,
                    'consult_ip_address' => $consult->ip_address,
                ])
                ->sendUsingTemplate('notice', $sendTo);

            return $response->setMessage(trans('plugins/real-estate::consult.email.success'));
        } catch (Exception $exception) {
            info($exception->getMessage());

            return $response
                ->setError()
                ->setMessage(trans('plugins/real-estate::consult.email.failed'));
        }
    }

    public function getProject(string $key, ProjectInterface $projectRepository)
    {
        $slug = SlugHelper::getSlug($key, SlugHelper::getPrefix(Project::class));

        if (! $slug) {
            abort(404);
        }

        $project = $projectRepository->advancedGet(array_merge([
            'with' => RealEstateHelper::getProjectRelationsQuery(),
            'condition' => ['id' => $slug->reference_id],
            'take' => 1,
        ], RealEstateHelper::getReviewExtraData()));

        if (! $project) {
            abort(404);
        }

        if ($project->slugable->key !== $key) {
            return redirect()->to($project->url);
        }

        SeoHelper::setTitle($project->name)->setDescription(Str::words($project->description, 120));

        $meta = new SeoOpenGraph();
        if ($project->image) {
            $meta->setImage(RvMedia::getImageUrl($project->image));
        }
        $meta->setDescription($project->description);
        $meta->setUrl($project->url);
        $meta->setTitle($project->name);
        $meta->setType('article');

        SeoHelper::setSeoOpenGraph($meta);

        Theme::breadcrumb()
            ->add(__('Home'), route('public.index'))
            ->add(__('Projects'), route('public.projects'))
            ->add($project->name, $project->url);

        $relatedProjects = $projectRepository->getRelatedProjects(
            $project->id,
            (int)theme_option('number_of_related_projects', 8)
        );

        if (function_exists('admin_bar')) {
            admin_bar()->registerLink(__('Edit this project'), route('project.edit', $project->id));
        }

        Helper::handleViewCount($project, 'viewed_project');

        do_action(BASE_ACTION_PUBLIC_RENDER_SINGLE, PROJECT_MODULE_SCREEN_NAME, $project);

        $images = [];

        if (! empty($project->images) && is_array($project->images)) {
            foreach ($project->images as $image) {
                $images[] = RvMedia::getImageUrl($image, null, false, RvMedia::getDefaultImage());
            }
        }

        return Theme::scope('real-estate.project', compact('project', 'images', 'relatedProjects'))->render();
    }

    public function getProperty(string $key, PropertyInterface $propertyRepository)
    {
        $slug = SlugHelper::getSlug($key, SlugHelper::getPrefix(Property::class));

        if (! $slug) {
            abort(404);
        }

        $property = $propertyRepository->getProperty(
            $slug->reference_id,
            RealEstateHelper::getPropertyRelationsQuery(),
            RealEstateHelper::getReviewExtraData()
        );

        if (! $property) {
            abort(404);
        }

        if ($property->slugable->key !== $key) {
            return redirect()->to($property->url);
        }

        SeoHelper::setTitle($property->name)->setDescription(Str::words($property->description, 120));

        $meta = new SeoOpenGraph();
        if ($property->image) {
            $meta->setImage(RvMedia::getImageUrl($property->image));
        }
        $meta->setDescription($property->description);
        $meta->setUrl($property->url);
        $meta->setTitle($property->name);
        $meta->setType('article');

        SeoHelper::setSeoOpenGraph($meta);

        Theme::breadcrumb()
            ->add(__('Home'), route('public.index'))
            ->add(__('Properties'), route('public.properties'))
            ->add($property->name, $property->url);

        Helper::handleViewCount($property, 'viewed_property');

        do_action(BASE_ACTION_PUBLIC_RENDER_SINGLE, PROPERTY_MODULE_SCREEN_NAME, $property);

        if (function_exists('admin_bar')) {
            admin_bar()->registerLink(__('Edit this property'), route('property.edit', $property->id));
        }

        $images = [];
        if (! empty($property->images) && is_array($property->images)) {
            foreach ($property->images as $image) {
                $images[] = RvMedia::getImageUrl($image, null, false, RvMedia::getDefaultImage());
            }
        }

        return Theme::scope('real-estate.property', compact('property', 'images'))->render();
    }

    public function getProjects(Request $request, BaseHttpResponse $response)
    {
        SeoHelper::setTitle(__('Projects'));

        $projects = RealEstateHelper::getProjectsFilter((int)theme_option('number_of_projects_per_page') ?: 12, RealEstateHelper::getReviewExtraData());

        if ($request->ajax()) {
            if ($request->input('minimal')) {
                return $response->setData(Theme::partial('search-suggestion', ['items' => $projects]));
            }

            return $response->setData(Theme::partial('real-estate.projects.items', compact('projects')));
        }

        return Theme::scope('real-estate.projects', compact('projects'))->render();
    }

    public function getProperties(Request $request, BaseHttpResponse $response)
    {
        SeoHelper::setTitle(__('Properties'));

        $properties = RealEstateHelper::getPropertiesFilter((int)theme_option('number_of_properties_per_page') ?: 12, RealEstateHelper::getReviewExtraData());

        if ($request->ajax()) {
            if ($request->query('minimal')) {
                return $response->setData(Theme::partial('search-suggestion', ['items' => $properties]));
            }

            return $response->setData(Theme::partial('real-estate.properties.items', compact('properties')));
        }

        return Theme::scope('real-estate.properties', compact('properties'))->render();
    }

    public function getPropertyCategory(
        string $key,
        Request $request,
        PropertyInterface $propertyRepository
    ) {
        $slug = SlugHelper::getSlug($key, SlugHelper::getPrefix(Category::class));

        if (! $slug) {
            abort(404);
        }

        $category = Category::query()
            ->where('id', $slug->reference_id)
            ->with(['slugable'])
            ->firstOrFail();

        SeoHelper::setTitle($category->name)->setDescription(Str::words($category->description, 120));

        $meta = new SeoOpenGraph();
        $meta->setDescription($category->description);
        $meta->setUrl($category->url);
        $meta->setTitle($category->name);
        $meta->setType('article');

        SeoHelper::setSeoOpenGraph($meta);

        Theme::breadcrumb()
            ->add(__('Home'), route('public.index'))
            ->add($category->name, $category->url);

        $filters = [
            'category_id' => $category->id,
        ];

        $perPage = (int)theme_option('number_of_properties_per_page', 12);

        $params = [
            'paginate' => [
                'per_page' => $perPage ?: 12,
                'current_paged' => $request->integer('page', 1),
            ],
            'order_by' => ['re_properties.created_at' => 'DESC'],
            'with' => RealEstateHelper::getPropertyRelationsQuery(),
        ];

        $properties = $propertyRepository->getProperties($filters, $params);

        return Theme::scope('real-estate.property-category', compact('category', 'properties'))->render();
    }

    public function changeCurrency(
        Request $request,
        BaseHttpResponse $response,
        $title = null
    ) {
        if (empty($title)) {
            $title = $request->input('currency');
        }

        if (! $title) {
            return $response;
        }

        $currency = Currency::query()
            ->where('title', $title)
            ->first();

        if ($currency) {
            cms_currency()->setApplicationCurrency($currency);
        }

        return $response;
    }

    public function getPropertyFeeds(PropertyInterface $propertyRepository)
    {
        if (! is_plugin_active('rss-feed')) {
            abort(404);
        }

        $data = $propertyRepository->getProperties([], [
            'take' => 20,
            'with' => ['slugable', 'categories', 'author'],
        ]);

        $feedItems = collect();

        foreach ($data as $item) {
            $imageURL = RvMedia::getImageUrl($item->image, null, false, RvMedia::getDefaultImage());

            $feedItem = FeedItem::create()
                ->id($item->id)
                ->title(BaseHelper::clean($item->name))
                ->summary(BaseHelper::clean($item->description))
                ->updated($item->updated_at)
                ->enclosure($imageURL)
                ->enclosureType((new MimeTypes())->getMimeType(File::extension($imageURL)))
                ->enclosureLength(RssFeed::remoteFilesize($imageURL))
                ->category((string)$item->category->name)
                ->link((string)$item->url);

            if (method_exists($feedItem, 'author')) {
                $feedItem = $feedItem->author($item->author_id && $item->author->name ? $item->author->name : '');
            } else {
                $feedItem = $feedItem
                    ->authorName($item->author_id && $item->author->name ? $item->author->name : '')
                    ->authorEmail($item->author_id && $item->author->email ? $item->author->email : '');
            }

            $feedItems[] = $feedItem;
        }

        return RssFeed::renderFeedItems(
            $feedItems,
            'Properties feed',
            'Latest properties from ' . theme_option('site_title')
        );
    }

    public function getProjectFeeds(ProjectInterface $projectRepository)
    {
        if (! is_plugin_active('rss-feed')) {
            abort(404);
        }

        $data = $projectRepository->getProjects(
            [],
            [
                'take' => 20,
                'width' => ['categories'],
            ]
        );

        $feedItems = collect();

        foreach ($data as $item) {
            $imageURL = RvMedia::getImageUrl($item->image, null, false, RvMedia::getDefaultImage());

            $feedItem = FeedItem::create()
                ->id($item->id)
                ->title(BaseHelper::clean($item->name))
                ->summary(BaseHelper::clean($item->description))
                ->updated($item->updated_at)
                ->enclosure($imageURL)
                ->enclosureType((new MimeTypes())->getMimeType(File::extension($imageURL)))
                ->enclosureLength(RssFeed::remoteFilesize($imageURL))
                ->category((string) $item->category->name)
                ->link((string) $item->url);

            if (method_exists($feedItem, 'author')) {
                $feedItem = $feedItem->author($item->author_id && $item->author->name ? $item->author->name : '');
            } else {
                $feedItem = $feedItem
                    ->authorName($item->author_id && $item->author->name ? $item->author->name : '')
                    ->authorEmail($item->author_id && $item->author->email ? $item->author->email : '');
            }

            $feedItems[] = $feedItem;
        }

        return RssFeed::renderFeedItems(
            $feedItems,
            'Projects feed',
            'Latest projects from ' . theme_option('site_title')
        );
    }

    public function getProjectsByCity(
        string $slug,
        Request $request,
        CityInterface $cityRepository,
        BaseHttpResponse $response
    ) {
        $city = $cityRepository->getFirstBy(compact('slug'));

        if (! $city) {
            abort(404);
        }

        SeoHelper::setTitle(__('Projects in :city', ['city' => $city->name]));

        Theme::breadcrumb()
            ->add(__('Home'), route('public.index'))
            ->add(SeoHelper::getTitle(), route('public.projects-by-city', $city->slug));

        do_action(BASE_ACTION_PUBLIC_RENDER_SINGLE, CITY_MODULE_SCREEN_NAME, $city);

        $perPage = $request->integer('per_page') ?: (int)theme_option('number_of_projects_per_page', 12);

        $request->merge(['city' => $slug]);

        $projects = RealEstateHelper::getProjectsFilter($perPage, RealEstateHelper::getReviewExtraData());

        if ($request->ajax()) {
            if ($request->input('minimal')) {
                return $response->setData(Theme::partial('search-suggestion', ['items' => $projects]));
            }

            return $response->setData(Theme::partial('real-estate.projects.items', ['projects' => $projects]));
        }

        return Theme::scope('real-estate.projects', [
            'projects' => $projects,
            'ajaxUrl' => route('public.projects-by-city', $city->slug),
            'actionUrl' => route('public.projects-by-city', $city->slug),
        ])
            ->render();
    }

    public function getPropertiesByCity(
        string $slug,
        Request $request,
        CityInterface $cityRepository,
        BaseHttpResponse $response
    ) {
        $city = $cityRepository->getFirstBy(compact('slug'));

        if (! $city) {
            abort(404);
        }

        SeoHelper::setTitle(__('Properties in :city', ['city' => $city->name]));

        do_action(BASE_ACTION_PUBLIC_RENDER_SINGLE, CITY_MODULE_SCREEN_NAME, $city);

        Theme::breadcrumb()
            ->add(__('Home'), route('public.index'))
            ->add(SeoHelper::getTitle(), route('public.properties-by-city', $city->slug));

        $perPage = $request->integer('per_page') ?: (int)theme_option('number_of_properties_per_page', 12);

        $request->merge(['city' => $slug]);

        $properties = RealEstateHelper::getPropertiesFilter($perPage, RealEstateHelper::getReviewExtraData());

        if ($request->ajax()) {
            if ($request->input('minimal')) {
                return $response->setData(Theme::partial('search-suggestion', ['items' => $properties]));
            }

            return $response->setData(Theme::partial('real-estate.properties.items', ['properties' => $properties]));
        }

        return Theme::scope('real-estate.properties', [
            'properties' => $properties,
            'ajaxUrl' => route('public.properties-by-city', $city->slug),
            'actionUrl' => route('public.properties-by-city', $city->slug),
        ])
            ->render();
    }

    public function getProjectsByState(
        string $slug,
        Request $request,
        BaseHttpResponse $response
    ) {
        $state = State::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->where('slug', $slug)
            ->firstOrFail();

        SeoHelper::setTitle(__('Projects in :state', ['state' => $state->name]));

        Theme::breadcrumb()
            ->add(__('Home'), route('public.index'))
            ->add(SeoHelper::getTitle(), route('public.projects-by-city', $state->slug));

        do_action(BASE_ACTION_PUBLIC_RENDER_SINGLE, STATE_MODULE_SCREEN_NAME, $state);

        $perPage = $request->integer('per_page') ?: (int)theme_option('number_of_projects_per_page', 12);

        $request->merge(['state' => $slug]);

        $projects = RealEstateHelper::getProjectsFilter($perPage, RealEstateHelper::getReviewExtraData());

        if ($request->ajax()) {
            if ($request->input('minimal')) {
                return $response->setData(Theme::partial('search-suggestion', ['items' => $projects]));
            }

            return $response->setData(Theme::partial('real-estate.projects.items', ['projects' => $projects]));
        }

        return Theme::scope('real-estate.projects', [
            'projects' => $projects,
            'ajaxUrl' => route('public.projects-by-state', $state->slug),
            'actionUrl' => route('public.projects-by-state', $state->slug),
        ])
            ->render();
    }

    public function getPropertiesByState(
        string $slug,
        Request $request,
        BaseHttpResponse $response
    ) {
        $state = State::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->where('slug', $slug)
            ->firstOrFail();

        SeoHelper::setTitle(__('Properties in :state', ['state' => $state->name]));

        do_action(BASE_ACTION_PUBLIC_RENDER_SINGLE, STATE_MODULE_SCREEN_NAME, $state);

        Theme::breadcrumb()
            ->add(__('Home'), route('public.index'))
            ->add(SeoHelper::getTitle(), route('public.properties-by-state', $state->slug));

        $perPage = $request->integer('per_page') ?: (int)theme_option('number_of_properties_per_page', 12);

        $request->merge(['state' => $slug]);

        $properties = RealEstateHelper::getPropertiesFilter($perPage, RealEstateHelper::getReviewExtraData());

        if ($request->ajax()) {
            if ($request->input('minimal')) {
                return $response->setData(Theme::partial('search-suggestion', ['items' => $properties]));
            }

            return $response->setData(Theme::partial('real-estate.properties.items', ['properties' => $properties]));
        }

        return Theme::scope('real-estate.properties', [
            'properties' => $properties,
            'ajaxUrl' => route('public.properties-by-state', $state->slug),
            'actionUrl' => route('public.properties-by-state', $state->slug),
        ])
            ->render();
    }

    public function getAgents()
    {
        if (RealEstateHelper::isDisabledPublicProfile()) {
            abort(404);
        }

        $accounts = Account::query()
            ->where('is_public_profile', true)
            ->orderByDesc('id')
            ->withCount([
                'properties' => function ($query) {
                    return RepositoryHelper::applyBeforeExecuteQuery($query, $query->getModel());
                },
            ])
            ->with(['avatar'])
            ->paginate(12);

        SeoHelper::setTitle(__('Agents'));

        Theme::breadcrumb()->add(__('Home'), route('public.index'))->add(__('Agents'), route('public.agents'));

        return Theme::scope('real-estate.agents', compact('accounts'))->render();
    }

    public function getAgent(
        string $username,
        Request $request,
        PropertyInterface $propertyRepository
    ) {
        if (RealEstateHelper::isDisabledPublicProfile()) {
            abort(404);
        }

        $account = Account::query()
            ->where([
                'username' => $username,
                'is_public_profile' => true,
            ])
            ->firstOrFail();

        SeoHelper::setTitle($account->name);

        Theme::breadcrumb()
            ->add(__('Home'), route('public.index'))
            ->add(SeoHelper::getTitle(), $account->url);

        if (function_exists('admin_bar')) {
            admin_bar()->registerLink(__('Edit this agent'), route('account.edit', $account->getKey()));
        }

        $params = [
            'condition' => [
                'author_id' => $account->getKey(),
                'author_type' => Account::class,
            ],
            'paginate' => [
                'per_page' => 12,
                'current_paged' => $request->integer('page', 1),
            ],
            'with' => RealEstateHelper::getPropertyRelationsQuery(),
        ];

        $properties = $propertyRepository->advancedGet($params + RealEstateHelper::getReviewExtraData());

        return Theme::scope('real-estate.agent', compact('properties', 'account'))
            ->render();
    }
}
