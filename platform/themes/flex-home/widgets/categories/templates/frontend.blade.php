@if (is_plugin_active('blog') && count($categories))
    <div class="boxright">
        <h5>{{ $config['name'] }}</h5>
        <ul class="listnews">
            @foreach($categories as $category)
                <li><a href="{{ $category->url }}" class="text-dark">{{ $category->name }}</a></li>
            @endforeach
        </ul>
    </div>
@endif
