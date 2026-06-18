<?php

namespace Botble\RealEstate\Enums;

use Botble\Base\Supports\Enum;
use Illuminate\Support\HtmlString;
use Botble\Base\Facades\Html;

class CrmLeadSourceEnum extends Enum
{
    public const MANUAL = 'manual';
    public const WEBSITE = 'website';
    public const CONSULT = 'consult';
    public const REFERRAL = 'referral';
    public const SOCIAL = 'social';
    public const PHONE = 'phone';
    public const OTHER = 'other';

    public static $langPath = 'plugins/real-estate::crm.lead_sources';

    public function toHtml(): HtmlString|string|null
    {
        return match ($this->value) {
            self::MANUAL => Html::tag('span', 'Manual', ['class' => 'label-default status-label'])->toHtml(),
            self::WEBSITE => Html::tag('span', 'Sitio Web', ['class' => 'label-primary status-label'])->toHtml(),
            self::CONSULT => Html::tag('span', 'Consulta', ['class' => 'label-info status-label'])->toHtml(),
            self::REFERRAL => Html::tag('span', 'Referido', ['class' => 'label-success status-label'])->toHtml(),
            self::SOCIAL => Html::tag('span', 'Redes Sociales', ['class' => 'label-warning status-label'])->toHtml(),
            self::PHONE => Html::tag('span', 'Teléfono', ['class' => 'label-default status-label'])->toHtml(),
            self::OTHER => Html::tag('span', 'Otro', ['class' => 'label-default status-label'])->toHtml(),
            default => null,
        };
    }

    public static function labels(): array
    {
        return [
            self::MANUAL => 'Manual',
            self::WEBSITE => 'Sitio Web',
            self::CONSULT => 'Consulta',
            self::REFERRAL => 'Referido',
            self::SOCIAL => 'Redes Sociales',
            self::PHONE => 'Teléfono',
            self::OTHER => 'Otro',
        ];
    }
}
