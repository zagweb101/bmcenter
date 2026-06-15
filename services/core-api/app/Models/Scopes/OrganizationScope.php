<?php

namespace App\Models\Scopes;

use App\Support\Tenancy\Tenancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global Scope يفرض تصفية organization_id على الخادم. PRD §12, ADR-003.
 * يمنع تسرّب البيانات بين المؤسسات افتراضيًا.
 */
class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $organizationId = app(Tenancy::class)->id();

        if ($organizationId !== null) {
            $builder->where($model->getTable() . '.organization_id', $organizationId);
        }
    }
}
