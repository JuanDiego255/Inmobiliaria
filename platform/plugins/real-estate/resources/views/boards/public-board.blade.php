<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $board->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .board-header {
            background: linear-gradient(135deg, #1a365d 0%, #2d5a87 100%);
            color: white;
            padding: 2.5rem 0;
        }
        .property-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .property-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        .property-card img {
            height: 220px;
            object-fit: cover;
        }
        .property-price {
            color: #2d5a87;
            font-weight: 700;
            font-size: 1.25rem;
        }
        .property-features span {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .share-bar {
            background: white;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
    </style>
</head>
<body>
    <div class="board-header">
        <div class="container">
            <h1 class="mb-2">{{ $board->name }}</h1>
            @if($board->description)
                <p class="mb-2 opacity-75">{{ $board->description }}</p>
            @endif
            @if($board->client)
                <p class="mb-0">
                    <i class="fas fa-user me-1"></i>
                    {{ trans('plugins/real-estate::board.prepared_for') }}: <strong>{{ $board->client->name }}</strong>
                </p>
            @endif
        </div>
    </div>

    <div class="container py-4">
        <div class="share-bar mb-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span class="text-muted">
                <i class="fas fa-clipboard-list me-1"></i>
                {{ $properties->count() }} {{ Str::plural('property', $properties->count()) }}
            </span>
            <div class="d-flex gap-2">
                <a href="{{ $board->whatsapp_share_url }}" target="_blank" class="btn btn-success btn-sm">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
                <a href="{{ $board->email_share_url }}" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-envelope"></i> Email
                </a>
            </div>
        </div>

        @if($properties->count())
            <div class="row g-4">
                @foreach($properties as $property)
                    <div class="col-md-6 col-lg-4">
                        <div class="card property-card h-100">
                            @if(!empty($property->formatted_images) && count($property->formatted_images))
                                <img src="{{ $property->formatted_images[0] }}" class="card-img-top" alt="{{ $property->name }}">
                            @else
                                <img src="{{ RvMedia::getDefaultImage() }}" class="card-img-top" alt="{{ $property->name }}">
                            @endif
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">{{ $property->name }}</h5>

                                @if($property->location)
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-map-marker-alt me-1"></i> {{ $property->location }}
                                    </p>
                                @endif

                                <div class="property-price mb-2">
                                    {{ $property->price_format }}
                                    @if($property->type->value === 'rent' && $property->period)
                                        <small class="text-muted">/ {{ $property->period->label() }}</small>
                                    @endif
                                </div>

                                <div class="property-features d-flex flex-wrap gap-3 mb-3">
                                    @if($property->number_bedroom)
                                        <span><i class="fas fa-bed me-1"></i> {{ $property->number_bedroom }} {{ trans('plugins/real-estate::board.bedrooms') }}</span>
                                    @endif
                                    @if($property->number_bathroom)
                                        <span><i class="fas fa-bath me-1"></i> {{ $property->number_bathroom }} {{ trans('plugins/real-estate::board.bathrooms') }}</span>
                                    @endif
                                    @if($property->square)
                                        <span><i class="fas fa-ruler-combined me-1"></i> {{ $property->square_text }}</span>
                                    @endif
                                </div>

                                @if($property->description)
                                    <p class="card-text text-muted small flex-grow-1">{{ Str::limit($property->description, 120) }}</p>
                                @endif

                                @if($property->url)
                                    <a href="{{ $property->url }}" target="_blank" class="btn btn-outline-primary btn-sm mt-auto">
                                        <i class="fas fa-eye me-1"></i> {{ trans('plugins/real-estate::board.view_property') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-home fa-3x text-muted mb-3"></i>
                <p class="text-muted">{{ trans('plugins/real-estate::board.no_properties') }}</p>
            </div>
        @endif
    </div>

    <footer class="text-center py-4 text-muted">
        <small>&copy; {{ date('Y') }} {{ theme_option('site_title', config('app.name')) }}</small>
    </footer>
</body>
</html>
