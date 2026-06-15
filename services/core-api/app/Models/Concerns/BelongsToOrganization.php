<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Support\Tenancy\Tenancy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * يُطبَّق على كل كيان تشغيلي يحمل organization_id. PRD §12, ADR-003.
 * - يفرض OrganizationScope تلقائيًا (تصفية على الخادم).
 * - يملأ organization_id من السياق عند الإنشاء إن لم يُحدَّد.
 */
trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope());

        static::creating(function ($model) {
            if (empty($model->organization_id)) {
                $model->organization_id = app(Tenancy::class)->id();
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
