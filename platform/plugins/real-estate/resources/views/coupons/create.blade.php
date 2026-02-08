@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="max-width-1200">
        <form
            action="{{ route('coupons.create') }}"
            method="post"
        >
            @csrf

            <v-coupon-form></v-coupon-form>
        </form>
    </div>

    @include('plugins/real-estate::coupons.partials.scripts', compact('jsValidator'))
@endsection
