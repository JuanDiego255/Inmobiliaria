@php
    use Botble\Base\Enums\SystemUpdaterStepEnum;
@endphp

@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="container">
        <div class="col-md-8 offset-md-2">
            <h2 class="text-center mb-4">{{ trans('core/base::system.updater') }}</h2>

            <div
                class="updater-box mb-5"
                dir="ltr"
            >
                <div class="note note-warning">
                    <p>SU SISTEMA ESTA ACTUALIZADO.</p>
                    
                </div>

                @if (empty($latestUpdate))

                    @if (request()->query('no-ajax'))
                        @if ($isOutdated)
                            <p class="mb-2 text-success">
                                {{ __('A new version (:version / released on :date) is available to update!', ['version' => $latestUpdate->version, 'date' => BaseHelper::formatDate($latestUpdate->releasedDate)]) }}
                            </p>

                            <div class="note note-info changelog-info">
                                {!! $latestUpdate->changelog !!}
                            </div>
                        @else
                            <p class="mb-2 text-success">
                                {{ __('The system is up-to-date. There are no new versions to update!') }}</p>
                        @endif

                        <form
                            action="{{ route('system.updater') }}?no-ajax=1&update_id={{ $latestUpdate->updateId }}&version={{ $latestUpdate->version }}"
                            method="POST"
                        >
                            @csrf
                            <ol class="list-group list-group-numbered">
                                @foreach (SystemUpdaterStepEnum::labels() as $step => $label)
                                    @break($step === SystemUpdaterStepEnum::lastStep())

                                    <li class="list-group-item mb-2">
                                        <button
                                            class="btn btn-warning btn-update-new-version"
                                            name="step_name"
                                            data-updating-text="Updating..."
                                            type="submit"
                                            value="{{ $step }}"
                                        ><span>{{ $label }}</span></button>
                                    </li>
                                @endforeach
                            </ol>
                        </form>
                    @else
                        <system-update-component
                            update-url="{{ route('system.updater.post') }}"
                            update-id="{{ $latestUpdate->updateId }}"
                            version="{{ $latestUpdate->version }}"
                            first-step="{{ SystemUpdaterStepEnum::firstStep() }}"
                            first-step-message="{{ SystemUpdaterStepEnum::DOWNLOAD()->message() }}"
                            last-step="{{ SystemUpdaterStepEnum::lastStep() }}"
                            :is-outdated="{{ json_encode($isOutdated) }}"
                        >
                            @if ($isOutdated)
                                <p class="mb-2 text-success">
                                    {{ __('A new version (:version / released on :date) is available to update!', ['version' => $latestUpdate->version, 'date' => BaseHelper::formatDate($latestUpdate->releasedDate)]) }}
                                </p>

                                <div class="note note-info changelog-info">
                                    {!! $latestUpdate->changelog !!}
                                </div>
                            @else
                                <p class="mb-2 text-success">
                                    {{ __('The system is up-to-date. There are no new versions to update!') }}</p>
                            @endif
                        </system-update-component>
                    @endif
                @else
                    <p class="mb-0 text-success">{{ __('The system is up-to-date. There are no new versions to update!') }}
                    </p>
                @endif
            </div>

            

            @if (isset($isOutdated) && isset($latestUpdate) && !$isOutdated && $latestUpdate)
                <div
                    class="updater-box bg-transparent shadow-none border-0 p-0"
                    dir="ltr"
                >
                    <div class="mb-2 bold">Latest changelog: released on {{ BaseHelper::formatDate($latestUpdate->releasedDate) }}
                    </div>
                    <pre>{!! trim(
                        str_replace(
                            PHP_EOL . PHP_EOL,
                            PHP_EOL,
                            strip_tags(
                                str_replace(
                                    ['<li>', '</li>', '<ul>'],
                                    ['<li>- ', '</li>' . PHP_EOL, PHP_EOL . '<ul>'],
                                    $latestUpdate->changelog,
                                ),
                            ),
                        ),
                    ) !!}</pre>
                </div>
            @endif
        </div>
    </div>
@stop
