@if($properties->count())
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>{{ trans('core/base::tables.id') }}</th>
                    <th>{{ trans('plugins/real-estate::property.form.images') }}</th>
                    <th>{{ trans('core/base::tables.name') }}</th>
                    <th>{{ trans('plugins/real-estate::property.form.price') }}</th>
                    <th>{{ trans('core/base::tables.status') }}</th>
                    <th>{{ trans('core/base::tables.operations') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($properties as $property)
                    <tr>
                        <td>{{ $property->id }}</td>
                        <td>
                            @if($property->image)
                                <img src="{{ RvMedia::getImageUrl($property->image, 'thumb') }}" alt="{{ $property->name }}" width="50" height="50" style="object-fit: cover; border-radius: 4px;">
                            @else
                                <img src="{{ RvMedia::getDefaultImage() }}" alt="" width="50" height="50" style="object-fit: cover; border-radius: 4px;">
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('property.edit', $property->id) }}" target="_blank">
                                {{ $property->name }}
                            </a>
                        </td>
                        <td>{{ $property->price_format }}</td>
                        <td>{!! $property->status->toHtml() !!}</td>
                        <td>
                            <button
                                class="btn btn-danger btn-sm btn-remove-property-from-board"
                                data-url="{{ route('board.remove-property', ['id' => $board->id, 'propertyId' => $property->id]) }}"
                                data-section-processing=".board-properties-section"
                            >
                                <i class="fas fa-trash"></i> {{ trans('plugins/real-estate::board.remove_property') }}
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="text-muted text-center py-3">{{ trans('plugins/real-estate::board.no_properties') }}</p>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.btn-remove-property-from-board').forEach(function (button) {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                if (!confirm('Are you sure?')) return;

                var url = this.getAttribute('data-url');
                fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                }).then(function (response) {
                    return response.json();
                }).then(function (data) {
                    if (!data.error) {
                        window.location.reload();
                    }
                });
            });
        });
    });
</script>
