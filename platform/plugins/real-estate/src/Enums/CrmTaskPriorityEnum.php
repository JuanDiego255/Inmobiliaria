<?php

namespace Botble\RealEstate\Enums;

use Botble\Base\Supports\Enum;
use Illuminate\Support\HtmlString;
use Botble\Base\Facades\Html;

class CrmTaskPriorityEnum extends Enum
{
    public const LOW = 'low';
    public const MEDIUM = 'medium';
    public const HIGH = 'high';
    public const URGENT = 'urgent';

    public static $langPath = 'plugins/real-estate::crm.task_priorities';

    public function toHtml(): HtmlString|string|null
    {
        return match ($this->value) {
            self::LOW => Html::tag('span', 'Baja', ['class' => 'label-default status-label'])->toHtml(),
            self::MEDIUM => Html::tag('span', 'Media', ['class' => 'label-info status-label'])->toHtml(),
            self::HIGH => Html::tag('span', 'Alta', ['class' => 'label-warning status-label'])->toHtml(),
            self::URGENT => Html::tag('span', 'Urgente', ['class' => 'label-danger status-label'])->toHtml(),
            default => null,
        };
    }

    public static function labels(): array
    {
        return [
            self::LOW => 'Baja',
            self::MEDIUM => 'Media',
            self::HIGH => 'Alta',
            self::URGENT => 'Urgente',
        ];
    }

    public function icon(): string
    {
        return match ($this->value) {
            self::LOW => 'fas fa-arrow-down',
            self::MEDIUM => 'fas fa-minus',
            self::HIGH => 'fas fa-arrow-up',
            self::URGENT => 'fas fa-exclamation',
            default => 'fas fa-circle',
        };
    }
}
