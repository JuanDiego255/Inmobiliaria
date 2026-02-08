<?php

namespace Botble\Career\Http\Controllers;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Career\Models\Career;
use Botble\Career\Repositories\Interfaces\CareerInterface;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\SeoHelper\SeoOpenGraph;
use Botble\Slug\Facades\SlugHelper;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PublicController extends Controller
{
    public function careers(Request $request, CareerInterface $careerRepository)
    {
        SeoHelper::setTitle(__('Careers'));

        Theme::breadcrumb()
            ->add(__('Home'), route('public.index'))
            ->add(__('Careers'), route('public.careers'));

        $careers = $careerRepository->advancedGet([
            'condition' => [
                'careers.status' => BaseStatusEnum::PUBLISHED,
            ],
            'paginate' => [
                'per_page' => 10,
                'current_paged' => $request->integer('page', 1) ?: 1,
            ],
            'order_by' => ['careers.created_at' => 'DESC'],
        ]);

        return Theme::scope('career.careers', compact('careers'))->render();
    }

    public function career(string $key, CareerInterface $careerRepository)
    {
        $slug = SlugHelper::getSlug($key, SlugHelper::getPrefix(Career::class));

        if (! $slug) {
            abort(404);
        }

        $career = $careerRepository->getFirstBy([
            'id' => $slug->reference_id,
            'status' => BaseStatusEnum::PUBLISHED,
        ]);

        if (! $career) {
            abort(404);
        }

        SeoHelper::setTitle($career->name)
            ->setDescription($career->description);

        $meta = new SeoOpenGraph();
        $meta->setDescription($career->description);
        $meta->setUrl($career->url);
        $meta->setTitle($career->name);
        $meta->setType('article');

        SeoHelper::setSeoOpenGraph($meta);

        Theme::breadcrumb()
            ->add(__('Home'), route('public.index'))
            ->add($career->name, $career->url);

        return Theme::scope('career.career', compact('career'))->render();
    }
}
