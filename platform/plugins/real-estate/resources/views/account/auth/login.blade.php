@extends('plugins/real-estate::account.layouts.skeleton')
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card login-form">
                    <div class="card-body">
                        <h4 class="text-center">{{ trans('plugins/real-estate::dashboard.login-title') }}</h4>
                        <br>
                        <form
                            method="POST"
                            action="{{ route('public.account.login') }}"
                        >
                            @csrf
                            <div class="form-group mb-3">
                                <input
                                    class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}"
                                    id="email"
                                    name="email"
                                    type="text"
                                    value="{{ old('email') }}"
                                    placeholder="{{ trans('plugins/real-estate::dashboard.email_or_username') }}"
                                    autofocus
                                >
                                @if ($errors->has('email'))
                                    <span class="invalid-feedback">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group mb-3">
                                <input
                                    class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}"
                                    id="password"
                                    name="password"
                                    type="password"
                                    placeholder="{{ trans('plugins/real-estate::dashboard.password') }}"
                                >
                                @if ($errors->has('password'))
                                    <span class="invalid-feedback">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group mb-3">
                                <div class="checkbox">
                                    <label>
                                        <input
                                            name="remember"
                                            type="checkbox"
                                            {{ old('remember') ? 'checked' : '' }}
                                        > {{ trans('plugins/real-estate::dashboard.remember-me') }}
                                    </label>
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                <button
                                    class="btn btn-blue btn-full fw6"
                                    type="submit"
                                >
                                    {{ trans('plugins/real-estate::dashboard.login-cta') }}
                                </button>
                                <div class="text-center">
                                    <a
                                        class="btn btn-link"
                                        href="{{ route('public.account.password.request') }}"
                                    >
                                        {{ trans('plugins/real-estate::dashboard.forgot-password-cta') }}
                                    </a>
                                </div>
                            </div>

                            <div class="text-center">
                                {!! apply_filters(BASE_FILTER_AFTER_LOGIN_OR_REGISTER_FORM, null, \Botble\RealEstate\Models\Account::class) !!}
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script
        type="text/javascript"
        src="{{ asset('vendor/core/core/js-validation/js/js-validation.js') }}"
    ></script>
    {!! JsValidator::formRequest(\Botble\RealEstate\Http\Requests\LoginRequest::class) !!}
@endpush
