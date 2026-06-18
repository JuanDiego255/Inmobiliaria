<?php

namespace Botble\RealEstate\Enums;

use Botble\Base\Supports\Enum;
use Illuminate\Support\HtmlString;
use Botble\Base\Facades\Html;

class CrmLeadStageEnum extends Enum
{
    public const NUEVO = 'nuevo';
    public const CONTACTADO = 'contactado';
    public const CALIFICADO = 'calificado';
    public const EN_NEGOCIACION = 'en_negociacion';
    public const GANADO = 'ganado';
    public const PERDIDO = 'perdido';

    public static $langPath = 'plugins/real-estate::crm.lead_stages';

    public function toHtml(): HtmlString|string|null
    {
        return match ($this->value) {
            self::NUEVO => Html::tag('span', 'Nuevo', ['class' => 'label-info status-label'])->toHtml(),
            self::CONTACTADO => Html::tag('span', 'Contactado', ['class' => 'label-primary status-label'])->toHtml(),
            self::CALIFICADO => Html::tag('span', 'Calificado', ['class' => 'label-warning status-label'])->toHtml(),
            self::EN_NEGOCIACION => Html::tag('span', 'En Negociación', ['class' => 'label-default status-label'])->toHtml(),
            self::GANADO => Html::tag('span', 'Ganado', ['class' => 'label-success status-label'])->toHtml(),
            self::PERDIDO => Html::tag('span', 'Perdido', ['class' => 'label-danger status-label'])->toHtml(),
            default => null,
        };
    }

    public static function labels(): array
    {
        return [
            self::NUEVO => 'Nuevo',
            self::CONTACTADO => 'Contactado',
            self::CALIFICADO => 'Calificado',
            self::EN_NEGOCIACION => 'En Negociación',
            self::GANADO => 'Ganado',
            self::PERDIDO => 'Perdido',
        ];
    }
}
