<?php

namespace Botble\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;

class TenantMailConfig extends Model
{
    protected $connection = 'mysql';

    protected $table = 're_tenant_mail_configs';

    protected $fillable = [
        'tenant_id',
        'mail_driver',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
        'notification_emails',
        'notification_whatsapp',
        'is_active',
    ];

    protected $casts = [
        'notification_emails' => 'array',
        'is_active' => 'boolean',
        'mail_port' => 'integer',
    ];

    protected $hidden = [
        'mail_password',
    ];
}
