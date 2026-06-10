@php
    $avatarColors = ['#717fe0', '#3aa76d', '#e08a3e', '#d35d6e', '#5b69c9', '#66a8a6', '#c08a3e', '#8b5cf6'];
@endphp
<div class="block__content">
    @foreach($reviews as $index => $review)
        <div class="pd-rev-item block--review">
            <div class="pd-rev-ava" style="background:{{ $avatarColors[$index % count($avatarColors)] }}">
                @if($review->author && $review->author->avatar_url)
                    <img src="{{ RvMedia::getImageUrl($review->author->avatar_url, 'thumb') }}" alt="{{ $review->author->name ?? '' }}">
                @else
                    {{ $review->author ? collect(explode(' ', $review->author->name))->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode('') : '?' }}
                @endif
            </div>
            <div class="pd-rev-body">
                <div class="pd-rev-top">
                    <span class="pd-rev-name">{{ $review->author->name ?? __('Anónimo') }}</span>
                    <span class="pd-rev-date">{{ $review->created_at?->translatedFormat('M, Y') }}</span>
                </div>
                <div class="pd-stars">
                    @for($i = 1; $i <= 5; $i++)
                        <span class="material-icons">{{ $i <= $review->star ? 'star' : 'star_border' }}</span>
                    @endfor
                </div>
                <p>{{ $review->content }}</p>
            </div>
        </div>
    @endforeach
</div>

{{ $reviews->onEachSide(1)->links() }}
