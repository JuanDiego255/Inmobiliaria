<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\Base\Facades\Assets;
use Botble\Base\Facades\EmailHandler;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Language\Facades\Language;
use Botble\Location\Models\City;
use Botble\Location\Models\Country;
use Botble\Location\Models\State;
use Botble\Media\Chunks\Exceptions\UploadMissingFileException;
use Botble\Media\Chunks\Handler\DropZoneUploadHandler;
use Botble\Media\Chunks\Receiver\FileReceiver;
use Botble\Media\Facades\RvMedia;
use Botble\Media\Models\MediaFile;
use Botble\Media\Services\ThumbnailService;
use Botble\Optimize\Facades\OptimizerHelper;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Models\Payment;
use Botble\PayPal\Services\Gateways\PayPalPaymentService;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Http\Requests\AvatarRequest;
use Botble\RealEstate\Http\Requests\SettingRequest;
use Botble\RealEstate\Http\Requests\UpdatePasswordRequest;
use Botble\RealEstate\Http\Resources\AccountResource;
use Botble\RealEstate\Http\Resources\ActivityLogResource;
use Botble\RealEstate\Http\Resources\PackageResource;
use Botble\RealEstate\Http\Resources\TransactionResource;
use Botble\RealEstate\Models\Account;
use Botble\RealEstate\Models\AccountActivityLog;
use Botble\RealEstate\Models\Package;
use Botble\RealEstate\Models\Transaction;
use Botble\RealEstate\Services\CouponService;
use Botble\SeoHelper\Facades\SeoHelper;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PublicAccountController extends Controller
{
    public function __construct()
    {
        OptimizerHelper::disable();
    }

    public function getDashboard()
    {
        $user = auth('account')->user();

        SeoHelper::setTitle(auth('account')->user()->name);

        Assets::addScriptsDirectly([
            'vendor/core/plugins/real-estate/js/components.js',
            'vendor/core/plugins/real-estate/libraries/cropper.js',
        ]);

        Assets::usingVueJS();

        return view('plugins/real-estate::account.dashboard.index', compact('user'));
    }

    public function getSettings()
    {
        SeoHelper::setTitle(trans('plugins/real-estate::account.account_settings'));

        $user = auth('account')->user();

        Assets::addScriptsDirectly([
            'vendor/core/plugins/real-estate/libraries/cropper.js',
            'vendor/core/plugins/location/js/location.js',
        ]);

        $countries = Country::query()->pluck('name', 'id')->all();
        $states = [];

        $countryId = $user->country_id ?: Arr::first(array_keys($countries));

        if ($countryId) {
            $states = State::query()->where('country_id', $countryId)->pluck('name', 'id')->all();
        }

        $cities = [];

        $stateId = $user->state_id ?: Arr::first(array_keys($states));

        if ($stateId) {
            $cities = City::query()->where('state_id', $stateId)->pluck('name', 'id')->all();
        }

        return view('plugins/real-estate::account.settings.index', compact('user', 'countries', 'states', 'cities'));
    }

    public function postSettings(SettingRequest $request, BaseHttpResponse $response)
    {
        $year = $request->input('year');
        $month = $request->input('month');
        $day = $request->input('day');

        if ($year && $month && $day) {
            $request->merge(['dob' => implode('-', [$year, $month, $day])]);

            $validator = Validator::make($request->input(), [
                'dob' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return redirect()->route('public.account.settings');
            }
        }

        $account = auth('account')->user();
        $account->fill($request->except('email'));
        $account->save();

        do_action('update_account_settings', $account);

        AccountActivityLog::query()->create(['action' => 'update_setting']);

        return $response
            ->setNextUrl(route('public.account.settings'))
            ->setMessage(trans('plugins/real-estate::account.update_profile_success'));
    }

    public function getSecurity()
    {
        SeoHelper::setTitle(trans('plugins/real-estate::account.security'));

        return view('plugins/real-estate::account.settings.security');
    }

    public function getPackages()
    {
        if (! RealEstateHelper::isEnabledCreditsSystem()) {
            abort(404);
        }

        SeoHelper::setTitle(trans('plugins/real-estate::account.packages'));

        Assets::addScriptsDirectly('vendor/core/plugins/real-estate/js/components.js');

        Assets::usingVueJS();

        return view('plugins/real-estate::account.settings.package');
    }

    public function getTransactions()
    {
        if (! RealEstateHelper::isEnabledCreditsSystem()) {
            abort(404);
        }

        SeoHelper::setTitle(trans('plugins/real-estate::account.transactions'));

        Assets::addScriptsDirectly('vendor/core/plugins/real-estate/js/components.js');

        Assets::usingVueJS();

        return view('plugins/real-estate::account.settings.transactions');
    }

    public function ajaxGetPackages(BaseHttpResponse $response)
    {
        if (! RealEstateHelper::isEnabledCreditsSystem()) {
            abort(404);
        }

        if (is_plugin_active('language')) {
            Language::setCurrentAdminLocale(Language::getCurrentLocaleCode());
        }

        $account = Account::query()->with(['packages'])->findOrFail(auth('account')->id());

        $packages = Package::query()
            ->wherePublished()
            ->get();

        $packages = $packages->filter(function ($package) use ($account) {
            return $package->account_limit === null || $account->packages->where(
                'id',
                $package->id
            )->count() < $package->account_limit;
        });

        return $response->setData([
            'packages' => PackageResource::collection($packages),
            'account' => new AccountResource($account),
        ]);
    }

    public function ajaxSubscribePackage(
        Request $request,
        BaseHttpResponse $response,
    ) {
        if (! RealEstateHelper::isEnabledCreditsSystem()) {
            abort(404);
        }

        $package = Package::query()->findOrFail($request->input('id'));

        $account = Account::query()->findOrFail(auth('account')->id());

        if ($package->account_limit && $account->packages()->where(
            'package_id',
            $package->id
        )->count() >= $package->account_limit) {
            abort(403);
        }

        if ((float)$package->price) {
            session(['subscribed_packaged_id' => $package->id]);

            return $response->setData(['next_page' => route('public.account.package.subscribe', $package->id)]);
        }

        $this->savePayment($package, null, true);

        return $response
            ->setData(new AccountResource($account->refresh()))
            ->setMessage(trans('plugins/real-estate::package.add_credit_success'));
    }

    protected function savePayment(Package $package, ?string $chargeId, bool $force = false)
    {
        if (! RealEstateHelper::isEnabledCreditsSystem()) {
            abort(404);
        }

        $payment = Payment::query()
            ->where('charge_id', $chargeId)
            ->first();

        if (! $payment && ! $force) {
            return false;
        }

        $account = auth('account')->user();

        if (($payment && $payment->status == PaymentStatusEnum::COMPLETED) || $force) {
            $account->credits += $package->number_of_listings;
            $account->save();

            $account->packages()->attach($package);
        }

        Transaction::query()->create([
            'user_id' => 0,
            'account_id' => auth('account')->id(),
            'credits' => $package->number_of_listings,
            'payment_id' => $payment ? $payment->id : null,
        ]);

        if (! $package->price) {
            EmailHandler::setModule(REAL_ESTATE_MODULE_SCREEN_NAME)
                ->setVariableValues([
                    'account_name' => $account->name,
                    'account_email' => $account->email,
                ])
                ->sendUsingTemplate('free-credit-claimed');
        } else {
            EmailHandler::setModule(REAL_ESTATE_MODULE_SCREEN_NAME)
                ->setVariableValues([
                    'account_name' => $account->name,
                    'account_email' => $account->email,
                    'package_name' => $package->name,
                    'package_price' => $package->price,
                    'package_percent_discount' => $package->percent_save,
                    'package_number_of_listings' => $package->number_of_listings,
                    'package_price_per_credit' => $package->price ? $package->price / ($package->number_of_listings ?: 1) : 0,
                ])
                ->sendUsingTemplate('payment-received');
        }

        EmailHandler::setModule(REAL_ESTATE_MODULE_SCREEN_NAME)
            ->setVariableValues([
                'account_name' => $account->name,
                'account_email' => $account->email,
                'package_name' => $package->name,
                'package_price' => $package->price ?: 0,
                'package_percent_discount' => $package->percent_save,
                'package_number_of_listings' => $package->number_of_listings,
                'package_price_per_credit' => $package->price ? $package->price / ($package->number_of_listings ?: 1) : 0,
            ])
            ->sendUsingTemplate('payment-receipt', auth('account')->user()->email);

        return true;
    }

    public function getSubscribePackage(int|string $id, CouponService $service)
    {
        if (! RealEstateHelper::isEnabledCreditsSystem()) {
            abort(404);
        }

        $package = Package::query()->findOrFail($id);

        Session::put('cart_total', $package->price);

        SeoHelper::setTitle(trans('plugins/real-estate::package.subscribe_package', ['name' => $package->name]));

        add_filter(PAYMENT_FILTER_AFTER_PAYMENT_METHOD, function () use ($service, $package) {
            $totalAmount = $service->getAmountAfterDiscount(
                Session::get('coupon_discount_amount', 0),
                $package->price
            );

            return view('plugins/real-estate::coupons.partials.form', compact('package', 'totalAmount'));
        });

        return view('plugins/real-estate::account.checkout', compact('package'));
    }

    public function getPackageSubscribeCallback(
        $packageId,
        Request $request,
        BaseHttpResponse $response
    ) {
        if (! RealEstateHelper::isEnabledCreditsSystem()) {
            abort(404);
        }

        $package = Package::query()->findOrFail($packageId);

        if (is_plugin_active('paypal') && $request->input('type') == PAYPAL_PAYMENT_METHOD_NAME) {
            $validator = Validator::make($request->input(), [
                'amount' => 'required|numeric',
                'currency' => 'required',
            ]);

            if ($validator->fails()) {
                return $response->setError()->setMessage($validator->getMessageBag()->first());
            }

            $payPalService = app(PayPalPaymentService::class);

            $paymentStatus = $payPalService->getPaymentStatus($request);

            if ($paymentStatus) {
                $chargeId = session('paypal_payment_id');

                $payPalService->afterMakePayment($request->input());

                $this->savePayment($package, $chargeId);

                return $response
                    ->setNextUrl(route('public.account.packages'))
                    ->setMessage(trans('plugins/real-estate::package.add_credit_success'));
            }

            return $response
                ->setError()
                ->setNextUrl(route('public.account.packages'))
                ->setMessage($payPalService->getErrorMessage());
        }

        $this->savePayment($package, $request->input('charge_id'));

        if (! $request->has('success') || $request->input('success')) {
            return $response
                ->setNextUrl(route('public.account.packages'))
                ->setMessage(session()->get('success_msg') ?: trans('plugins/real-estate::package.add_credit_success'));
        }

        return $response
            ->setError()
            ->setNextUrl(route('public.account.packages'))
            ->setMessage(__('Payment failed!'));
    }

    public function postSecurity(UpdatePasswordRequest $request, BaseHttpResponse $response)
    {
        $account = auth('account')->user();
        $account->password = Hash::make($request->input('password'));
        $account->save();

        AccountActivityLog::query()->create(['action' => 'update_security']);

        return $response->setMessage(trans('plugins/real-estate::dashboard.password_update_success'));
    }

    public function postAvatar(AvatarRequest $request, ThumbnailService $thumbnailService, BaseHttpResponse $response)
    {
        try {
            $account = auth('account')->user();

            $result = RvMedia::handleUpload($request->file('avatar_file'), 0, auth('account')->user()->upload_folder);

            if ($result['error']) {
                return $response->setError()->setMessage($result['message']);
            }

            $avatarData = json_decode($request->input('avatar_data'));

            $file = $result['data'];

            $thumbnailService
                ->setImage(RvMedia::getRealPath($file->url))
                ->setSize((int)$avatarData->width, (int)$avatarData->height)
                ->setCoordinates((int)$avatarData->x, (int)$avatarData->y)
                ->setDestinationPath(File::dirname($file->url))
                ->setFileName(File::name($file->url) . '.' . File::extension($file->url))
                ->save('crop');

            $avatar = MediaFile::query()->find($account->avatar_id);

            if ($avatar) {
                $avatar->forceDelete();
            }

            $account->avatar_id = $file->id;

            $account->save();

            AccountActivityLog::query()->create([
                'action' => 'changed_avatar',
            ]);

            return $response
                ->setMessage(trans('plugins/real-estate::dashboard.update_avatar_success'))
                ->setData(['url' => Storage::url($file->url)]);
        } catch (Exception $ex) {
            return $response
                ->setError()
                ->setMessage($ex->getMessage());
        }
    }

    public function getActivityLogs(BaseHttpResponse $response)
    {
        $activities = AccountActivityLog::query()
            ->where('account_id', auth('account')->id())
            ->latest()
            ->paginate();

        Assets::addScriptsDirectly('vendor/core/plugins/real-estate/js/components.js');

        Assets::usingVueJS();

        return $response->setData(ActivityLogResource::collection($activities))->toApiResponse();
    }

    public function postUpload(Request $request, BaseHttpResponse $response)
    {
        if (setting('media_chunk_enabled') != '1') {
            $validator = Validator::make($request->all(), [
                'file.0' => 'required|image|mimes:jpg,jpeg,png,webp',
            ]);

            if ($validator->fails()) {
                return $response->setError()->setMessage($validator->getMessageBag()->first());
            }

            $result = RvMedia::handleUpload(Arr::first($request->file('file')), 0, auth('account')->user()->upload_folder);

            if ($result['error']) {
                return $response->setError()->setMessage($result['message']);
            }

            return $response->setData($result['data']);
        }

        try {
            // Create the file receiver
            $receiver = new FileReceiver('file', $request, DropZoneUploadHandler::class);
            // Check if the upload is success, throw exception or return response you need
            if ($receiver->isUploaded() === false) {
                throw new UploadMissingFileException();
            }
            // Receive the file
            $save = $receiver->receive();
            // Check if the upload has finished (in chunk mode it will send smaller files)
            if ($save->isFinished()) {
                $result = RvMedia::handleUpload($save->getFile(), 0, auth('account')->user()->upload_folder);

                if (! $result['error']) {
                    return $response->setData($result['data']);
                }

                return $response->setError()->setMessage($result['message']);
            }
            // We are in chunk mode, lets send the current progress
            $handler = $save->handler();

            return response()->json([
                'done' => $handler->getPercentageDone(),
                'status' => true,
            ]);
        } catch (Exception $exception) {
            return $response->setError()->setMessage($exception->getMessage());
        }
    }

    public function postUploadFromEditor(Request $request)
    {
        return RvMedia::uploadFromEditor($request, 0, auth('account')->user()->upload_folder);
    }

    public function ajaxGetTransactions(BaseHttpResponse $response)
    {
        if (! RealEstateHelper::isEnabledCreditsSystem()) {
            abort(404);
        }

        $transactions = Transaction::query()
            ->where('account_id', auth('account')->id())
            ->latest()
            ->with(['payment', 'user'])
            ->paginate();

        return $response->setData(TransactionResource::collection($transactions))->toApiResponse();
    }
}
