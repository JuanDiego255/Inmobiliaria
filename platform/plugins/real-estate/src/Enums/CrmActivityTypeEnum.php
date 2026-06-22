<?php

namespace Botble\RealEstate\Enums;

use Botble\Base\Supports\Enum;
use Illuminate\Support\HtmlString;
use Botble\Base\Facades\Html;

class CrmActivityTypeEnum extends Enum
{
    public const NOTE = 'note';
    public const CALL = 'call';
    public const EMAIL = 'email';
    public const WHATSAPP = 'whatsapp';
    public const VISIT = 'visit';
    public const MEETING = 'meeting';
    public const META_AUTO = 'meta_auto';

    public static $langPath = 'plugins/real-estate::crm.activity_types';

    public function toHtml(): HtmlString|string|null
    {
        return match ($this->value) {
            self::NOTE => Html::tag('span', 'Nota', ['class' => 'label-default status-label'])->toHtml(),
            self::CALL => Html::tag('span', 'Llamada', ['class' => 'label-info status-label'])->toHtml(),
            self::EMAIL => Html::tag('span', 'Email', ['class' => 'label-primary status-label'])->toHtml(),
            self::WHATSAPP => Html::tag('span', 'WhatsApp', ['class' => 'label-success status-label'])->toHtml(),
            self::VISIT => Html::tag('span', 'Visita', ['class' => 'label-warning status-label'])->toHtml(),
            self::MEETING => Html::tag('span', 'Reunión', ['class' => 'label-danger status-label'])->toHtml(),
            self::META_AUTO => Html::tag('span', 'Meta (Auto)', ['class' => 'label-info status-label', 'style' => 'background-color:#0081FB;color:#fff'])->toHtml(),
            default => null,
        };
    }

    public static function labels(): array
    {
        return [
            self::NOTE => 'Nota',
            self::CALL => 'Llamada',
            self::EMAIL => 'Email',
            self::WHATSAPP => 'WhatsApp',
            self::VISIT => 'Visita',
            self::MEETING => 'Reunión',
            self::META_AUTO => 'Meta (Auto)',
        ];
    }

    public static function icons(): array
    {
        return [
            self::NOTE => 'fas fa-sticky-note',
            self::CALL => 'fas fa-phone',
            self::EMAIL => 'fas fa-envelope',
            self::WHATSAPP => 'fab fa-whatsapp',
            self::VISIT => 'fas fa-walking',
            self::MEETING => 'fas fa-users',
            self::META_AUTO => 'fab fa-meta',
        ];
    }

    public function icon(): string
    {
        return static::icons()[$this->value] ?? 'fas fa-circle';
    }
}
