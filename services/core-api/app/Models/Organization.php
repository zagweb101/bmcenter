<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * المؤسسة (Tenant root). PRD §12. لا تستخدم BelongsToOrganization (هي الجذر).
 */
class Organization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name_ar', 'name_en', 'slug', 'vat_number', 'commercial_registration',
        'contact_email', 'contact_phone', 'country_code', 'city', 'district',
        'street', 'building_number', 'postal_code', 'default_currency', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }
}
