<?php

namespace Botble\RealEstate\Models;

use Botble\Base\Casts\SafeContent;
use Botble\Base\Models\BaseModel;
use Botble\RealEstate\Enums\ClientStatusEnum;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends BaseModel
{
    protected $table = 're_clients';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'occupation',
        'notes',
        'age',
        'status',
    ];

    protected $casts = [
        'status' => ClientStatusEnum::class,
        'name' => SafeContent::class,
        'address' => SafeContent::class,
        'occupation' => SafeContent::class,
        'notes' => SafeContent::class,
        'age' => 'int',
    ];

    public function boards(): HasMany
    {
        return $this->hasMany(Board::class);
    }
}
