<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * الدور (Role). PRD §10, §22. RBAC مفروض على الخادم.
 */
class Role extends Model
{
    use HasFactory, BelongsToOrganization, Auditable;

    protected $fillable = ['organization_id', 'key', 'name_ar', 'name_en', 'is_system', 'max_discount_amount'];

    protected $casts = ['is_system' => 'boolean', 'max_discount_amount' => 'decimal:2'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('branch_id');
    }
}
