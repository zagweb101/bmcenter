<?php

namespace App\Services\Compliance\Zatca;

/**
 * نتيجة إرسال فاتورة إلى ZATCA. PRD §16.3.
 */
class ZatcaResult
{
    /**
     * @param string $status cleared | reported | rejected
     * @param array<string> $warnings
     * @param array<string> $errors
     */
    public function __construct(
        public string $status,
        public ?string $invoiceHash = null,
        public ?string $qrPayload = null,
        public ?string $cryptographicStamp = null,
        public ?string $clearedXml = null,
        public ?int $icv = null,
        public ?string $pih = null,
        public array $warnings = [],
        public array $errors = [],
    ) {
    }

    public function ok(): bool
    {
        return in_array($this->status, ['cleared', 'reported'], true);
    }
}
