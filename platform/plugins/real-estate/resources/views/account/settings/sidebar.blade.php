<div class="col-12 col-md-3">
    <div
        class="list-group mb-3 br2"
        style="box-shadow: rgb(204, 204, 204) 0px 1px 1px;"
    >
        <div class="list-group-item fw6 bn light-gray-text">
            {{ trans('plugins/real-estate::dashboard.sidebar_title') }}
        </div>
        <a
            class="list-group-item list-group-item-action bn @if (Route::currentRouteName() == 'public.account.settings') active @endif"
            href="{{ route('public.account.settings') }}"
        >
            <i class="fas fa-user-circle mr-2"></i>
            <span>{{ trans('plugins/real-estate::dashboard.sidebar_information') }}</span>
        </a>
        <a
            class="list-group-item list-group-item-action bn @if (Route::currentRouteName() == 'public.account.security') active @endif"
            href="{{ route('public.account.security') }}"
        >
            <i class="fas fa-user-lock mr-2"></i>
            <span>{{ trans('plugins/real-estate::dashboard.sidebar_security') }}</span>
        </a>
        <a
            class="list-group-item list-group-item-action bn @if (Route::currentRouteName() == 'public.account.invoices') active @endif"
            href="{{ route('public.account.invoices.index') }}"
        >
            <i class="fas fa-file-invoice mr-2"></i>
            <span>{{ trans('plugins/real-estate::dashboard.sidebar_invoices') }}</span>
        </a>
    </div>
</div>
