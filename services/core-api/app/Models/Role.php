<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * الدور (Role). PRD §10, §22. RBAC مفروض على الخادم.
 */
class Role extends Model
{
    use HasFactory, BelongsToOrganization;

    protected $fillable = ['organization_id', 'key', 'name_ar', 'name_en', 'is_system'];

    protected $casts = ['is_system' => 'boolean'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('branch_id');
    }
}
