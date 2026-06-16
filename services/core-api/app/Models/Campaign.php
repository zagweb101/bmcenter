<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

/** حملة تسويقية. PRD §13. */
class Campaign extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'name', 'channel', 'starts_on', 'ends_on', 'is_active',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'is_active' => 'boolean',
    ];
}
