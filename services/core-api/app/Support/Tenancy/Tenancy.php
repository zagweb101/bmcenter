<?php

namespace App\Support\Tenancy;

/**
 * حاوية سياق المؤسسة الحالية (Tenant context). PRD §12, ADR-003.
 * تُضبط من Middleware/Auth على الخادم — لا تُقبل قيمة المؤسسة من الواجهة كمصدر ثقة.
 */
class Tenancy
{
    protected ?int $organizationId = null;

    public function set(?int $organizationId): void
    {
        $this->organizationId = $organizationId;
    }

    public function id(): ?int
    {
        // الأولوية للقيمة المضبوطة صراحةً، ثم مؤسسة المستخدم المصادَق.
        return $this->organizationId ?? auth()->user()?->organization_id;
    }

    public function check(): bool
    {
        return $this->id() !== null;
    }

    public function forget(): void
    {
        $this->organizationId = null;
    }
}
