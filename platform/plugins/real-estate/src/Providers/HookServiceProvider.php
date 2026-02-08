<?php

namespace Botble\RealEstate\Providers;

use Botble\Base\Facades\Form;
use Botble\Base\Facades\Html;
use Botble\Base\Facades\MetaBox;
use Botble\Base\Forms\FormAbstract;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\TwigCompiler;
use Botble\Dashboard\Supports\DashboardWidgetInstance;
use Botble\Language\Facades\Language;
use Botble\LanguageAdvanced\Supports\LanguageAdvancedManager;
use Botble\Location\Models\City;
use Botble\Menu\Facades\Menu;
use Botble\Page\Models\Page;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Models\Payment;
use Botble\Payment\Supports\PaymentHelper;
use Botble\RealEstate\Enums\ConsultStatusEnum;
use Botble\RealEstate\Enums\InvoiceStatusEnum;
use Botble\RealEstate\Enums\ModerationStatusEnum;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Models\Account;
use Botble\RealEstate\Models\Category;
use Botble\RealEstate\Models\Consult;
use Botble\RealEstate\Models\Invoice;
use Botble\RealEstate\Models\Package;
use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Services\CouponService;
use Botble\RealEstate\Supports\InvoiceHelper;
use Botble\RealEstate\Supports\TwigExtension;
use Botble\RealEstate\Tables\PropertyTable;
use Botble\Theme\Facades\Theme;
use Botble\Theme\Supports\ThemeSupport;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->booted(function () {
            add_filter(BASE_FILTER_TOP_HEADER_LAYOUT, [$this, 'registerTopHeaderNotification'], 130);
            add_filter(BASE_FILTER_APPEND_MENU_NAME, [$this, 'getUnReadCount'], 130, 2);
            add_filter(BASE_FILTER_MENU_ITEMS_COUNT, [$this, 'getMenuItemCount'], 130);

            add_filter('cms_twig_compiler', function (TwigCompiler $twigCompiler) {
                if (! array_key_exists(TwigExtension::class, $twigCompiler->getExtensions())) {
                    $twigCompiler->addExtension(new TwigExtension());
                }

                return $twigCompiler;
            }, 130);

            if (defined('MENU_ACTION_SIDEBAR_OPTIONS')) {
                Menu::addMenuOptionModel(Category::class);
                add_action(MENU_ACTION_SIDEBAR_OPTIONS, [$this, 'registerMenuOptions'], 13);
            }

            if (defined('PAYMENT_FILTER_PAYMENT_PARAMETERS')) {
                add_filter(PAYMENT_FILTER_PAYMENT_PARAMETERS, function ($html) {
                    if (! auth('account')->check()) {
                        return $html;
                    }

                    return $html . Form::hidden('customer_id', auth('account')->id())->toHtml() .
                        Form::hidden('customer_type', Account::class)->toHtml();
                }, 123);
            }

            if (defined('PAYMENT_ACTION_PAYMENT_PROCESSED')) {
                add_action(PAYMENT_ACTION_PAYMENT_PROCESSED, function ($data) {
                    $payment = PaymentHelper::storeLocalPayment($data);

                    InvoiceHelper::store(array_merge($data, [
                        'discount_amount' => Session::get('coupon_discount_amount'),
                        'coupon_code' => Session::get('applied_coupon_code'),
                    ]));

                    if ($payment instanceof Model) {
                        MetaBox::saveMetaBoxData($payment, 'subscribed_packaged_id', session('subscribed_packaged_id'));
                    }

                    $this->app->make(CouponService::class)->forgotCouponSession();
                }, 123);

                add_action(BASE_ACTION_META_BOXES, function ($context, $payment) {
                    if (get_class($payment) == Payment::class && $context == 'advanced' && Route::currentRouteName() == 'payments.show') {
                        MetaBox::addMetaBox('additional_payment_data', __('Package information'), function () use ($payment) {
                            $subscribedPackageId = MetaBox::getMetaData($payment, 'subscribed_packaged_id', true);

                            $package = Package::query()->find($subscribedPackageId);

                            if (! $package) {
                                return null;
                            }

                            return view('plugins/real-estate::partials.payment-extras', compact('package'));
                        }, get_class($payment), $context);
                    }
                }, 128, 2);
            }

            if (defined('PAYMENT_FILTER_REDIRECT_URL')) {
                add_filter(PAYMENT_FILTER_REDIRECT_URL, function ($checkoutToken) {
                    $checkoutToken = $checkoutToken ?: session('subscribed_packaged_id');

                    if (! $checkoutToken) {
                        return route('public.index');
                    }

                    if (str_contains($checkoutToken, url(''))) {
                        return $checkoutToken;
                    }

                    return route('public.account.package.subscribe.callback', $checkoutToken);
                }, 123);
            }

            if (defined('PAYMENT_FILTER_CANCEL_URL')) {
                add_filter(PAYMENT_FILTER_CANCEL_URL, function ($checkoutToken) {
                    $checkoutToken = $checkoutToken ?: session('subscribed_packaged_id');

                    if (! $checkoutToken) {
                        return route('public.index');
                    }

                    if (str_contains($checkoutToken, url(''))) {
                        return $checkoutToken;
                    }

                    return route('public.account.package.subscribe', $checkoutToken) . '?' . http_build_query(['error' => true, 'error_type' => 'payment']);
                }, 123);
            }

            if (defined('ACTION_AFTER_UPDATE_PAYMENT')) {
                add_action(ACTION_AFTER_UPDATE_PAYMENT, function ($request, $payment) {
                    if (in_array($payment->payment_channel, [PaymentMethodEnum::COD, PaymentMethodEnum::BANK_TRANSFER])
                        && $request->input('status') == PaymentStatusEnum::COMPLETED
                    ) {
                        $subscribedPackageId = MetaBox::getMetaData($payment, 'subscribed_packaged_id', true);

                        if (! $subscribedPackageId) {
                            return;
                        }

                        $package = Package::query()->find($subscribedPackageId);

                        if (! $package) {
                            return;
                        }

                        $account = Account::query()->find($payment->customer_id);

                        if (! $account) {
                            return;
                        }

                        if ($payment->status == PaymentStatusEnum::PENDING) {
                            $account->credits += $package->number_of_listings;
                            $account->save();

                            $account->packages()->attach($package);
                        }

                        $payment->status = PaymentStatusEnum::COMPLETED;

                        Invoice::query()
                            ->where('reference_id', $package->getKey())
                            ->where('reference_type', Package::class)
                            ->update(['status' => InvoiceStatusEnum::COMPLETED]);
                    }
                }, 123, 2);
            }

            if (defined('PAYMENT_FILTER_PAYMENT_DATA')) {
                add_filter(PAYMENT_FILTER_PAYMENT_DATA, function (array $data, Request $request) {
                    $orderIds = [session('subscribed_packaged_id')];

                    $package = Package::query()->whereIn('id', $orderIds)->first();

                    if (! $package) {
                        return $data;
                    }

                    $discountAmount = 0;

                    $couponService = $this->app->make(CouponService::class);

                    if (Session::has('applied_coupon_code')) {
                        $coupon = $couponService->getCouponByCode(Session::get('applied_coupon_code'));

                        if ($coupon) {
                            $discountAmount = $couponService->getDiscountAmount(
                                $coupon->type->getValue(),
                                $coupon->value,
                                $package->price
                            );

                            $coupon->increment('total_used');
                        }
                    }

                    $price = $couponService->getAmountAfterDiscount($discountAmount, $package->price);

                    $products = [
                        [
                            'id' => $package->id,
                            'name' => $package->name,
                            'price' => $this->convertOrderAmount($package->price - $discountAmount),
                            'price_per_order' => $this->convertOrderAmount($package->price - $discountAmount),
                            'qty' => 1,
                        ],
                    ];

                    $account = auth('account')->user();

                    $address = [
                        'name' => $account->name,
                        'email' => $account->email,
                        'phone' => $account->phone,
                        'country' => null,
                        'state' => null,
                        'city' => null,
                        'address' => null,
                        'zip' => null,
                    ];

                    return [
                        'amount' => $this->convertOrderAmount($price),
                        'shipping_amount' => 0,
                        'shipping_method' => null,
                        'tax_amount' => 0,
                        'currency' => strtoupper(get_application_currency()->title),
                        'order_id' => $orderIds,
                        'description' => trans('plugins/payment::payment.payment_description', ['order_id' => Arr::first($orderIds), 'site_url' => request()->getHost()]),
                        'customer_id' => $account->getKey(),
                        'customer_type' => Account::class,
                        'return_url' => $request->input('return_url'),
                        'callback_url' => $request->input('callback_url'),
                        'products' => $products,
                        'orders' => [$package],
                        'address' => $address,
                        'checkout_token' => session('subscribed_packaged_id'),
                    ];
                }, 120, 2);
            }

            add_filter(DASHBOARD_FILTER_ADMIN_LIST, function ($widgets) {
                foreach ($widgets as $key => $widget) {
                    if (in_array($key, [
                            'widget_total_themes',
                            'widget_total_users',
                            'widget_total_plugins',
                            'widget_total_pages',
                        ]) && $widget['type'] == 'stats') {
                        Arr::forget($widgets, $key);
                    }
                }

                return $widgets;
            }, 150);

            add_filter(DASHBOARD_FILTER_ADMIN_LIST, function ($widgets, $widgetSettings) {
                $items = Property::query()
                    ->active()
                    ->count();

                return (new DashboardWidgetInstance())
                    ->setType('stats')
                    ->setPermission('property.index')
                    ->setTitle(trans('plugins/real-estate::property.active_properties'))
                    ->setKey('widget_total_1')
                    ->setIcon('fas fa-briefcase')
                    ->setColor('#8e44ad')
                    ->setStatsTotal($items)
                    ->setRoute(route('property.index', [
                        'filter_table_id' => strtolower(Str::slug(Str::snake(PropertyTable::class))),
                        'class' => PropertyTable::class,
                        'filter_columns' => [
                            'status',
                        ],
                        'filter_operators' => [
                            '=',
                        ],
                        'filter_values' => [
                            'active',
                        ],
                    ]))
                    ->init($widgets, $widgetSettings);
            }, 2, 2);

            add_filter(DASHBOARD_FILTER_ADMIN_LIST, function ($widgets, $widgetSettings) {
                $items = Property::query()
                    ->notExpired()
                    ->where('moderation_status', ModerationStatusEnum::PENDING)
                    ->count();

                return (new DashboardWidgetInstance())
                    ->setType('stats')
                    ->setPermission('property.index')
                    ->setTitle(trans('plugins/real-estate::property.pending_properties'))
                    ->setKey('widget_total_2')
                    ->setIcon('fas fa-briefcase')
                    ->setColor('#32c5d2')
                    ->setStatsTotal($items)
                    ->setRoute(route('property.index', [
                        'filter_table_id' => strtolower(Str::slug(Str::snake(PropertyTable::class))),
                        'class' => PropertyTable::class,
                        'filter_columns' => [
                            'moderation_status',
                        ],
                        'filter_operators' => [
                            '=',
                        ],
                        'filter_values' => [
                            ModerationStatusEnum::PENDING,
                        ],
                    ]))
                    ->init($widgets, $widgetSettings);
            }, 3, 2);

            add_filter(DASHBOARD_FILTER_ADMIN_LIST, function ($widgets, $widgetSettings) {
                $items = Property::query()
                    ->expired()
                    ->count();

                return (new DashboardWidgetInstance())
                    ->setType('stats')
                    ->setPermission('property.index')
                    ->setTitle(trans('plugins/real-estate::property.expired_properties'))
                    ->setKey('widget_total_3')
                    ->setIcon('fas fa-briefcase')
                    ->setColor('#e7505a')
                    ->setStatsTotal($items)
                    ->setRoute(route('property.index', [
                        'filter_table_id' => strtolower(Str::slug(Str::snake(PropertyTable::class))),
                        'class' => PropertyTable::class,
                        'filter_columns' => [
                            'status',
                        ],
                        'filter_operators' => [
                            '=',
                        ],
                        'filter_values' => [
                            'expired',
                        ],
                    ]))
                    ->init($widgets, $widgetSettings);
            }, 4, 2);

            add_filter(DASHBOARD_FILTER_ADMIN_LIST, function ($widgets, $widgetSettings) {
                $items = Account::query()->count();

                return (new DashboardWidgetInstance())
                    ->setType('stats')
                    ->setPermission('account.index')
                    ->setTitle(trans('plugins/real-estate::account.agents'))
                    ->setKey('widget_total_4')
                    ->setIcon('fas fa-users')
                    ->setColor('#3598dc')
                    ->setStatsTotal($items)
                    ->setRoute(route('account.index'))
                    ->init($widgets, $widgetSettings);
            }, 5, 2);

            if (defined('LANGUAGE_MODULE_SCREEN_NAME')) {
                add_action(BASE_ACTION_META_BOXES, [$this, 'addLanguageChooser'], 55, 2);
            }

            add_filter('social_login_before_saving_account', function ($data, $oAuth, $providerData) {
                if (Arr::get($providerData, 'model') == Account::class && Arr::get($providerData, 'guard') == 'account') {
                    $firstName = implode(' ', explode(' ', $oAuth->getName(), -1));
                    Arr::forget($data, 'name');
                    $data = array_merge($data, [
                        'first_name' => $firstName,
                        'last_name' => $lastName = trim(str_replace($firstName, '', $oAuth->getName())),
                        'username' => $oAuth->getNickname() ?: Str::slug($firstName . $lastName),
                    ]);
                }

                return $data;
            }, 49, 3);

            add_filter('social_login_before_creating_account', function ($data) {
                if (! RealEstateHelper::isRegisterEnabled()) {
                    return (new BaseHttpResponse())
                        ->setError()
                        ->setMessage(trans('auth.failed'));
                }

                return $data;
            }, 49);

            if (is_plugin_active('language') && is_plugin_active('language-advanced')) {
                add_filter(BASE_FILTER_BEFORE_RENDER_FORM, function ($form, $data) {
                    if (is_in_admin() &&
                        request()->segment(1) === 'account' &&
                        Auth::guard('account')->check() &&
                        Language::getCurrentAdminLocaleCode() != Language::getDefaultLocaleCode() &&
                        $data &&
                        $data->id &&
                        LanguageAdvancedManager::isSupported($data)
                    ) {
                        $refLang = null;

                        if (Language::getCurrentAdminLocaleCode() != Language::getDefaultLocaleCode()) {
                            $refLang = '?ref_lang=' . Language::getCurrentAdminLocaleCode();
                        }

                        $form->setFormOption(
                            'url',
                            route('public.account.language-advanced.save', $data->id) . $refLang
                        );
                    }

                    return $form;
                }, 9999, 2);
            }

            add_filter('account_dashboard_header', function ($html) {
                $customCSSFile = public_path(Theme::path() . '/css/style.integration.css');
                if (File::exists($customCSSFile)) {
                    $html .= Html::style(Theme::asset()
                        ->url('css/style.integration.css?v=' . filectime($customCSSFile)));
                }

                return $html . ThemeSupport::getCustomJS('header');
            }, 15);

            if (defined('PAGE_MODULE_SCREEN_NAME')) {
                add_filter(PAGE_FILTER_PAGE_NAME_IN_ADMIN_LIST, function (?string $name, Page $page) {
                    $subTitle = null;

                    switch ($page->id) {
                        case theme_option('properties_list_page_id'):
                            $subTitle = __('Properties List');

                            break;
                        case theme_option('projects_list_page_id'):
                            $subTitle = __('Projects List');

                            break;
                    }

                    if (! $subTitle) {
                        return $name;
                    }

                    $subTitle = Html::tag('span', $subTitle, ['class' => 'additional-page-name'])
                        ->toHtml();

                    if (Str::contains($name, ' —')) {
                        return $name . ', ' . $subTitle;
                    }

                    return $name . ' —' . $subTitle;
                }, 124, 2);
            }

            $pages = Page::query()->wherePublished()->pluck('name', 'id')->all();

            theme_option()
                ->setSection([
                    'title' => __('Real Estate'),
                    'desc' => __('Theme options for Real Estate'),
                    'id' => 'opt-text-subsection-real-estate',
                    'subsection' => true,
                    'icon' => 'fas fa-briefcase',
                ])
                ->setField([
                    'id' => 'projects_list_page_id',
                    'section_id' => 'opt-text-subsection-real-estate',
                    'type' => 'customSelect',
                    'label' => __('Projects List page'),
                    'attributes' => [
                        'name' => 'projects_list_page_id',
                        'list' => ['' => __('-- Select --')] + $pages,
                        'value' => '',
                        'options' => [
                            'class' => 'form-control',
                        ],
                    ],
                ])
                ->setField([
                    'id' => 'properties_list_page_id',
                    'section_id' => 'opt-text-subsection-real-estate',
                    'type' => 'customSelect',
                    'label' => __('Properties List page'),
                    'attributes' => [
                        'name' => 'properties_list_page_id',
                        'list' => ['' => __('-- Select --')] + $pages,
                        'value' => '',
                        'options' => [
                            'class' => 'form-control',
                        ],
                    ],
                ])
                ->setField([
                    'id' => 'number_of_projects_per_page',
                    'section_id' => 'opt-text-subsection-real-estate',
                    'type' => 'number',
                    'label' => __('Number of projects per page'),
                    'attributes' => [
                        'name' => 'number_of_projects_per_page',
                        'value' => 12,
                        'options' => [
                            'class' => 'form-control',
                        ],
                    ],
                ])
                ->setField([
                    'id' => 'number_of_properties_per_page',
                    'section_id' => 'opt-text-subsection-real-estate',
                    'type' => 'number',
                    'label' => __('Number of properties per page'),
                    'attributes' => [
                        'name' => 'number_of_properties_per_page',
                        'value' => 15,
                        'options' => [
                            'class' => 'form-control',
                        ],
                    ],
                ])
                ->setField([
                    'id' => 'number_of_related_projects',
                    'section_id' => 'opt-text-subsection-real-estate',
                    'type' => 'number',
                    'label' => __('Number of related projects'),
                    'attributes' => [
                        'name' => 'number_of_related_projects',
                        'value' => 8,
                        'options' => [
                            'class' => 'form-control',
                        ],
                    ],
                ])
                ->setField([
                    'id' => 'number_of_related_properties',
                    'section_id' => 'opt-text-subsection-real-estate',
                    'type' => 'number',
                    'label' => __('Number of related properties'),
                    'attributes' => [
                        'name' => 'number_of_related_properties',
                        'value' => 8,
                        'options' => [
                            'class' => 'form-control',
                        ],
                    ],
                ])
                ->setField([
                    'id' => 'latitude_longitude_center_on_properties_page',
                    'section_id' => 'opt-text-subsection-real-estate',
                    'type' => 'text',
                    'label' => __('Latitude longitude center on properties page'),
                    'attributes' => [
                        'name' => 'latitude_longitude_center_on_properties_page',
                        'value' => '43.615134, -76.393186',
                        'options' => [
                            'class' => 'form-control',
                        ],
                    ],
                ]);

            if (is_plugin_active('location')) {
                add_filter(BASE_FILTER_BEFORE_RENDER_FORM, function (FormAbstract $form, ?Model $data) {
                    if (get_class($data) == City::class) {
                        $form
                            ->addAfter('country_id', 'is_featured', 'onOff', [
                                'label' => trans('core/base::forms.is_featured'),
                                'default_value' => false,
                                'value' => $data->is_featured,
                            ]);
                    }

                    return $form;
                }, 120, 3);

                add_action([BASE_ACTION_AFTER_CREATE_CONTENT, BASE_ACTION_AFTER_UPDATE_CONTENT], function (string $screen, Request $request, $data): void {
                    if ($data instanceof City && $request->has('is_featured')) {
                        $data->is_featured = $request->input('is_featured');
                        $data->save();
                    }
                }, 120, 3);
            }
        });
    }

    protected function convertOrderAmount(float $amount): float
    {
        $currentCurrency = get_application_currency();

        if ($currentCurrency->is_default) {
            return $amount;
        }

        return (float)format_price($amount * $currentCurrency->exchange_rate, $currentCurrency, true);
    }

    public function registerTopHeaderNotification(?string $options): ?string
    {
        if (Auth::user()->hasPermission('consults.edit')) {
            $consults = Consult::query()
                ->where('status', ConsultStatusEnum::UNREAD)
                ->select(['id', 'name', 'email', 'phone', 'created_at'])
                ->orderByDesc('created_at')
                ->paginate(10);

            if ($consults->count() == 0) {
                return $options;
            }

            return $options . view('plugins/real-estate::notification', compact('consults'))->render();
        }

        return $options;
    }

    public function getUnReadCount(?string $number, string $menuId): ?string
    {
        if ($menuId == 'cms-plugins-consult') {
            $attributes = [
                'class' => 'badge badge-success menu-item-count unread-consults',
                'style' => 'display: none;',
            ];

            return Html::tag('span', '', $attributes)->toHtml();
        }

        return $number;
    }

    public function getMenuItemCount(array $data = []): array
    {
        if (Auth::user()->hasPermission('consult.index')) {
            $data[] = [
                'key' => 'unread-consults',
                'value' => Consult::query()->where('status', ConsultStatusEnum::UNREAD)->count(),
            ];
        }

        return $data;
    }

    public function registerMenuOptions(): void
    {
        if (Auth::user()->hasPermission('property_category.index')) {
            Menu::registerMenuOptions(Category::class, trans('plugins/real-estate::category.menu'));
        }
    }

    public function addLanguageChooser(string $priority, ?Model $model): void
    {
        if ($priority == 'head' && $model instanceof Category) {
            $languages = Language::getActiveLanguage(['lang_id', 'lang_name', 'lang_code', 'lang_flag']);

            if ($languages->count() < 2) {
                return;
            }

            echo view('plugins/language::partials.admin-list-language-chooser', [
                'route' => 'property_category.index',
                'languages' => $languages,
            ])->render();
        }
    }
}
