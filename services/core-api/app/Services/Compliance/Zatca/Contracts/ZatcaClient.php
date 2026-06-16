<?php

namespace App\Services\Compliance\Zatca\Contracts;

use App\Models\Invoice;
use App\Services\Compliance\Zatca\ZatcaResult;

/**
 * واجهة عميل ZATCA. PRD §16, ADR-004.
 * Standard (B2B/B2G) → Clearance. Simplified (B2C) → Reporting.
 * تُبدَّل بين Simulation/Production عبر الإعداد دون تغيير منطق الأعمال.
 */
interface ZatcaClient
{
    /**
     * يرسل الفاتورة للتخليص/الإبلاغ ويعيد النتيجة (Hash/QR/Stamp/Status).
     */
    public function submit(Invoice $invoice): ZatcaResult;
}
