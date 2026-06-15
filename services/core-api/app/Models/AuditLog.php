<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * سجل تدقيق (Audit Log). PRD §6, §22. append-only (لا تعديل/حذف).
 * لا BelongsToOrganization scope تلقائي لأن بعض الأحداث قد تسبق ضبط السياق؛
 * organization_id يُملأ يدويًا عبر خدمة التدقيق.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null; // append-only: created_at فقط

    protected $fillable = [
        'organization_id', 'actor_user_id', 'action',
        'subject_type', 'subject_id', 'old_values', 'new_values', 'context',
        'ip_address', 'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
