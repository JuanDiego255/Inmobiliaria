@if($menuContent = Menu::generateMenu(['slug' => $config['menu_id']]))
    <div class="col-sm-4">
        <div class="menufooter">
            @if ($config['name'])
                <h4>{{ $config['name'] }}</h4>
            @endif
            {!! $menuContent !!}
        </div>
    </div>
@endif
