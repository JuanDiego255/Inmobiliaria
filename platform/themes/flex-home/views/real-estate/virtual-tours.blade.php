<link href="https://fonts.googleapis.com/css?family=Fahkwang:400,500,600,700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">

<style>
    .vt-page{background:#0e0b08;min-height:100vh;padding:60px 24px 80px;font-family:'Open Sans',sans-serif}
    .vt-container{max-width:1200px;margin:0 auto}
    .vt-header{text-align:center;margin-bottom:40px}
    .vt-header h1{font-family:'Fahkwang',sans-serif;text-transform:uppercase;letter-spacing:2.5px;color:#fff;font-size:32px;font-weight:600;margin-bottom:8px}
    .vt-header p{color:rgba(255,255,255,.6);font-size:15px;margin:0}
    .vt-filter{display:flex;justify-content:center;margin-bottom:36px}
    .vt-filter form{position:relative;width:100%;max-width:480px}
    .vt-filter input{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.13);color:#fff;border-radius:8px;padding:14px 48px 14px 18px;width:100%;font-size:14px;font-family:'Open Sans',sans-serif;transition:border-color .2s}
    .vt-filter input::placeholder{color:rgba(255,255,255,.4)}
    .vt-filter input:focus{outline:none;border-color:rgba(233,30,99,.5);box-shadow:0 0 0 3px rgba(233,30,99,.15)}
    .vt-filter button{position:absolute;right:6px;top:50%;transform:translateY(-50%);background:#e91e63;border:none;color:#fff;border-radius:6px;width:36px;height:36px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background .2s}
    .vt-filter button:hover{background:#c2185b}
    .vt-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:22px}
    .vt-card{background:rgba(14,11,8,.72);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:2px solid transparent;border-radius:10px;overflow:hidden;transition:transform .25s cubic-bezier(.4,0,.2,1),border-color .25s}
    .vt-card:hover{transform:scale(1.03);border-color:rgba(255,255,255,.5)}
    .vt-card-media{position:relative;aspect-ratio:16/10;overflow:hidden}
    .vt-card-media img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .4s}
    .vt-card:hover .vt-card-media img{transform:scale(1.08)}
    .vt-card-media .vt-title{position:absolute;bottom:0;left:0;right:0;padding:28px 16px 14px;background:linear-gradient(to top,rgba(0,0,0,.85),transparent);color:#fff;font-family:'Fahkwang',sans-serif;font-size:14px;letter-spacing:1px;font-weight:500}
    .vt-badge{position:absolute;top:12px;left:12px;background:#e91e63;box-shadow:0 0 14px rgba(233,30,99,.5);color:#fff;font-size:10px;font-weight:600;padding:5px 12px;border-radius:20px;text-transform:uppercase;letter-spacing:1px;font-family:'Fahkwang',sans-serif}
    .vt-card-body{padding:16px}
    .vt-card-location{color:rgba(255,255,255,.6);font-size:13px;margin-bottom:14px;display:flex;align-items:center;gap:6px}
    .vt-card-location .material-icons{font-size:16px;color:#e91e63}
    .vt-card-meta{display:flex;gap:16px;margin-bottom:14px}
    .vt-card-meta span{color:rgba(255,255,255,.5);font-size:12px;display:flex;align-items:center;gap:4px}
    .vt-card-meta .material-icons{font-size:14px}
    .vt-btn{display:inline-flex;align-items:center;gap:8px;background:rgba(8,8,12,.72);border:1px solid rgba(255,255,255,.13);color:rgba(255,255,255,.9);border-radius:8px;padding:10px 18px;font-size:13px;text-decoration:none;transition:all .2s;font-family:'Open Sans',sans-serif;cursor:pointer}
    .vt-btn:hover{background:#e91e63;color:#fff;border-color:#e91e63;text-decoration:none}
    .vt-btn .material-icons{font-size:16px}
    .vt-empty{text-align:center;padding:80px 20px;color:rgba(255,255,255,.5)}
    .vt-empty .material-icons{font-size:64px;color:rgba(255,255,255,.15);display:block;margin-bottom:16px}
    .vt-empty h3{font-family:'Fahkwang',sans-serif;color:rgba(255,255,255,.7);font-size:18px;margin-bottom:8px}
    .vt-pagination{display:flex;justify-content:center;gap:8px;margin-top:40px}
    .vt-pagination a,.vt-pagination span{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:8px;font-size:14px;text-decoration:none;transition:all .2s}
    .vt-pagination a{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.13);color:rgba(255,255,255,.7)}
    .vt-pagination a:hover{background:#e91e63;border-color:#e91e63;color:#fff}
    .vt-pagination .vt-pg-active{background:#e91e63;border:1px solid #e91e63;color:#fff}
    .vt-not-configured{text-align:center;padding:100px 20px;color:rgba(255,255,255,.6)}
    .vt-not-configured .material-icons{font-size:72px;color:rgba(255,255,255,.12);display:block;margin-bottom:16px}
    @media(max-width:640px){
        .vt-page{padding:40px 16px 60px}
        .vt-header h1{font-size:22px}
        .vt-grid{grid-template-columns:1fr;gap:16px}
    }
</style>

<div class="vt-page">
    <div class="vt-container">
        <div class="vt-header">
            <h1>Tours Virtuales 360&deg;</h1>
            <p>Explorá nuestras propiedades con recorridos inmersivos</p>
        </div>

        <div class="vt-filter">
            <form method="GET" action="{{ route('public.virtual-tours') }}">
                <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Buscar tour por nombre o ubicación...">
                <button type="submit"><span class="material-icons">search</span></button>
            </form>
        </div>

        @if(count($tours))
            <div class="vt-grid">
                @foreach($tours as $tour)
                    <div class="vt-card">
                        <div class="vt-card-media">
                            <img src="{{ $tour['main_image'] ?? asset('vendor/core/plugins/real-estate/images/no-image.png') }}"
                                 alt="{{ $tour['name'] ?? 'Tour Virtual' }}"
                                 loading="lazy">
                            <span class="vt-badge"><span class="material-icons" style="font-size:11px;vertical-align:middle;margin-right:2px">360</span> Tour 360</span>
                            <div class="vt-title">{{ $tour['name'] ?? '' }}</div>
                        </div>
                        <div class="vt-card-body">
                            @if(!empty($tour['location']))
                                <div class="vt-card-location">
                                    <span class="material-icons">place</span>
                                    {{ $tour['location'] }}
                                </div>
                            @endif
                            <div class="vt-card-meta">
                                @if(!empty($tour['type_name']))
                                    <span><span class="material-icons">home</span> {{ $tour['type_name'] }}</span>
                                @endif
                                @if(!empty($tour['scenes_count']))
                                    <span><span class="material-icons">panorama_photosphere</span> {{ $tour['scenes_count'] }} escenas</span>
                                @endif
                                @if(!empty($tour['formatted_price']))
                                    <span><span class="material-icons">sell</span> {{ $tour['formatted_price'] }}</span>
                                @endif
                            </div>
                            <a href="{{ $tour['tour_url'] ?? '#' }}" target="_blank" rel="noopener" class="vt-btn">
                                <span class="material-icons">play_arrow</span> Ver Tour 360
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            @if(!empty($meta['last_page']) && $meta['last_page'] > 1)
                <div class="vt-pagination">
                    @for($i = 1; $i <= $meta['last_page']; $i++)
                        @if($i == ($meta['current_page'] ?? 1))
                            <span class="vt-pg-active">{{ $i }}</span>
                        @else
                            <a href="{{ route('public.virtual-tours', array_merge(request()->query(), ['page' => $i])) }}">{{ $i }}</a>
                        @endif
                    @endfor
                </div>
            @endif
        @else
            <div class="vt-empty">
                <span class="material-icons">panorama_photosphere</span>
                <h3>{{ !empty($q) ? 'No se encontraron tours' : 'Tours próximamente' }}</h3>
                <p>{{ !empty($q) ? 'Intentá con otros términos de búsqueda.' : 'Pronto tendremos recorridos virtuales disponibles.' }}</p>
            </div>
        @endif
    </div>
</div>
