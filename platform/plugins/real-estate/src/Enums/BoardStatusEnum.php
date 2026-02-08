<?php

namespace Botble\RealEstate\Enums;

use Botble\Base\Facades\Html;
use Botble\Base\Supports\Enum;
use Illuminate\Support\HtmlString;

/**
 * @method static BoardStatusEnum ACTIVE()
 * @method static BoardStatusEnum INACTIVE()
 */
class BoardStatusEnum extends Enum
{
    public const ACTIVE = 'active';
    public const INACTIVE = 'inactive';

    public static $langPath = 'plugins/real-estate::board.statuses';

    public function toHtml(): HtmlString|string|null
    {
        return match ($this->value) {
            self::ACTIVE => Html::tag('span', self::ACTIVE()->label(), ['class' => 'label-success status-label'])
                ->toHtml(),
            self::INACTIVE => Html::tag('span', self::INACTIVE()->label(), ['class' => 'label-warning status-label'])
                ->toHtml(),
            default => null,
        };
    }
}
