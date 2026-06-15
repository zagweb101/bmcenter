<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * الصلاحية (Permission) — عامة عبر المؤسسات (قاموس موحّد). PRD §22.
 */
class Permission extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'name_ar', 'group'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
