<?php

namespace Botble\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;

class TenantFeature extends Model
{
    protected $connection = 'mysql';

    protected $table = 're_tenant_features';

    protected $fillable = [
        'tenant_id',
        'photos_enabled',
        'interactive_buttons_enabled',
        'dashboard_enabled',
        'pdf_documents_enabled',
        'plan_name',
        'plan_started_at',
        'plan_expires_at',
    ];

    protected $casts = [
        'photos_enabled' => 'boolean',
        'interactive_buttons_enabled' => 'boolean',
        'dashboard_enabled' => 'boolean',
        'pdf_documents_enabled' => 'boolean',
        'plan_started_at' => 'datetime',
        'plan_expires_at' => 'datetime',
    ];

    public static function for(string $tenantId): static
    {
        return static::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'photos_enabled' => false,
                'interactive_buttons_enabled' => false,
                'dashboard_enabled' => false,
                'pdf_documents_enabled' => false,
            ]
        );
    }

    public function hasFeature(string $feature): bool
    {
        $column = $feature . '_enabled';

        if (! array_key_exists($column, $this->attributes) && ! $this->hasAttribute($column)) {
            return false;
        }

        if ($this->plan_expires_at && $this->plan_expires_at->isPast()) {
            return false;
        }

        return (bool) $this->$column;
    }

    protected function hasAttribute(string $key): bool
    {
        return in_array($key, $this->fillable);
    }
}
