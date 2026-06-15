<?php

namespace App\Models\Concerns;

use App\Services\Audit\AuditLogger;

/**
 * يربط الكيان بسجل التدقيق تلقائيًا (إنشاء/تعديل/حذف/استعادة). PRD §6, §22.
 * يمكن للنموذج تعريف auditRedactedKeys() لحجب حقول إضافية،
 * أو $auditIgnore لتجاهل تغييرات حقول بعينها (مثل الطوابع الزمنية).
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            // getAttributes (الخام) لتضمين الحقول المخفية مع حجبها لاحقًا — لا كشف للقيمة.
            app(AuditLogger::class)->record('created', $model, null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $changes = $model->auditableChanges();
            if (empty($changes)) {
                return;
            }
            $old = array_intersect_key($model->getOriginal(), $changes);
            app(AuditLogger::class)->record('updated', $model, $old, $changes);
        });

        static::deleted(function ($model) {
            app(AuditLogger::class)->record('deleted', $model, $model->getAttributes(), null);
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                app(AuditLogger::class)->record('restored', $model, null, $model->getAttributes());
            });
        }
    }

    /**
     * التغييرات الجديرة بالتدقيق (تستبعد الطوابع الزمنية وما في $auditIgnore).
     */
    public function auditableChanges(): array
    {
        $ignore = array_merge(
            [$this->getUpdatedAtColumn(), 'remember_token'],
            $this->auditIgnore ?? [],
        );

        return array_diff_key($this->getChanges(), array_flip(array_filter($ignore)));
    }
}
