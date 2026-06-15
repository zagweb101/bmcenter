<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * طلب حق صاحب البيانات (PDPL). PRD §19.5.
 */
class PrivacyRequest extends Model
{
    use HasFactory, BelongsToOrganization, Auditable;

    protected $fillable = [
        'organization_id', 'person_id', 'type', 'status', 'subject_reason',
        'identity_verified', 'verification_method', 'handled_by_user_id',
        'due_at', 'resolved_at', 'resolution_note',
    ];

    protected $casts = [
        'identity_verified' => 'boolean',
        'due_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by_user_id');
    }
}
