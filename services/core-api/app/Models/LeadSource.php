<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

/** مصدر Lead. PRD §13. */
class LeadSource extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'key', 'name_ar', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
