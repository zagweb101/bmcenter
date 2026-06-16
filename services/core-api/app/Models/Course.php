<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** الدورة (Course). PRD §14. */
class Course extends Model
{
    use BelongsToOrganization, Auditable, SoftDeletes;

    protected $fillable = [
        'organization_id', 'code', 'name_ar', 'name_en',
        'description', 'default_price', 'is_active',
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function cohorts(): HasMany
    {
        return $this->hasMany(Cohort::class);
    }
}
