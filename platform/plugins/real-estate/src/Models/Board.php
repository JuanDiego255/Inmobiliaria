<?php

namespace Botble\RealEstate\Models;

use Botble\Base\Casts\SafeContent;
use Botble\Base\Models\BaseModel;
use Botble\RealEstate\Enums\BoardStatusEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Board extends BaseModel
{
    protected $table = 're_boards';

    protected $fillable = [
        'name',
        'description',
        'client_id',
        'token',
        'status',
    ];

    protected $casts = [
        'status' => BoardStatusEnum::class,
        'name' => SafeContent::class,
        'description' => SafeContent::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (Board $board) {
            if (empty($board->token)) {
                $board->token = Str::random(40);
            }
        });

        static::deleting(function (Board $board) {
            $board->properties()->detach();
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withDefault();
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 're_board_properties')
            ->withPivot(['notes', 'order', 'property_status'])
            ->withTimestamps()
            ->orderBy('re_board_properties.order');
    }

    public function getPublicUrlAttribute(): string
    {
        return route('public.board.show', $this->token);
    }

    public function getWhatsappShareUrlAttribute(): string
    {
        $text = urlencode(trans('plugins/real-estate::board.share_message', [
            'name' => $this->name,
            'url' => $this->public_url,
        ]));

        return "https://wa.me/?text={$text}";
    }

    public function getEmailShareUrlAttribute(): string
    {
        $subject = urlencode($this->name);
        $body = urlencode(trans('plugins/real-estate::board.share_message', [
            'name' => $this->name,
            'url' => $this->public_url,
        ]));

        return "mailto:?subject={$subject}&body={$body}";
    }
}
