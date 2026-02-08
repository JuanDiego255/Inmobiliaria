<ul>
    @foreach($items as $item)
        <li>
            <p><a href="{{ $item->url }}">{{ $item->name }}</a></p>
            <p>{{ implode(', ', array_filter([$item->city->name, $item->state->name])) }}</p>
        </li>
    @endforeach
</ul>
