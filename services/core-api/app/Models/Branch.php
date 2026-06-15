<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * الفرع (Branch). PRD §12.
 */
class Branch extends Model
{
    use HasFactory, SoftDeletes, BelongsToOrganization;

    protected $fillable = [
        'organization_id', 'name_ar', 'name_en', 'code', 'city', 'district',
        'street', 'building_number', 'postal_code', 'phone', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];
}
