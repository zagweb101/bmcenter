<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Support\Tenancy\Tenancy;
use Illuminate\Database\Eloquent\Model;

/**
 * خدمة سجل التدقيق (Audit Log). PRD §6, §22.
 * append-only؛ تُخفي الحقول الحساسة قبل الكتابة (لا بيانات شخصية في السجلات).
 */
class AuditLogger
{
    /**
     * مفاتيح تُحجب دائمًا من قيم السجل (PRD §22).
     */
    public const ALWAYS_REDACT = [
        'password', 'remember_token',
        'national_id_encrypted', 'national_id_hash',
        'private_key_ref', 'compliance_csid_ref', 'production_csid_ref',
    ];

    public function record(
        string $action,
        ?Model $subject = null,
        ?array $old = null,
        ?array $new = null,
        array $context = [],
    ): AuditLog {
        $extraRedact = $subject && method_exists($subject, 'auditRedactedKeys')
            ? $subject->auditRedactedKeys()
            : [];

        return AuditLog::create([
            'organization_id' => $this->resolveOrganizationId($subject),
            'actor_user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'old_values' => $this->redact($old, $extraRedact),
            'new_values' => $this->redact($new, $extraRedact),
            'context' => array_merge($this->requestContext(), $context) ?: null,
            'ip_address' => $this->safe(fn () => request()?->ip()),
            'user_agent' => $this->safe(fn () => request()?->userAgent()),
        ]);
    }

    /**
     * يسجّل وصولًا لبيانات حساسة (PRD §11: كل وصول لبيانات حساسة يُسجَّل).
     */
    public function logSensitiveAccess(Model $subject, array $fields, array $context = []): AuditLog
    {
        return $this->record('viewed_sensitive', $subject, null, null, array_merge($context, [
            'fields' => array_values($fields),
        ]));
    }

    protected function resolveOrganizationId(?Model $subject): ?int
    {
        if ($subject && isset($subject->organization_id)) {
            return (int) $subject->organization_id;
        }

        return app(Tenancy::class)->id();
    }

    protected function redact(?array $values, array $extra = []): ?array
    {
        if ($values === null) {
            return null;
        }

        $keys = array_merge(self::ALWAYS_REDACT, $extra);

        foreach ($values as $key => $value) {
            if (in_array($key, $keys, true)) {
                $values[$key] = '[REDACTED]';
            }
        }

        return $values;
    }

    protected function requestContext(): array
    {
        return $this->safe(function () {
            $request = request();
            if (! $request || app()->runningInConsole()) {
                return [];
            }

            return array_filter([
                'method' => $request->method(),
                'path' => $request->path(),
                'route' => optional($request->route())->getName(),
            ]);
        }) ?? [];
    }

    /**
     * يلتقط أي استثناء (مثل غياب request في CLI) ويعيد null.
     */
    protected function safe(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (\Throwable) {
            return null;
        }
    }
}
