<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الفاتورة (Invoice) — تصميم النموذج في 0A، التكامل الفعلي مع ZATCA في 0C.
 * PRD §16, §17, ملحق أ. ADR-006: Decimal لا Float، العملة SAR.
 * النزاهة: لا تعديل/حذف بعد الإصدار؛ التصحيح عبر Credit/Debit Note (يُفرض في الكود/0C).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('egs_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('buyer_person_id')->nullable()->constrained('persons')->nullOnDelete();

            // هوية المستند (PRD §16.3)
            $table->uuid('uuid')->unique();                 // UUID
            $table->string('document_number')->nullable();  // الرقم التسلسلي للمستند
            $table->string('invoice_type_code', 8)->nullable();  // 388/381/383 ...
            $table->string('transaction_type', 8)->nullable();   // standard | simplified
            $table->foreignId('original_invoice_id')->nullable() // للإشعارات الدائنة/المدينة
                  ->constrained('invoices')->nullOnDelete();

            // لقطة بيانات البائع/المشتري وقت الإصدار (§17 حفظ القيم وقت العملية)
            $table->jsonb('seller_snapshot')->nullable();
            $table->jsonb('buyer_snapshot')->nullable();

            $table->string('currency', 3)->default('SAR');  // ADR-006
            $table->timestamp('issued_at')->nullable();

            // المبالغ — Decimal (ADR-006)
            $table->decimal('subtotal', 15, 2)->default(0);          // قبل الضريبة
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('total_including_tax', 15, 2)->default(0); // المستحق
            $table->jsonb('tax_breakdown')->nullable();              // حسب الفئة/النسبة

            // حالة الفاتورة (PRD §16.5)
            $table->string('status', 32)->default('draft');
            // draft | validated | issue_requested | pending_clearance | pending_reporting
            // | cleared | reported | rejected | adjustment_required | adjusted | archived

            // حقول ZATCA (PRD §16.3) — تُملأ في 0C
            $table->string('pih')->nullable();              // Previous Invoice Hash
            $table->unsignedBigInteger('icv')->nullable();  // Invoice Counter Value
            $table->text('invoice_hash')->nullable();
            $table->text('qr_payload')->nullable();
            $table->text('cryptographic_stamp')->nullable();
            $table->longText('xml_payload')->nullable();
            $table->longText('cleared_xml')->nullable();
            $table->jsonb('submission_warnings')->nullable();
            $table->jsonb('submission_errors')->nullable();

            $table->timestamps();
            // لا softDeletes — الفاتورة الصادرة لا تُحذف (PRD §17). الأرشفة عبر status=archived.

            $table->index(['organization_id', 'status']);
            $table->unique(['organization_id', 'document_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
