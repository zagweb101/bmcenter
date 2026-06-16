<?php

namespace App\Services\Compliance\Zatca;

use App\Models\Invoice;
use App\Services\Compliance\Zatca\Contracts\ZatcaClient;
use RuntimeException;

/**
 * مشغّل الإنتاج لـ ZATCA — نقطة الربط الفعلي. PRD §16, §33.
 *
 * عند الربط، يُنفَّذ هنا:
 *  - توليد XML المتوافق (UBL 2.1) + التوقيع بالـ CSID (من Secrets Vault).
 *  - Standard → Clearance API | Simplified → Reporting API (خلال المدة النظامية).
 *  - حفظ XML المخلّص/الاستجابة/الـ Hashes + Queue/Retry/DLQ (§16.6).
 *
 * يبقى فارغًا عمدًا حتى اكتمال Onboarding/CSID ومراجعة المستشار الضريبي.
 */
class ProductionZatcaClient implements ZatcaClient
{
    public function submit(Invoice $invoice): ZatcaResult
    {
        throw new RuntimeException(
            'ربط ZATCA الإنتاجي غير مُفعَّل بعد: يلزم Onboarding وCSID ومراجعة مستشار ضريبي (PRD §16, §33). '
            . 'استخدم ZATCA_DRIVER=simulation حتى اكتمال الربط.'
        );
    }
}
