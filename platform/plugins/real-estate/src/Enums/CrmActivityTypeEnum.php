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
        ];
    }

    public function icon(): string
    {
        return static::icons()[$this->value] ?? 'fas fa-circle';
    }
}
