<div
    class="order-detail-box"
    data-refresh-url="{{ route('public.account.coupon.refresh', $package->getKey()) }}"
>
    <div class="card mt-3 mb-0">
        <div class="card-body">
            <h5 class="card-title mb-4">{{ __('Your order') }}</h5>
            <div class="d-flex align-items-center mb-3">
                <div class="w-100">
                    <h6 class="text-muted fw-bold">{{ $package->name }}</h6>
                </div>

                <div class="text-end w-100 text-muted">{{ format_price($package->price) }}</div>
            </div>
            @if (session()->has('applied_coupon_code'))
                <div class="d-flex align-items-center mb-3">
                    <div class="w-100">
                        <h6 class="text-muted">{{ trans('plugins/real-estate::coupon.coupon_code') }}</h6>
                    </div>
                    <div class="text-end w-100 text-muted">{{ session()->get('applied_coupon_code') }}</div>
                </div>
                @if (session()->get('coupon_discount_amount') > 0)
                    <div class="d-flex align-items-center mb-3">
                        <div class="w-100">
                            <h6 class="text-muted">{{ trans('plugins/real-estate::coupon.discount_amount') }}</h6>
                        </div>
                        <div class="text-end w-100 text-muted">
                            {{ format_price(session()->get('coupon_discount_amount')) }}</div>
                    </div>
                @endif
            @endif
            <div class="d-flex align-items-center mb-3">
                <div class="w-100">
                    <h6>{{ trans('plugins/real-estate::coupon.total') }}</h6>
                </div>
                <div class="text-end w-100">{{ format_price($totalAmount) }}</div>
            </div>
        </div>
    </div>

    <button
        class="btn btn-link ps-0 text-decoration-none toggle-coupon-form"
        type="button"
    >{{ trans('plugins/real-estate::coupon.toggle_coupon_form_text') }}</button>

    <div
        class="card coupon-form mt-3"
        @style(['display: none' => !session()->has('applied_coupon_code')])
    >
        <div class="card-body">
            @if (session('applied_coupon_code'))
                <div class="d-flex align-items-center justify-content-between alert alert-success mb-0 w-100">
                    <span>{{ __('Coupon code: :code', ['code' => session('applied_coupon_code')]) }}</span>

                    <button
                        class="btn btn-link text-danger text-decoration-none remove-coupon-code"
                        data-url="{{ route('public.account.coupon.remove') }}"
                        type="button"
                    >
                        <i class="fa fa-trash"></i>
                        {{ __('Remove') }}
                    </button>
                </div>
            @else
                <div class="form-group">
                    <label
                        class="form-label"
                        for="coupon_code"
                    >{{ trans('plugins/real-estate::coupon.coupon_code') }}</label>
                    <div class="input-group">
                        <input
                            class="form-control"
                            id="coupon_code"
                            name="coupon_code"
                            type="text"
                            value="{{ BaseHelper::clean(old('coupon_code')) }}"
                            placeholder="{{ trans('plugins/real-estate::coupon.coupon_code_placeholder') }}"
                        >
                        <button
                            class="btn btn-primary apply-coupon-code"
                            data-url="{{ route('public.account.coupon.apply') }}"
                            type="button"
                        >
                            {{ trans('plugins/real-estate::coupon.apply_coupon_code') }}
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
