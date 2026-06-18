<?php

namespace Botble\RealEstate\Enums;

use Botble\Base\Supports\Enum;
use Illuminate\Support\HtmlString;
use Botble\Base\Facades\Html;

class CrmTaskStatusEnum extends Enum
{
    public const PENDING = 'pending';
    public const IN_PROGRESS = 'in_progress';
    public const COMPLETED = 'completed';
    public const CANCELLED = 'cancelled';

    public static $langPath = 'plugins/real-estate::crm.task_statuses';

    public function toHtml(): HtmlString|string|null
    {
        return match ($this->value) {
            self::PENDING => Html::tag('span', 'Pendiente', ['class' => 'label-warning status-label'])->toHtml(),
            self::IN_PROGRESS => Html::tag('span', 'En Progreso', ['class' => 'label-info status-label'])->toHtml(),
            self::COMPLETED => Html::tag('span', 'Completada', ['class' => 'label-success status-label'])->toHtml(),
            self::CANCELLED => Html::tag('span', 'Cancelada', ['class' => 'label-default status-label'])->toHtml(),
            default => null,
        };
    }

    public static function labels(): array
    {
        return [
            self::PENDING => 'Pendiente',
            self::IN_PROGRESS => 'En Progreso',
            self::COMPLETED => 'Completada',
            self::CANCELLED => 'Cancelada',
        ];
    }
}
