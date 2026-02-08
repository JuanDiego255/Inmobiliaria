<?php

namespace Botble\RssFeed\Http\Controllers;

use Botble\Base\Facades\BaseHelper;
use Botble\Blog\Models\Post;
use Botble\Media\Facades\RvMedia;
use Botble\RssFeed\Facades\RssFeed;
use Botble\RssFeed\FeedItem;
use Botble\Theme\Http\Controllers\PublicController;
use Illuminate\Support\Facades\File;
use Mimey\MimeTypes;

class RssFeedController extends PublicController
{
    public function getPostFeeds()
    {
        if (! is_plugin_active('blog')) {
            abort(404);
        }

        $posts = Post::query()
            ->wherePublished()
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        $feedItems = collect();

        foreach ($posts as $post) {
            if (! $post instanceof Post) {
                continue;
            }

            $imageURL = RvMedia::getImageUrl($post->image, null, false, RvMedia::getDefaultImage());

            $description = BaseHelper::clean($post->description);

            $category = $post->categories()->value('name');

            $author = (string) $post->author?->name;

            $feedItem = FeedItem::create()
                ->id($post->getKey())
                ->title(BaseHelper::clean($post->name))
                ->when($description, fn (FeedItem $item) => $item->summary($description))
                ->updated($post->updated_at)
                ->enclosure($imageURL)
                ->enclosureType((new MimeTypes())->getMimeType(File::extension($imageURL)))
                ->enclosureLength(RssFeed::remoteFilesize($imageURL))
                ->when($category, fn (FeedItem $feedItem) => $feedItem->category($category))
                ->link((string) $post->url)
                ->when(! empty($author), function (FeedItem $feedItem) use ($post, $author) {
                    if (method_exists($feedItem, 'author')) {
                        return $feedItem->author($author);
                    }

                    return $feedItem
                        ->authorName($author)
                        ->authorEmail((string) $post->author?->email);
                });

            $feedItems[] = $feedItem;
        }

        return RssFeed::renderFeedItems($feedItems, 'Posts feed', sprintf('Latest posts from %s', theme_option('site_title')));
    }
}
