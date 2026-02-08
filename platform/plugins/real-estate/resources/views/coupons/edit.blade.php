@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="max-width-1200">
        <form
            action="{{ route('coupons.edit', $coupon) }}"
            method="post"
        >
            @csrf

            <v-coupon-form :coupon="{{ json_encode($coupon) }}"></v-coupon-form>
        </form>
    </div>

    @include('plugins/real-estate::coupons.partials.scripts', compact('jsValidator'))
@endsection
