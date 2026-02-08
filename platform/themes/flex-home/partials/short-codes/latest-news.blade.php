@if (is_plugin_active('blog'))
    <div
        class="box_shadow"
        style="margin-bottom: 0;padding-bottom: 80px;"
    >
        <div class="container-fluid w90">
            <div class="discover">
                <div class="row">
                    <div class="col-12">
                        <h2>{!! BaseHelper::clean($title) !!}</h2>
                        @if ($subtitle)
                            <p>{!! BaseHelper::clean($subtitle) !!}</p>
                        @endif
                        <br>
                        <div class="blog-container">
                            <div class="row">
                                @foreach ($posts as $post)
                                    <div class="col-md-3 col-sm-6 container-grid">
                                        <div class="grid-in">
                                            <div class="grid-shadow">
                                                <div
                                                    class="hourseitem"
                                                    style="margin-top: 0; "
                                                >
                                                    <div class="blii">
                                                        <div class="img"><img
                                                                class="thumb"
                                                                src="{{ RvMedia::getImageUrl($post->image, 'small', false, RvMedia::getDefaultImage()) }}"
                                                                alt="{{ $post->name }}"
                                                                style="border-radius: 0"
                                                            >
                                                        </div>
                                                        <a
                                                            class="linkdetail"
                                                            href="{{ $post->url }}"
                                                        ></a>
                                                    </div>
                                                </div>
                                                <div class="grid-h">
                                                    <div class="blog-title">
                                                        <a href="{{ $post->url }}">
                                                            <h2>{{ $post->name }}</h2>
                                                        </a>
                                                        <div class="post-meta">
                                                            <p class="d-inline-block">
                                                                {{ $post->created_at->translatedFormat('M d, Y') }}
                                                                {{ __('in') }}
                                                                @if ($post->firstCategory)
                                                                    &nbsp;<a
                                                                        href="{{ $post->firstCategory->url }}">{{ $post->firstCategory->name }}</a>&nbsp;
                                                                @endif
                                                            </p> - <p class="d-inline-block"><i class="fa fa-eye"></i>
                                                                {{ number_format($post->views) }}</p>
                                                        </div>
                                                    </div>
                                                    <div class="blog-excerpt">
                                                        <p>{{ Str::words($post->description, 35) }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
