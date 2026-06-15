<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * الشخص (Person 360). PRD §11.
 * الحقول الحساسة مشفّرة (encrypted cast) ولا تظهر افتراضيًا.
 */
class Person extends Model
{
    use HasFactory, SoftDeletes, BelongsToOrganization, Auditable;

    // اسم الجدول صريح لتفادي جمع Laravel الشاذ (people).
    protected $table = 'persons';

    protected $fillable = [
        'organization_id', 'first_name', 'last_name', 'full_name',
        'country_code', 'phone_national', 'phone_e164', 'email',
        'national_id_hash', 'national_id_encrypted', 'birth_date', 'gender', 'nationality',
    ];

    protected $hidden = ['national_id_encrypted', 'national_id_hash'];

    protected $casts = [
        'birth_date' => 'date',
        'merged_at' => 'datetime',
        'national_id_encrypted' => 'encrypted', // PRD §11 بيانات حساسة مشفّرة
    ];

    public function contactMethods(): HasMany
    {
        return $this->hasMany(ContactMethod::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }

    public function isMerged(): bool
    {
        return $this->merged_into_person_id !== null;
    }
}
