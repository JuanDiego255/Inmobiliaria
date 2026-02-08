<li
    class="dropdown dropdown-extended dropdown-inbox"
    id="header_inbox_bar"
>
    <a
        class="dropdown-toggle dropdown-header-name"
        data-bs-toggle="dropdown"
        href="javascript:;"
        aria-haspopup="true"
        aria-expanded="false"
    >
        <i class="icon-envelope-open"></i>
        <span class="badge badge-default"> {{ $consults->total() }} </span>
    </a>
    <ul class="dropdown-menu dropdown-menu-right">
        <li class="external">
            <h3>{!! BaseHelper::clean(trans('plugins/real-estate::consult.new_consult_notice', ['count' => $consults->total()])) !!}</h3>
            <a href="{{ route('consult.index') }}">{{ trans('plugins/real-estate::consult.view_all') }}</a>
        </li>
        <li>
            <ul
                class="dropdown-menu-list scroller"
                data-handle-color="#637283"
                style="height: {{ $consults->total() * 70 }}px;"
            >
                @foreach ($consults as $consult)
                    <li>
                        <a href="{{ route('consult.edit', $consult->id) }}">
                            <span class="photo">
                                <img
                                    class="rounded-circle"
                                    src="{{ $consult->avatar_url }}"
                                    alt="{{ $consult->name }}"
                                >
                            </span>
                            <span class="subject"><span class="from"> {{ $consult->name }} </span><span
                                    class="time">{{ $consult->created_at->toDateTimeString() }} </span></span>
                            <span class="message"> {{ $consult->phone }} - {{ $consult->email }} </span>
                        </a>
                    </li>
                @endforeach

                @if ($consults->total() > 10)
                    <li class="text-center"><a
                            href="{{ route('consult.index') }}">{{ trans('plugins/consult::consult.view_all') }}</a>
                    </li>
                @endif
            </ul>
        </li>
    </ul>
</li>
