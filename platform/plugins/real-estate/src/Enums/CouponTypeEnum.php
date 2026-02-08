<?php

namespace Botble\RealEstate\Enums;

use Botble\Base\Facades\Html;
use Botble\Base\Supports\Enum;
use Illuminate\Support\HtmlString;

/**
 * @method static \Botble\RealEstate\Enums\CouponTypeEnum PERCENTAGE()
 * @method static \Botble\RealEstate\Enums\CouponTypeEnum FIXED()
 */
class CouponTypeEnum extends Enum
{
    public const PERCENTAGE = 'percentage';

    public const FIXED = 'fixed';

    public static $langPath = 'plugins/real-estate::coupon.types';

    public function toHtml(): HtmlString|string
    {
        return match ($this->value) {
            self::PERCENTAGE => Html::tag('span', self::PERCENTAGE()->label(), ['class' => 'label-info status-label'])->toHtml(),
            self::FIXED => Html::tag('span', self::FIXED()->label(), ['class' => 'label-success status-label'])->toHtml(),
            default => parent::toHtml(),
        };
    }
}
