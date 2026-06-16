<?php

namespace App\Services\Compliance\Zatca;

use App\Models\Invoice;
use App\Services\Compliance\Zatca\Contracts\ZatcaClient;

/**
 * مشغّل محاكاة ZATCA — يعمل بالكامل بلا هيئة (تطوير/Staging). PRD §16.6.
 * يحاكي سلسلة ICV/PIH والـ Hash والـ QR (TLV) دون كسر السلسلة.
 * لا يُستخدم في الإنتاج — يُستبدل بـ ProductionZatcaClient عند الربط.
 */
class SimulationZatcaClient implements ZatcaClient
{
    public function submit(Invoice $invoice): ZatcaResult
    {
        $org = $invoice->organization()->first();

        // سلسلة ICV/PIH على مستوى المؤسسة (لا تُكسر — §16.6).
        $previous = Invoice::where('organization_id', $invoice->organization_id)
            ->whereNotNull('icv')
            ->where('id', '!=', $invoice->id)
            ->orderByDesc('icv')
            ->lockForUpdate()
            ->first();

        $icv = $previous ? ((int) $previous->icv + 1) : 1;
        $pih = $previous?->invoice_hash ?? base64_encode(hash('sha256', '0', true));

        $canonical = implode('|', [
            $pih,
            $invoice->uuid,
            (string) $invoice->total_including_tax,
            (string) $invoice->tax_total,
            (string) ($invoice->issued_at?->toIso8601String() ?? now()->toIso8601String()),
        ]);
        $invoiceHash = base64_encode(hash('sha256', $canonical, true));

        $qr = $this->buildQrTlv(
            sellerName: $org?->name_ar ?? 'BAYT ALMOSWER',
            vatNumber: $org?->vat_number ?? '300000000000003',
            timestamp: $invoice->issued_at?->toIso8601String() ?? now()->toIso8601String(),
            total: (string) $invoice->total_including_tax,
            vat: (string) $invoice->tax_total,
            invoiceHash: $invoiceHash,
        );

        // B2C → Reporting، B2B/B2G → Clearance (§16.2).
        $status = $invoice->transaction_type === 'standard' ? 'cleared' : 'reported';

        return new ZatcaResult(
            status: $status,
            invoiceHash: $invoiceHash,
            qrPayload: $qr,
            cryptographicStamp: base64_encode(hash('sha256', 'SIM-STAMP|' . $invoiceHash, true)),
            clearedXml: $status === 'cleared' ? '<Invoice simulated="true"/>' : null,
            icv: $icv,
            pih: $pih,
            warnings: ['SIMULATION: لم تُرسل للهيئة فعليًا.'],
        );
    }

    /**
     * ترميز QR وفق نمط TLV (Tag-Length-Value) ثم Base64 — مبسّط للمحاكاة.
     */
    private function buildQrTlv(
        string $sellerName,
        string $vatNumber,
        string $timestamp,
        string $total,
        string $vat,
        string $invoiceHash,
    ): string {
        $fields = [1 => $sellerName, 2 => $vatNumber, 3 => $timestamp, 4 => $total, 5 => $vat, 6 => $invoiceHash];
        $tlv = '';
        foreach ($fields as $tag => $value) {
            $bytes = (string) $value;
            $tlv .= chr($tag) . chr(strlen($bytes)) . $bytes;
        }

        return base64_encode($tlv);
    }
}
