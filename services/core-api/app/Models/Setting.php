<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

/**
 * إعداد على مستوى المؤسسة (Setting). PRD §8.1.
 */
class Setting extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'key', 'value', 'type'];

    protected $casts = ['value' => 'array'];
}
