<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** المجموعة (Cohort). PRD §14. */
class Cohort extends Model
{
    use BelongsToOrganization, Auditable, SoftDeletes;

    protected $fillable = [
        'organization_id', 'branch_id', 'course_id', 'trainer_user_id',
        'name', 'capacity', 'price', 'tax_rate', 'starts_on', 'ends_on', 'status',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'starts_on' => 'date',
        'ends_on' => 'date',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
